<?php

namespace IIIF\API;

use MediaWiki\MediaWikiServices;
use Title;
use LocalFile;
//use MediaWiki\Revision\RevisionStore;
//use MediaWiki\Revision\RevisionRecord;
//use SMW\DIWikiPage;
use Wikimedia\ParamValidator\ParamValidator;
use IIIF\IIIFMwRemote;
use IIIF\IIIFUtils;
// use IIIF\API\IIIFMwImgAPI;
use IIIF\IIIFParsers\IIIFMwImageUtils;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIF\SMW\IIIFSMW;

/**
 * Creates IIIF Presentation API using MediaWiki file system
 * Supports image files from local MediaWiki repository
 * API-based support for external MediaWiki repos incl. Wikimedia Commons
 * Image Service: Level 0, tileless, sizes pyramid
 * 
 * @todo smwquery ?
 * @todo mistakes in ID should be intercepted
 * 
 * api.php?action=iiif-mw-pres&format=json&files=a|b|c
 * api.php?action=iiif-mw-pres&format=json&pageids=
 * //api.php?action=iiif-mw-pres&format=json&smwquery=((Concept:...))
 * new: api.php?action=iiif-mw-pres&format=json&smwquery=<base64url>
 * 
 * Uses Special:IIIFServ/presentation/... as redirect - supports pageids
 * 
 * @note an API that can return multiple thumbnails in a single request would be more helpful
 * https://phabricator.wikimedia.org/T56035
 * 
 */

class IIIFMWPresAPI extends \ApiBase {

	private $metaErrors = [];

	public function execute() {
		$params = $this->extractRequestParams();
		$resourceType = $params["resourcetype"];
		$pageIdStr = $params["pageids"];
		$fileStr = $params["files"];
		$smwquery = $params['smwquery'];

		$repoGeneric = $params['source'] ?? "local";
		$label = $params["label"] ?? "Collection of images";
		$res = $canvases = [];
		$redirectParams = null;

		if ( $resourceType === "manifest" && $pageIdStr !== null ) {
			$redirectParams = "pageids/" . trim($pageIdStr);
			$pageIdArr = explode( ",", trim($pageIdStr) );
			foreach ( $pageIdArr as $id ) {
				$prefixAndIdArr = explode( ":", $id );
				if ( count( $prefixAndIdArr ) < 2 ) {
					// print_r( 'prefixAndIdArr less than two, meaning no prefix<br>' );
					// {pageid} or more rarely, {pageid}-{revid}
					$idArr = explode( "-", $id );
					$repoSource = $repoGeneric;
				} else {
					// print_r( 'prefixAndIdArr two or more, meaning repo prefix<br>' );
					// {repoprefix}:{pageid} or {repoprefix}:{pageid}-{revid}
					$repoSource = $prefixAndIdArr[0];
					$idArr = explode( "-", $prefixAndIdArr[1] );
				}
				$pageid = $idArr[0];
				$revId = ( array_key_exists( 1, $idArr ) ) ? $idArr[1]: null;
				if ( $repoSource === "local" || $repoSource === "" ) {
					$canvasData = $params["version"] === "3"
						? self::buildCanvasV3( $pageid, null, $revId )
						: self::buildCanvasV2( $pageid, null, $revId );
					if ( $canvasData !== null ) {
						$canvases[] = $canvasData;
					} else {
						$this->metaErrors[] = "$pageid is not a valid page ID for a file page";
					}
				} else {
					$canvases[] = IIIFMwRemote::buildCanvasRemotely(
						$pageid,
						null,
						$revId,
						$repoSource,
						$params["version"]
					);
				}
			}
		} elseif ( $resourceType === "manifest" && $fileStr !== null ) {
			$repoSource = $repoGeneric;
			$redirectParams = "files/" . trim($fileStr);
			$fileArr = explode( ";", trim($fileStr) );
			foreach ( $fileArr as $file ) {
				if ( $repoSource === "local" ) {
					$canvases[] = $params["version"] === "3"
						? self::buildCanvasV3( null, $file, null )
						: self::buildCanvasV2( null, $file, null );
				} else {
					$canvases[] = IIIFMwRemote::buildCanvasRemotely(
						null,
						$file,
						null,
						$repoSource,
						$params["version"]
					);
				}
			}
		} elseif ( $resourceType === "manifest" && $smwquery !== null ) {
			$redirectParams = "smwquery/" . trim($smwquery);
			// Currently, v3 only
			$params["version"] = "3";
			$rawQuery = IIIFUtils::base64urlDecode( $smwquery );
			$rawQuery .= "|?Page ID#=pageid|link=none|limit=999";
			$smwQueryRes = IIIFSMW::getQueryResultForQuery( $rawQuery, "" )->toArray();
			$canvases[] = $this->convertSMWQueryResultsToCanvasItems( $smwQueryRes["results"] );
		}

		if ( $resourceType === "manifest" && $redirectParams !== null ) {
			if ( $params["version"] === "2" ) {
				$res = self::buildManifestV2(
					$redirectParams,
					$canvases,
					$repoGeneric ?? $repoSource,
					trim($label)
				);
			} elseif( $params["version"] === "3" ) {
				$res = self::buildManifestV3(
					$redirectParams,
					$canvases,
					$repoGeneric ?? $repoSource,
					trim($label)
				);
			}
		} else {
			// No need for output if resourceType is
			// sequence, canvas or annotation
		}

		if ( !empty( $this->metaErrors ) ) {
			foreach( $this->metaErrors as $comm ) {
				$res["meta"]["error"][] = $comm;
			}
		};

		$apiResult = $this->getResult();
		foreach( $res as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
		//$apiResult->addValue( null, "files", $res );
	}

	/**
	 * Build canvas for IIIF Presentation v2
	 * Local files only. Cf. iiifMwRemote::buildCanvasRemotely()
	 * @return array|null
	 */
	private static function buildCanvasV2(
		$pageid = null,
		$fileName = null,
		$revid = null
	) {
		$baseUrl = IIIFUtils::getUrlBase();
		$IIIFMwImageUtils = new IIIFMwImageUtils();
		// pageid, fileName, sourceWidth, sourceHeight, uploader, mediaType, thumbnailUrl, imageResourceId, smallThumb
		$fileData = $IIIFMwImageUtils->getFileDataForCanvas( $baseUrl, $pageid, $fileName, $revid );
		if ( $fileData === null || empty($fileData) ) {
			return null;
		}
		$canvasMetaData = [];
		$canvasMetaData[] = [
			"label" => "Uploaded by",
			"value" => $fileData["uploader"]
		];

		$canvas = IIIFCanvasParsers::formatCanvasV2(
			$baseUrl,
			"local",
			$fileData["pageid"],
			$fileData["fileName"],
			$canvasMetaData,
			$fileData["sourceWidth"],
			$fileData["sourceHeight"],
			$fileData["imageResourceId"],
			$fileData["smallThumb"],
			$fileData["mediaType"]
		);
		return $canvas;
	}

	/**
	 * Build canvas for IIIF Presentation v3
	 * @return array|null
	 */
	private static function buildCanvasV3( $pageid, $fileName, $revid ) {
		$baseUrl = IIIFUtils::getUrlBase();
		$IIIFMwImageUtils = new IIIFMwImageUtils();
		// pageid, fileName, sourceWidth, sourceHeight, uploader, mediaType, thumbnailUrl, imageResourceId, smallThumb
		$fileData = $IIIFMwImageUtils->getFileDataForCanvas( $baseUrl, $pageid, $fileName, $revid );
		if ( $fileData === null || empty($fileData) ) {
			return null;
		}
		$canvasMetaData = [];
		$canvasMetaData[] = [
			"label" => IIIFParserUtils::formatLabelOrValue( "Uploaded by", "3" ),
			"value" => IIIFParserUtils::formatLabelOrValue( $fileData["uploader"], "3" )
		];

		$canvas = IIIFCanvasParsers::formatCanvasV3(
			$baseUrl,
			"local",
			$fileData["pageid"],
			$fileData["fileName"],
			$canvasMetaData,
			$fileData["sourceWidth"],
			$fileData["sourceHeight"],
			$fileData["imageResourceId"],
			$fileData["smallThumb"],
			$fileData["mediaType"]
		);
		return $canvas;
	}

	/**
	 * Build canvas for IIIF Presentation v2
	 * @param $param
	 * @param $paramType
	 */
	public static function buildManifestV2(
		string $redirectParams,
		array $canvases = [],
		$source = "local",
		$label = "[No label]"
	) {
		$baseUrl = IIIFUtils::getUrlBase();
		$manifestId = $baseUrl . "/Special:IIIFServ/presentation/manifest/{$source}/{$redirectParams}";
			//https://codecs.codicology.eu/Special:IIIFServ/presentation/{repo}/pageids/34234,5656,65656
		$sequenceId = $baseUrl . "/Special:IIIFServ/presentation/sequence/{$source}/normal.json" ;
			// cf. http://34.250.47.171:5000/iiif/sequence/normal.json
		$sequences = [];
		$sequences[0] = [
			"@id" => $sequenceId,
			"@type" => "sc:Sequence",
			"canvases" => $canvases,
			"label" => "default order"
		];
		$res = [
			"@context" => "http://iiif.io/api/presentation/2/context.json",
			"@type" => "sc:Manifest",
			"@id" => $manifestId,
			"label" => $label,
			"description" => "Manifest generated for a selection of MediaWiki files.",
			"sequences" => $sequences
		];
		return $res;
	}

	public static function buildManifestV3(
		string $redirectParams,
		array $canvases = [],
		$source = "local",
		$label = "[No label]"
	) {
		$baseUrl = IIIFUtils::getUrlBase();
		$res = [
			"@context" => "http://iiif.io/api/presentation/3/context.json",
			"type" => "Manifest",
			"id" => $baseUrl . "/Special:IIIFServ/presentation3/manifest/{$source}/{$redirectParams}",
			"label" => IIIFParserUtils::formatLabelOrValue( $label, "3", "none" ),
			// description not in IIIF v3
			// "description" => "Manifest generated from a selection of MediaWiki files",
			"items" => $canvases
		];
		return $res;
	}

	public function getAllowedParams() : array {
		$arr = [
			// identifier, see IIIFSpecialServ
			"id" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"files" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"pageids" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"smwquery"=> [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"smwsort"=> [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"version" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "2"
			],
			"label" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"source" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "local"
			],
			"resourcetype" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "manifest"
			]
		];
		return $arr;
	}

	private function convertSMWQueryResultsToCanvasItems( array $results ) {
		$canvases = [];
		foreach( $results as $pagename => $data ) {
			$fullurl = $data["fullurl"];
			$pageid = $data["printouts"]["pageid"][0];
			
			$canvasData = self::buildCanvasV3( $pageid, null, null );
			if ( $canvasData !== null ) {
				if ( !empty($data["printouts"]["label"]) ) {
					$canvasData["label"]["none"][0] = $data["printouts"]["label"][0];
				}
				// add anything else?
				//$description = $data["printouts"]["description"][0];
				$canvases[] = $canvasData;
			} else {
				$this->metaErrors[] = "$pageid is not a valid page id for a file page";
			}
		}
		return $canvases;
	}
	
}
