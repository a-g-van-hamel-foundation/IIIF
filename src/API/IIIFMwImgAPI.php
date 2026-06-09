<?php

/**
 * IIIF API for creating an Image Information Request (IIR)
 * Returns info.json containing technical metadata about an image
 * 
 * @link https://iiif.io/api/image/2.0/#information-request
 * @link https://iiif.io/api/image/2.1/#image-information
 * @link https://iiif.io/api/image/3.0/#5-image-information
 * 
 * @todo clean up dev notes:
 * Not (.../identifier/info.json) but (/identifier)
 * In support of zoomable IIIF images from MediaWiki files
 * Light-weight alternative to tiles
 * api.php?action=iiif-mw-img&format=json&file=a // filename
 * api.php?action=iiif-mw-img&format=json&pageid=a // pageid
 * api.php?action=iiif-mw-img&format=json&pageid=a&revid=345345
 * https://iiif.io/api/image/2.1/
 * 
 * For local files, we use PHP methods
 * For external files from e.g. Commons, we'll use the API
 * Note: an API that can return multiple thumbnails in a single request may be preferrable
 * https://phabricator.wikimedia.org/T56035
 * 
 * Requires proxy. A special page (Special:.../.../info.json) will be used to redirect to the API
 * The URI for requesting image information must conform to the following URI Template:
 * {scheme}://{server}{/prefix}/{identifier}/info.json
 * http://www.example.org/image-service/abcd1234/info.json
 * Example: https://iiifmediawiki.herokuapp.com/image/a/a0/Gustave_Courbet_-_A_Burial_at_Ornans_-_Google_Art_Project_2.jpg/info.json
 * The API places no restrictions on the form of the identifiers that a server may use or support. All special characters (e.g. ? or #) must be URI encoded to avoid unpredictable client behaviors. The URI syntax relies upon slash (/) separators so any slashes in the identifier must be URI encoded (also called “percent encoded”). See the additional discussion in URI Encoding and Decoding.
 * Rotate? https://doc.wikimedia.org/mediawiki-core/master/php/ApiImageRotate_8php_source.html
 * @link https://www.mediawiki.org/wiki/API:Imageinfo
 * @link https://www.mediawiki.org/wiki/API:Images
 * @link https://www.mediawiki.org/wiki/Extension:PageImages 
 * The PageImages extension provides image information by adding a prop=pageimages to the properties API for action=query. 
 */

namespace IIIF\API;

use MediaWiki\Api\ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use LocalFile;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\RevisionRecord;
use Wikimedia\ParamValidator\ParamValidator;
use IIIF\IIIFUtils;
use IIIF\IIIFMwRemote;

class IIIFMwImgAPI extends ApiBase {

	//THUMB_TEMPLATE = "https://upload.wikimedia.org/wikipedia/commons/thumb/{0}/{1}/{2}/{3}px-{2}"
	//FULL_TEMPLATE = "https://upload.wikimedia.org/wikipedia/commons/{0}/{1}/{2}"

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}
	
	// Wikipedia extracts
	// https://en.wikipedia.org/w/api.php?action=query&format=json&prop=extracts|images&imlimit=max&exintro=&titles=
	// Commons: 
	// https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url|timestamp|user|mime|extmetadata&iiurlwidth={0}&titles={1}
	// 
	public static function gatherData() {
		// maybe get image size and divide by 10?
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$file = $params['file'] ?? "";
		$pageid = $params['pageid'] ?? "";
		// possibly, more complex than just pageid
		$revid = $params['revid'] ?? null;
		$repoName = $params['source'] ?? null;

		if ( trim($pageid) !== "" ) {
			//print_r( '$pageid is not nothing. It is something. It is [' . $pageid . "]" );
			// $repoName can be overridden
			$idtype = "pageid";
			$prefixAndIdArr = explode( ":", $pageid );
			if ( count( $prefixAndIdArr ) < 2 ) {
				$idArr = explode( "-", $pageid ); //pageid
			} else {
				// may override
				$repoName = $prefixAndIdArr[0];
				$idArr = $prefixAndIdArr[1];
			}
			$id = $idArr[0]; //pageid
			$revid = ( array_key_exists( 1, $idArr ) ) ? $idArr[1] : null;
		} else {
			$idtype = "name";
			$id = $file; //file page name
		}

		if ( $repoName == "local" || $repoName == "" ) {
			$fileObj = self::getFile( $id, $idtype, $revid );
			if ( $fileObj == null ) {
				print_r( "No file object..." );
				return;
			}
			$sourceHeight = $fileObj->getHeight();
			$sourceWidth = $fileObj->getWidth();
			// $title = Title::newFromText( $filename, NS_FILE );
			// $url = $file->getFullUrl();
			// delegate to method
			// cf. https://doc.wikimedia.org/mediawiki-core/master/php/ApiQueryImageInfo_8php_source.html
			$IIR = self::buildIIR( $id, $sourceWidth, $sourceHeight, [], "2" ); // IIIF v2
		} else {
			// Remote/API
			if ( $idtype == "name" ) {
				$apiEndpoint = IIIFMwRemote::getApiEndpoint ( $repoName );
				$pageid = IIIFMwRemote::getPageIDFromRemoteAPI( $file, $apiEndpoint );
			}
			$resArr = IIIFMwRemote::getArrFromIIAPI( $repoName, $pageid );
			$fileProps = $resArr['query']['pages'][ intval($pageid) ];
			$sourceWidth = $fileProps['imageinfo'][0]['width'];
			$sourceHeight = $fileProps['imageinfo'][0]['height'];
			$IIR = IIIFMwRemote::buildIIRRemotely( $pageid, $repoName, $sourceWidth, $sourceHeight, "2" );
		}

		$res = $IIR;
		
		$apiResult = $this->getResult();
		foreach( $res as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
		//foreach( $newManifestArr as $key => $val ) {
		//	$apiResult->addValue( null, $key, $val );
		//}

	}

	/**
	 * Get file object based on either name, page ID or revision ID
	 */
	public static function getFile( $id = null, $idtype = "name", $revid = null ) {
		$services = MediaWikiServices::getInstance();
		$localRepo = $services->getRepoGroup()->getLocalRepo();
		//print_r( $idtype . "<br>" );
		if ( $idtype == "name" ) {
			$filename = ( substr( $id, 0, 5 ) === "File:" ) ? $id : "File:" . $id;
			$titleObj = Title::newFromText( $filename ); // ?$filename, NS_FILE
			$file = $localRepo->findFile( $titleObj ); // File instance
		} elseif ( $idtype == "pageid" ) {
			$titleObj = Title::newFromID( $id, 0 ); // instance of Title
			$file = $localRepo->findFile( $titleObj );
		} elseif ( $idtype == 'revisionid' ) {
			// Not possible to get page/file through revision only
			// SELECT * FROM `revision` WHERE `rev_id` = 24230
			// look up rev_page
			//not sure
			// $id = 24230;
			// getRevisionById is not static
			$revisionRecord = RevisionStore::getRevisionById( $id, 0, null );
			$pageTitle = $revisionRecord->getPageAsLinkTarget(); // LinkTarget
			$page = $revisionRecord->getPage(); // PageIdentity
			$file = $localRepo->findFile( $pageTitle );
		} else {
			return;
		}
		if  ( $file == false ) {
			return;
		}
		// Get file associated with revision instead
		if ( $revid !== null ) {
			$oldfiles = $file->getHistory( 1, $revid, $revid );
			$file = $oldfiles[0];
		}
		return $file;
	}

	/**
	 * @return array
	 */
  	public function getAllowedParams() : array {
		$arr = [
			"file" => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"pageid" => [
				ParamValidator::PARAM_TYPE => 'string', // integer
				ParamValidator::PARAM_REQUIRED => false
			],
			"revid" => [
				ParamValidator::PARAM_TYPE => 'string', // integer
				ParamValidator::PARAM_REQUIRED => false
			],
			"source" => [
				ParamValidator::PARAM_TYPE => 'string', // integer
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 'local'
			]
		];
		return $arr;
	}

	/**
	 * Build Image Information Request (IIR), level 0, tile-less
	 * ~ '.../info.json'
	 */
	public static function buildIIR(
		string $id,
		int $sourceWidth,
		int $sourceHeight,
		array $sizes = [],
		string $version = "2"
	): array {
		// Not currently using api URL but special page below
		// $currentId = IIIFUtils::getUrlBase() . "$_SERVER[REQUEST_URI]";
		// @todo create version for UI
		// @todo in case of file names, convert to page ids

		$redirectId = IIIFUtils::getFullURLForPage( "Special:IIIFServ/image/local/{$id}" );

		$res = $scaleArr = [];

		// @todo - Issue here is that aspect ratio is not consistent 
		// when calculating and rounding height
		$widths = IIIFUtils::getThumbnailWidths();
		foreach( $widths as $thumbWidth ) {
			if ( $thumbWidth >= $sourceWidth ) {
				$scaleArr[] = [
					"width" => $sourceWidth,
					"height" => $sourceHeight
				];
				break;
			}
			$thumbHeight = LocalFile::scaleHeight( $sourceWidth, $sourceHeight, $thumbWidth );
			$scaleArr[] = [
				"width" => $thumbWidth,
				"height" => $thumbHeight
			];
		}

		if ( $version === "2" ) {
			$service = [
				"@context" => "http://iiif.io/api/image/2/context.json",
				"type" => "ImageService2",
				"@id" => $redirectId,
				"profile" => [
					"http://iiif.io/api/image/2/level0.json"
				],
				"protocol" => "http://iiif.io/api/image",
				"height" => $sourceHeight,
				"width" => $sourceWidth,
				"sizes" => $scaleArr
			];
		} elseif ( $version === "3" ) {
			// @todo check
			$service = [
				"@context" => "http://iiif.io/api/image/3/context.json",
				"type" => "ImageService3",
				// "id" => $currentId,
				"id" => $redirectId,
				"profile" => "level0",
				"protocol" => "http://iiif.io/api/image",
				"height" => $sourceHeight,
				"width" => $sourceWidth,
				"sizes" => $scaleArr
			];
		}
		return $service;
	}

	/**
	 * A version of scaleHeight (File.php) that doesn't round numbers
	 */
	public static function scaleHeight( $sourceWidth, $sourceHeight, $destWidth ) {
		if ( $sourceWidth == 0 ) {
			return 0;
		} else {
			return (int)( $sourceHeight * $destWidth / $sourceWidth );
		}
	}

}
