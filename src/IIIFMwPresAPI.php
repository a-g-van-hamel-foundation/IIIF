<?php

use MediaWiki\MediaWikiServices;
//use MediaWiki\Revision\RevisionStore;
//use MediaWiki\Revision\RevisionRecord;
//use SMW\DIWikiPage;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Creates IIIF Presentation API using MediaWiki file system
 * Supports image files from local MediaWiki repository
 * API-based support for external MediaWiki repos incl. Wikimedia Commons
 * Image Service: Level 0, tileless, sizes pyramid
 * 
 * @todo smwquery
 * @todo IIIF v3
 * @todo mistakes in ID should be intercepted
 * 
 * api.php?action=iiif-mw-pres&format=json&files=a|b|c
 * api.php?action=iiif-mw-pres&format=json&pageids=
 * api.php?action=iiif-mw-pres&format=json&smwquery=((Concept:...))
 * 
 * Uses Special:IIIFServ/presentation/... as redirect - supports pageids
 * 
 * @note an API that can return multiple thumbnails in a single request would be more helpful
 * https://phabricator.wikimedia.org/T56035
 * 
 */

class IIIFMWPresAPI extends ApiBase {

	public function execute() {
		$params = $this->extractRequestParams();
		$label = ( array_key_exists( 'label', $params ) ) ? trim($params['label']) : "";
		$pageIdStr = ( array_key_exists( 'pageids', $params ) ) ? trim($params['pageids']) : "";
		$fileStr = ( array_key_exists( 'files', $params ) ) ? trim($params['files']) : "";
		$smwquery = ( array_key_exists( 'smwquery', $params ) ) ? trim($params['smwquery']) : "";
		$repoGeneric = ( array_key_exists( 'source', $params ) ) ? trim($params['source']) : "local";
		//
		$res = $canvases = [];
		$redirectParams = null;

		if ( $pageIdStr !== "" ) {
			$redirectParams = "pageids/$pageIdStr";
			$pageIdArr = explode( ",", $pageIdStr );
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
				// print_r( '<br>Incoming, repo: ' . $repoSource );
				if ( $repoSource == "local" || $repoSource == "" ) {
					$canvases[] = self::buildCanvasV2( $pageid, null, $revId );
				} else {
					// remote/API temp disabled
					$canvases[] = IIIFMwRemote::buildCanvasRemotelyV2( $pageid, null, $revId, $repoSource );
				}
			}
		} elseif ( $fileStr !== "" ) {
			$repoSource = $repoGeneric;
			$redirectParams = "files/$fileStr";
			$fileArr = explode( ";", $fileStr );
			foreach ( $fileArr as $file ) {
				if ( $repoSource == "local" ) {
					$canvases[] = self::buildCanvasV2( null, $file, null );
				} else {
					$canvases[] = IIIFMwRemote::buildCanvasRemotelyV2( null, $file, null, $repoSource );
				}
			}
		}
		if ( $redirectParams !== null ) {
			$res = self::buildManifestV2( $redirectParams, $canvases, $repoSource, $label );
		}

		$apiResult = $this->getResult();
		foreach( $res as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
		//$apiResult->addValue( null, "files", $res );
	}

	public function getAllowedParams() : array {
		$arr = [
			"files" => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"pageids" => [
				ParamValidator::PARAM_TYPE     => 'string', // integer
				ParamValidator::PARAM_REQUIRED => false
			],
			"smwquery"=> [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"smwsort"=> [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"version"=> [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"label"=> [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"source" => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT	=> 'local'
			]
		];
		return $arr;
	}

	/**
	 * Build canvas for IIIF Presentation v2
	 * Local files only. Cf. iiifMwRemote::buildCanvasRemotelyV2()
	 */
	public static function buildCanvasV2( 
		$pageid = null,
		$fileName = null,
		$revid = null
	) {
		$baseUrl = IIIFUtils::getUrlBase();
		// @todo: allow for combinations of pageids and revision ids, e.g. "234234-697372"
		//$services = MediaWikiServices::getInstance();
		$localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
		if ( $pageid !== null ) {
			$titleObj = \Title::newFromID( $pageid, 0 );
			$fileName = $titleObj->getText();
		} elseif ( $fileName !== null ) {
			$titleObj = \Title::newFromText( $fileName, NS_FILE );
			$pageid = $titleObj->getId();
		}
		$fileUrl = $titleObj->getFullURL(); // maybe
		$fileObj = $localRepo->findFile( $titleObj );
		//$fileObj = IIIFMwImgAPI::getFile( $pageid, "pageid", $revid );
		// $titleObj = \Title::newFromID( $pageid, 0 );
		if ( $fileObj === false ) {
			print_r( "No file object.<br>" );
			return [];
		}
		// $id
		$sourceHeight = $fileObj->getHeight();
		$sourceWidth = $fileObj->getWidth();
		$uploader = $fileObj->getUploader();
		$mediaType = $fileObj->getMediaType(); // e.g. "image/jpeg"
		$mimeType = $fileObj->getMimeType();
		$metadataArr = $fileObj->getMetaDataArray();

		$thumbnailUrl = $fileObj->createThumb( $sourceWidth, $sourceHeight  ); // largest thumbnail URL
		$imageResourceId = $baseUrl . $thumbnailUrl;
			// cf. "https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Autorretrato_Goya_1815.jpg/1600px-Autorretrato_Goya_1815.jpg"
		$smallThumb = $baseUrl . $fileObj->createThumb( 120 );
			// cf. "https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Autorretrato_Goya_1815.jpg/100px-Autorretrato_Goya_1815.jpg",

		$canvasId = $baseUrl . "/Special:IIIFServ/presentation/canvas/local/" . $pageid . ".json";
			// cf. http://34.250.47.171:5000/iiif/canvas/c3240093.json
		$imageAnnotationId = $baseUrl . "/Special:IIIFServ/presentation/annotation/local/" . $pageid . ".json";
			// cf. "http://34.250.47.171:5000/iiif/annotation/a3240093.json"

		$canvasMetadata = [];
		$canvasMetadata[] = [
			"label" => "Uploaded by",
			"value" => $uploader
		];
		/*
		foreach( $metadataArr as $k => $v ) {
			$canvasMetadata[] = [
				"label" => $k,
				"value" => $v
			];
		}
		*/
		// Using a fictitious example
		$service = IIIFMwImgAPI::buildIIR( $pageid, $sourceWidth, $sourceHeight, [], "2" );		
		$imageResource = [
			"@type" => "dctypes:Image",
			"@id" => $imageResourceId,
			"height" => $sourceHeight,
			"width" => $sourceWidth,
			"service" => $service
		];
		$images = [];
		$images[] = [
			"@id" => $imageAnnotationId,
			"@type" => "oa:Annotation",
			"motivation" => "sc:painting",
			"on" => $canvasId,
			"resource" => $imageResource
		];
		$thumbnail = [
			"@id" => $smallThumb,
			"@type" => "dctypes:Image",
			"format" => $mediaType,
			"width" => 120,
			"height" => \LocalFile::scaleHeight( $sourceWidth, $sourceHeight, 120 )
		];
		$canvas = [
			"@type" => "sc:Canvas",
			"@id" => $canvasId,
			"label" => $fileName,
            "height" => $sourceHeight,
			"width" => $sourceWidth,
			"images" => $images,
			"metadata" => $canvasMetadata,
			"thumbnail" => $thumbnail
		];
		return $canvas;
	}

	/**
	 * Build canvas for IIIF Presentation v3
	 */
	public static function buildCanvasV3( $pageid ) {
		//
	}

	/**
	 * Build canvas for IIIF Presentation v2
	 * @param $param
	 * @param $paramType
	 */
	public static function buildManifestV2(
		string $redirectParams,
		array $canvases = [],
		$source = 'local',
		$label = "[No label]"
	) {
		$baseUrl = IIIFUtils::getUrlBase();
		$manifestId = $baseUrl . "/Special:IIIFServ/presentation/{$source}/{$redirectParams}";
			//https://codecs.codicology.eu/Special:IIIFServ/presentation/{repo}/pageids/34234,5656,65656
		$sequenceId = $baseUrl . "/Special:IIIFServ/presentation/{$source}/sequence/normal.json" ;
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
			"description" => "",
			"label" => $label,
			"sequences" => $sequences
		];
		return $res;
	}

	public static function buildManifestV3( array $canvases = [] ) {
		// @todo
	}

	
}
