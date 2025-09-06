<?php

/**
 * Methods for working with remote MediaWiki APIs
 * Wikimedia Commons and external APIs set through $wgForeignFileRepos
 * @link https://www.mediawiki.org/wiki/Manual:$wgForeignFileRepos
 * @link https://www.mediawiki.org/wiki/API:Imageinfo
 * 
 * @dev: imageinfo cannot be relied on for exact thumbnail sizes:
 * e.g. https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url&pageids=1230085&iiurlwidth=600 returns a 960px thumbnail so use this instead
 * https://upload.wikimedia.org/wikipedia/commons/thumb/1/1f/Tree_frog_Fern_Forest.jpg/600px-Tree_frog_Fern_Forest.jpg
 */

use MediaWiki\MediaWikiServices;
//use LocalFile;
use IIIFUtils;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIF\IIIFParsers\IIIFMwImageUtils;

class IIIFMwRemote {

	/**
	 * Get image info from MediaWiki API
	 * @return array
	 */
	public static function getArrFromIIAPI(
		$source = 'commons',
		$pageids = null,
		$width = null
	) {
		$api = self::getApiEndpoint( $source );
		$apiBaseUrl = $api . "?";
		$titlesParam = $pageidsParam = "";
		// Maybe replace delimiters
		if ( $pageids !== null ) {
			$pageidsParam = $pageids;
		}
		$qArray = [
			"action" => "query",
			"format" => "json",
			"prop" => "imageinfo",
			"iiprop" => "url|size|timestamp|user|mime|extmetadata",
			"pageids" => $pageidsParam
			//"iiurlwidth" => $width
		];
		$qParams = http_build_query( $qArray );
		$url = $apiBaseUrl . $qParams;
		// cURL session
		$arr = IIIFUtils::getArrayFromJsonUrl( $url, true );
		if ( gettype($arr) !== "array" ) {
			return [];
		}
		return $arr;
	}

	/**
	 * Build canvas for IIIF Presentation V2/V3,
	 * retrieving data from remote wiki.
	 * @param mixed $pageid - page ID if fileName is not provided
	 * @param string|null $fileName - file name if pageid is not provided
	 * @param mixed $revid - revision ID, not currently used
	 * @param string $reposource - "commons" or resource identifier set in config
	 * @param string $apiVersion - Presentation API version, "2" or "3"
	 * @return array
	 */
	public static function buildCanvasRemotely(
		$pageid = null,
		$fileName = null,
		$revid = null,
		$reposource = "commons",
		$apiVersion = "2"
	) {
		// First, make the request
		if ( $pageid == null && $fileName !== null ) {
			$apiEndpoint = self::getApiEndpoint( $reposource );
			$pageid = self::getPageIDFromRemoteAPI( $fileName, $apiEndpoint );
			if ( $pageid === null ) {
				return [];
			}
		}

		$resArr = self::getArrFromIIAPI( $reposource, $pageid );
		$fileProps = IIIFUtils::getArrayPath( ['query', 'pages', intval($pageid) ], $resArr, null );
		if ( $fileProps === null || !array_key_exists( "imageinfo", $fileProps ) ) {
			return [];
		}

		$baseLocalUrl = IIIFUtils::getUrlBase();
		$fileTitle = $fileProps['title'];
		$sourceWidth = $fileProps['imageinfo'][0]['width'];
		$sourceHeight = $fileProps['imageinfo'][0]['height'];
		$mimeType = $fileProps['imageinfo'][0]['mime'];
		$canvasMetadata = self::buildMetadataFromFileProps( $fileProps, $apiVersion );

		// Get largestThumbnailUrl as imageResourceId
		$imageResourceId = self::getThumbnailRemotely( $reposource, $pageid, $sourceWidth, true );
		$smallThumb = self::getThumbnailRemotely( $reposource, $pageid, 120, false );

		// Second, format the requests in the appropriate format V2 or V3
		if ( $apiVersion == "2" ) {
			$canvas = IIIFCanvasParsers::formatCanvasV2(
				$baseLocalUrl,
				$reposource,
				$pageid,
				$fileTitle,
				$canvasMetadata,
				$sourceWidth,
				$sourceHeight,
				$imageResourceId,
				$smallThumb,
				$mimeType
			);
		} elseif( $apiVersion == "3" ) {
			$canvas = IIIFCanvasParsers::formatCanvasV3(
				$baseLocalUrl,
				$reposource,
				$pageid,
				$fileTitle,
				$canvasMetadata,
				$sourceWidth,
				$sourceHeight,
				$imageResourceId,
				$smallThumb,
				$mimeType
			);	
		}
		return $canvas;
	}

	/**
	 * Format metadata retrieved from file properties
	 * @todo Maybe move - this is largely a formatting thing
	 */
	private static function buildMetadataFromFileProps( $fileProps, $apiVersion = "2" ) {		
		$canvasMetadata = [];
		$fileTitle = $fileProps['title'];
		$filesourceurl = $fileProps['imageinfo'][0]['descriptionurl'];
		$canvasMetadata[] = [
			"label" => IIIFParserUtils::formatLabelOrValue( "Source", $apiVersion ),
			"value" => IIIFParserUtils::formatLabelOrValue( "<a href='$filesourceurl'>$fileTitle</a>", $apiVersion )
		];
		$canvasMetadata[] = [
			"label" => IIIFParserUtils::formatLabelOrValue( "Uploaded by", $apiVersion ),
			"value" => IIIFParserUtils::formatLabelOrValue( $fileProps['imageinfo'][0]['user'], $apiVersion )
		];
		$extMetaData = $fileProps['imageinfo'][0]['extmetadata'];
		foreach ( $extMetaData as $k=>$data ) {
			$canvasMetadata[] = [
				"label" => IIIFParserUtils::formatLabelOrValue( $k, $apiVersion ),
				"value" => IIIFParserUtils::formatLabelOrValue( strval( $data['value'] ), $apiVersion )
			];
		}
		return $canvasMetadata;
	}

	/**
	 * API-base variant of IIIFMwImgAPI::buildIIR()
	 * Build Image Information Request (IIR), level 0, tile-less
	 */
	public static function buildIIRRemotely(
		string $pageid,
		string $repoName,
		int $sourceWidth,
		int $sourceHeight,
		string $version = "2"
	): array {
		$baseUrl = IIIFUtils::getUrlBase();
		$redirectId = "{$baseUrl}/Special:IIIFServ/image/{$repoName}/{$pageid}";
		$scaleArr = IIIFMwImageUtils::calculateLevel0SizesFixed( $sourceWidth, $sourceHeight );
		$service = IIIFCanvasParsers::formatImageService( $version, $redirectId, $sourceWidth, $sourceHeight, $scaleArr );
		return $service;
	}

	/**
	 * Get image from Information Image Request remotely
	 * e.g. Special:IIIFServ/image/local/18814/full/1024,270/0/default.jpg
	 */
	public static function getIIRImageRemotely( $urlParts ) {
		$repo = $urlParts[1];
		$pageid = $urlParts[2];
		$sizes = explode( ",", $urlParts[4] );
		$width = $sizes[0];
		$res = self::getThumbnailRemotely( $repo, $pageid, $width );
		return $res;
	}

	/**
	 * Get thumbnail image through the imageinfo API
	 * and modify the URL to get the exact size required.
	 * @param string $repo - 
	 * @param mixed $pageid - page id of the file page
	 * @param mixed $width - requested width
	 * @param boolean $isFullWidth - whether $width equals the full image width. Currently ignored because hopefully, the check on responsiveUrls is sufficient and we may not know in advance anyway.
	 */
	private static function getThumbnailRemotely(
		string $repo,
		mixed $pageid,
		mixed $width,
		bool $isfullWidth = false
	): string {
		// get endpoint e.g. "https://commons.wikimedia.org/w/api.php"
		$api = self::getApiEndpoint( $repo );
		$qArray = [
			"action" => "query",
			"format" => "json",
			"prop" => "imageinfo",
			// options: "url|size|timestamp|user|mime"
			"iiprop" => "url|size",
			"pageids" => $pageid,
			"iiurlwidth" => $width
		];
		$qParams = http_build_query( $qArray );
		$url = $api . "?" . $qParams;

		// Get data from cURL session
		$resArr = IIIFUtils::getArrayFromJsonUrl( $url );
		$fileProps = IIIFUtils::getArrayPath( [ 'query', 'pages', intval($pageid) ], $resArr, [] );
		$thumb = IIIFUtils::getArrayPath( [ 'imageinfo', 0, 'thumburl' ], $fileProps, null );

		if ( $thumb !== null && $thumb !== false ) {
			// First check if we have the full image or a thumb
			if( $width === IIIFUtils::getArrayPath( [ 'imageinfo', 0, 'width'], $fileProps ) ) {
				return $thumb;
			}
			// Requested thumb. May not match the requested width
			// so we deconstruct it and build a new one.
			// structure: {repoUrlBase}/thumb/{zonePrefix}/{archive or temp or sha1}/{fileName}/
			// e.g. https://upload.wikimedia.org/wikipedia/commons/thumb/1/1f/Tree_frog_Fern_Forest.jpg/800px-Tree_frog_Fern_Forest.jpg
			$revUrlParts = array_reverse( explode( "/", $thumb ) );
			$fileName = $revUrlParts[1];
			$archiveName = $revUrlParts[2];
			$zonePrefix = $revUrlParts[3];
			array_splice($revUrlParts, 0, 5 );
			$repoUrlBase = implode( "/", array_reverse( $revUrlParts ) );
			$thumbUrl = self::createThumbnailUrl(
				$repoUrlBase,
				$zonePrefix,
				$archiveName,
				$fileName,
				$width
			);
			return $thumbUrl;
		} else {
			 // @todo ?
			return "";
		}
	}

	/**
	 * Template/Helper for getThumbnailRemotely()
	 * @return string
	 */
	private static function createThumbnailUrl(
		string $repoUrlBase,
		string $zonePrefix,
		string $archiveName,
		string $filename,
		int $width
	) {
		return "$repoUrlBase/thumb/$zonePrefix/$archiveName/$filename/{$width}px-{$filename}";
	}

	/**
	 * Get Page ID from remote API endpoint.
	 * @return null|
	 */
	public static function getPageIDFromRemoteAPI( string $page, string $apiEndpoint ) {
		$pageID = null;
		$q = [
			"action" => "query",
			"format" => "json",
			"prop" => "pageprops",
			"titles" => $page
		];
		$qParams = http_build_query( $q );
		$apiUrl = $apiEndpoint . "?" . $qParams;
		$arr = IIIFUtils::getArrayFromJsonUrl( $apiUrl );
		if ( $arr == null ) {
			print_r( "No page ID found from " . $apiUrl );
			return null;
		}
		$pageProps = IIIFUtils::getArrayPath( ['query', 'pages'], $arr );
		// array uses pageid not sequential number
		if ( $pageProps === null ) {
			return null;
		}
		$firstKey = array_key_first( $pageProps );
		$pageID = IIIFUtils::getArrayPath( [ $firstKey, 'pageid' ], $pageProps );
		return $pageID;
	}

	/**
	 * Get API endpoint identified by shorthand
	 * @param string $repoSource
	 */
	public static function getApiEndpoint( string $repoSource ) {
		$api = "";
		if ( $repoSource == "commons" || $repoSource == "" ) {
			$api = "https://commons.wikimedia.org/w/api.php";
		} else {
			$foreignFileRepos = MediaWikiServices::getInstance()->getMainConfig()->get( 'ForeignFileRepos' );
			foreach( $foreignFileRepos as $repo ) {
				if ( $repo["name"] === $repoSource ) {
					// only if 'class' = ForeignAPIRepo::class,
					$api = IIIFUtils::getArrayPath( ["apibase"], $repo, "" );
				}
			}
		}
		return $api;
	}

	/**
	 * @deprecated use buildCanvasRemotely
	 */
	public static function buildCanvasRemotelyV2( 
		$pageid = null,
		$fileName = null,
		$revid = null,
		$reposource = 'commons'
	) {
		return self::buildCanvasRemotely( $pageid, $fileName, $revid, $reposource, "2" );
	}

}
