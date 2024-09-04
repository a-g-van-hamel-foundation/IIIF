<?php

use MediaWiki\MediaWikiServices;

/**
 * Methods for working with remote MediaWiki APIs
 * Currently tested with Wikimedia Commons
 * 
 * Supports external APIs through $wgForeignFileRepos
 * @link https://www.mediawiki.org/wiki/Manual:$wgForeignFileRepos
 * 
 * Avenues:
 * Wikimedia Commons interwiki links: easier to derive full URLs but not thumbnails
 * InstantCommons: if enabled, can be used to get thumbnails
 * maybe this helps: https://github.com/wikimedia/mediawiki-api-demos/blob/master/php/edit.php
 * https://commons.wikimedia.org/w/api.php
 * 
 * Example of Wikimedia API query:
 * @example https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url|timestamp|user|mime|extmetadata&&iiurlwidth=300titles=
 * @example https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url|timestamp|user|mime|extmetadata&&iiurlwidth=300&titles=File:D%C3%BClmen,%20Kirchspiel,%20Dernekamp,%20B%C3%A4ume%20--%202021%20--%209397.jpg
 * 
 */

class IIIFMwRemote {

	/**
	 * Get array from MediaWiki API for image info
	 */
	public static function getArrFromIIAPI(
		$source = 'commons',
		$pageids = null,
		$width = null
	) {
		// Documentation: imageinfo https://www.mediawiki.org/wiki/API:Imageinfo
		$api = self::getApiEndpoint( $source ); //"https://commons.wikimedia.org/w/api.php",
		$apiBaseUrl = $api . "?";
		$titlesParam = $pageidsParam = "";
		// maybe replace delimiters
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
		$arr = IIIFUtils::getArrayFromJsonUrl( $url );		
		return $arr;
	}

	/*
	 * Build canvas for IIIF Presentation v2
	 * Variant of buildCanvasV2 retrieving data from remote url
	 */
	public static function buildCanvasRemotelyV2( 
		$pageid = null,
		$fileName = null,
		$revid = null,
		$reposource = 'commons'
	) {
		if ( $pageid == null && $fileName !== null ) {
			$apiEndpoint = self::getApiEndpoint( $reposource );
			$pageid = self::getPageIDFromRemoteAPI( $fileName, $apiEndpoint );
			if ( $pageid == null ) {
				return;
			}
		}

		$resArr = self::getArrFromIIAPI( $reposource, $pageid );
		// IIIFUtils::printArray( $resArr );

		$fileProps = $resArr['query']['pages'][ intval($pageid) ];
		$filePropsImageinfo = $fileProps['imageinfo']; //arr

		$baseLocalUrl = IIIFUtils::getUrlBase();
		$canvasId = "{$baseLocalUrl}/Special:IIIFServ/presentation/canvas/{$reposource}/{$pageid}.json";
		
		$sourceWidth = $fileProps['imageinfo'][0]['width'];
		$sourceHeight = $fileProps['imageinfo'][0]['height'];
		$mimeType = $fileProps['imageinfo'][0]['mime'];

		$canvasMetadata = [];
		$fileTitle = $fileProps['title'];
		$filesourceurl = $fileProps['imageinfo'][0]['descriptionurl'];
		$canvasMetadata[] = [ "label" => "Source", "value" => "<a href='$filesourceurl'>$fileTitle</a>" ];
		$canvasMetadata[] = [ "label" => "Uploaded by", "value" => $fileProps['imageinfo'][0]['user'] ];
		$extMetaData = $fileProps['imageinfo'][0]['extmetadata'];
		foreach ( $extMetaData as $k=>$data ) {
			$canvasMetadata[] = [
				"label" => $k,
				"value" => $data['value']
			];
		}	
		//$thumbnailUrl = $fileObj->createThumb( $sourceWidth, $sourceHeight  ); // largest thumbnail URL
		//$imageResourceId = $baseUrl . $thumbnailUrl;
		$imageAnnotationId = [];
		$imageResourceId = "";

		$service = self::buildIIRRemotely( $pageid, $reposource, $sourceWidth, $sourceHeight, "2" );
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
		$smallThumb = self::getThumbnailRemotely( $reposource, $pageid, 120 );
		$thumbnail = [
			"@id" => $smallThumb,
			"@type" => "dctypes:Image",
			"format" => $mimeType,
			"width" => 120,
			"height" => \LocalFile::scaleHeight( $sourceWidth, $sourceHeight, 120 )
		];
		// thumburl

		$canvas = [
			"@type" => "sc:Canvas",
			"@id" => $canvasId,
			"label" => str_replace( "File:", "", $fileTitle ),
            "height" => $sourceHeight,
			"width" => $sourceWidth,
			"images" => $images,
			"metadata" => $canvasMetadata,
			"thumbnail" => $thumbnail
		];
		return $canvas;
	}

	/**
	 * API-base variant of IIIFMwImgAPI::buildIIR()
	 * Build Image Information Request (IIR), level 0, tile-less
	 * 
	 */
	public static function buildIIRRemotely(
		string $pageid,
		string $repoName,
		int $sourceWidth,
		int $sourceHeight,
		string $version = "2"
	): array {
		// print_r( 'Incoming, repo: ' . $repoName );
		$baseUrl = IIIFUtils::getUrlBase();
		$id = $pageid;
		$redirectId = "{$baseUrl}/Special:IIIFServ/image/{$repoName}/{$id}";

		// @todo
		$scaleArr = [];
		$widths = IIIFUtils::getThumbnailWidths();
		foreach( $widths as $thumbWidth ) {
			if ( $thumbWidth >= $sourceWidth ) {
				$scaleArr[] = [
					"width" => $sourceWidth,
					"height" => $sourceHeight
				];
				break;
			}
			$thumbHeight = \LocalFile::scaleHeight( $sourceWidth, $sourceHeight, $thumbWidth );
			$scaleArr[] = [
				"width" => $thumbWidth,
				"height" => $thumbHeight
			];
		}

		$service = [];
		if ( $version = "2" ) {
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
		} elseif ( $version = "3" ) {
			// @todo
		}
		return $service;

	}

	/**
	 * image/local/
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
	 * 
	 */
	private static function getThumbnailRemotely( $repo, $pageid, $width ): string {
		$api = self::getApiEndpoint( $repo ); //"https://commons.wikimedia.org/w/api.php"
		$qArray = [
			"action" => "query",
			"format" => "json",
			"prop" => "imageinfo",
			//"iiprop" => "url|size|timestamp|user|mime",
			"iiprop" => "url",
			"pageids" => $pageid,
			"iiurlwidth" => $width
		];
		$qParams = http_build_query( $qArray );
		$url = $api . "?" . $qParams;
		// cURL session
		$resArr = IIIFUtils::getArrayFromJsonUrl( $url );
		$fileProps = $resArr['query']['pages'][ intval($pageid) ];
		$thumb = $fileProps['imageinfo'][0]['thumburl'];
		if ( $thumb !== null || $thumb !== false ) {
			return $thumb;
		} else {
			return ""; // @todo ?
		}
	}


	/**
	 * 
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
		$pageProps = $arr['query']['pages']; // array uses pageid not sequential number
		$firstKey = array_key_first( $pageProps );
		$pageID = $pageProps[$firstKey]['pageid'];
		return $pageID;
	}

	public static function getApiEndpoint( $repoSource ) {
		$api = "";
		if ( $repoSource == 'commons' || $repoSource == '' ) {
			$api = "https://commons.wikimedia.org/w/api.php";
		} else {
			// @todo config options
			global $wgForeignFileRepos;
			// FileRepos : https://www.mediawiki.org/wiki/Manual:$wgForeignFileRepos
			foreach( $wgForeignFileRepos as $repo ) {
				if ( $repo["name"] == $repoSource ) {
					// only if 'class' = ForeignAPIRepo::class,
					$api = array_key_exists( "apibase", $repo ) ? $repo["apibase"] : "";
				}
			}
		}
		return $api;
	}

}
