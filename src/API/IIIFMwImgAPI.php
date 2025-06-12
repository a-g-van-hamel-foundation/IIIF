<?php

/**
 * IIIF API for creating image information request
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

use MediaWiki\MediaWikiServices;
use Title;
use LocalFile;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\RevisionRecord;
use Wikimedia\ParamValidator\ParamValidator;
use IIIFUtils;
use IIIFMwRemote;

class IIIFMwImgAPI extends \ApiBase {

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
			$revisionRecord = \RevisionStore::getRevisionById( $id, 0, null );
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
	 * Not for production use.
	 */
	private static function testDoSomethingWithFile( $file ) {
		$baseUrl = IIIFUtils::getUrlBase();
		$height = $file->getHeight();
		$width = $file->getWidth();
		// $file->getDescriptionShortUrl() e.g. /index.php?curid=5573"
		$fileUrl = $baseUrl . $file->getURL(); // /images/..
		$viewUrl = $baseUrl . $file->getViewURL();
		$thumbUrl = $file->getThumbUrl();
		$uploader = $file->getUploader();

		$package = [
			"url" => $baseUrl . $file->getURL()
		];
		// thumbnails
		//$sizes = [ 320, 640, 800, 1024, 1280, 2560 ];
		$sizes = [ 320, 640, 800, 1024, 1280, 2560 ];

		$thumbnailUrls = $scaleArr = [];
		foreach( $sizes as $w ) {
			// $scaleArr[] = LocalFile::scaleheight( $width, $height, $w );
			$thumbnailUrls[] = $baseUrl . self::createThumbnail( $file, $w );
		}
		// getMimeType() and getMediaType()
		// get thumbnail URL
		return $thumbnailUrls;
	}

	/**
	 * @todo check if we can make the requested thumbnail, and get transform parameters.
	 * // cf API: https://phabricator.wikimedia.org/source/mediawiki/browse/master/includes/api/ApiQueryImageInfo.php
	 * @param File $img
	 */
	private static function createThumbnail( File $img, $width ) { 
		// $finalThumbParams = $this->mergeThumbParams( $img, $scale, $params['urlparam'] );
		$thumb = $img->createThumb( $width );
		return $thumb;
	}

	private static function testCollectFunctionsIMightNeed( string $imageName ) {
		// $finalThumbParams = $this->mergeThumbParams( $img, $scale, $params['urlparam'] );
		$services = MediaWikiServices::getInstance();
		$localRepo = $services->getRepoGroup()->getLocalRepo();
		$imageName = '';
		$image = $localRepo->newFile( $imageName );
		// $image->exists()
		$imageLimits = $services->getMainConfig()->get( 'ImageLimits' );
		// $galleryOptions = $services->getMainConfig()->get( 'GalleryOptions' );
		// $galleryOptions['imageWidth'], galleryOptions['imageHeight']

		// File.php - https://doc.wikimedia.org/mediawiki-core/master/php/classFile.html
		// $image ->transform( $params, File::RENDER_NOW );
		$thumbPath = $image->getThumbPath(); //  thumbnail path
		$thumbUrl = $image->getThumbUrl(); //  thumbnail directory

		$thumbnailParams = [
			"physicalWidth"=> ""
		];
		$thumbnailSource = $image->getThumbnailSource( $thumbnailParams );
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
	 * Get data from requests to external APIs, e.g. Commons
	 * Avoid for internal PHP uses (https://www.mediawiki.org/wiki/API:Calling_internally)
	 */
	public static function getResultFromApiRequest( $endPoint, $titles, $width ) {
		// https://en.wikipedia.org/w/api.php?action=query&format=json&prop=imageinfo&titles=File:Mapofkanto.png&iiprop=url|timestamp|user|mime|extmetadata&iiurlwidth=100
		// https://codecs.vanhamel.nl/api.php?action=query&format=json&prop=imageinfo&titles=File:1-AG-van-Hamel.jpg&iiprop=url|timestamp|user|mime|extmetadata&iiurlwidth=200
		//$apiUrlStr = `/api.php?action=query&format=json&prop={$prop}&titles={$titles}&iiprop=`;
		$prop = 'imageinfo';
		$titles = 'File:1-AG-van-Hamel.jpg'; //test
		$baseUrlStr = IIIFUtils::getUrlBase();
		$endPoint = $baseUrlStr . "api.php";
		$iiprop = "url|size|timestamp|user|mime|extmetadata";
		$iiurlwidth= "200";
		
		$qParams = [
			"action" => "query",
			"format" => "json",
			"prop" => $prop,
			"titles" => $titles,
			"iiprop" => $iiprop,
			"iiurlwidth" => $iiurlwidth // or ...height?
		];
		$qUrl = $endPoint . "?" . http_build_query( $qParams );

		// ? 
		$ch = curl_init( $qUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close( $ch );

		$res = json_decode( $output, true );
		return $res;
		/*
		foreach( $res["query"]["pages"] as $k => $v ) {
			echo( $v["title"] . " is uploaded by User:" . $v["imageinfo"][0]["user"] . "\n" );
		}
		*/
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
		$baseUrl = IIIFUtils::getUrlBase();
		$currentId = $baseUrl . "$_SERVER[REQUEST_URI]";
		// @todo create version for UI
		// $id = $id;
		// @todo in case of file names, convert to page ids
		$redirectId = $baseUrl . "/Special:IIIFServ/image/local/" . $id;
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
