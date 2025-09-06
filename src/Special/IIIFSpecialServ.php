<?php

namespace IIIF\Special;

use MediaWiki\MediaWikiServices;
use Title;
use RedirectSpecialPage;
use File;
use IIIFUtils;
use IIIFMwRemote;
use IIIF\IIIFParsers\IIIFMwImageUtils;

/**
 * Service to create redirects from Special:IIIFServ/...
 * Special:IIIFServ/presentation/{params} redirects to API
 * Special:IIIFServ/image/{params} has different destinations
 * Special:IIIFServ/image/{imgidentifier}/{otherparams}/default.{file ext} redirects to thumbnail
 * e.g. /image/imgidentifier/full/1024,270/0/default.jpg
 * Special:IIIFServ/image/{params}/info.json redirects to API
 * 
 * Redirect IIIF to thumbnail image
 * e.g. /Special:IIIFServ/image/18814/full/1024,270/0/default.jpg
 * => /images/thumb/8/8b/Website-omslag7.jpg/1024px-Website-omslag7.jpg
 * 
 * Similar implementation
 * @link https://github.com/wikimedia/mediawiki/blob/master/includes/specials/SpecialApiHelp.php
 * 
 * @todo Doxygen
 * @todo maybe distinguish v2/v3: image2/image3 and presentation2/presentation3
 * 
 * {scheme}://{server}{/prefix}/{identifier}/  {region}/{size}/{rotation}/{quality}.{format}
 * https://example.org/image-service/abcd1234 /full/max/0/default.jpg
 * https://codecs.vanhamel.nl/Special:IIIFServ/image/local/44730/full/800,/0/default.jpg
 * 
 * e.g. 18814/full/1024,270/0/default.jpg
 * https://codecs.codicology.eu/images 
 * https://codecs.codicology.eu/images/thumb
 * original:
 * $redirect = $this->getRedirect( $subpage );
 * $query = $this->getRedirectQuery( $subpage );
 **/

class IIIFSpecialServ extends RedirectSpecialPage {

	public function __construct(
		$name = "IIIFServ"
	) {
		parent::__construct( $name );
		//$this->mAllowedRedirectParams = [ 'width', 'height' ];
	}
	
	public function execute( $subpage ) {
		if ( $subpage == null ) {
			return;
		}
		$urlParts = explode( "/", $subpage );
		$request = [
			"purpose" => $urlParts[0],
			// local, commons, etc.
			"repo" => array_key_exists( 1, $urlParts ) ? $urlParts[1] : "",
			"identifier" => array_key_exists( 2, $urlParts ) ? $urlParts[2] : ""
		];

		if ( $request["purpose"] === "image" ) {
			$qualityAndFormat = explode( ".", $urlParts[ array_key_last($urlParts) ] );
			if ( $qualityAndFormat[0] === "default" ) {
				// Redirect to thumbnail
				// @example /Special:IIIFServ/image/local/44730/full/800,/0/default.jpg
				if ( $request["repo"] === "local" || $request["repo"] === "" ) {
					$thumbnail = IIIFMwImageUtils::getIIRImage( $urlParts );
					if ( !$thumbnail && $thumbnail !== null ) {
						$thumbnail = IIIFUtils::getUrlBase() . "/extensions/IIIF/assets/no-image-available.png";
					}
				} else {
					$thumbnail = IIIFMwRemote::getIIRImageRemotely( $urlParts );
				}
				$this->getOutput()->redirect( $thumbnail );
			} else {
				// ?action=iiif-mw-img
				// Assuming final item in $urlParts = "info.json"
				// OR without "info.json" - not strictly necessary for level 0 though.
				$apiUrl = self::getIIR( $urlParts );
				$this->getOutput()->redirect( $apiUrl );
			}
		} elseif ( str_starts_with( $request["purpose"], "presentation" ) ) {
			// ?action=iiif-mw-pres
			$apiVersion = $request["purpose"] === "presentation3" ? "3" : "2";
			$apiUrl = self::getPresentationApiUri( $urlParts, $apiVersion );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $request["purpose"] === "collection" ) {
			// ?action=iiif-collection
			$apiUrl = self::getCollectionApiUri( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $request["purpose"] === "wiki" ) {
			// ?action=iiif-wiki
			$apiUrl = self::getWikiApiUri( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $request["purpose"] === "manifest" && $request["repo"] == "smwconfig" ) {
			$apiUrl = self::getManifestApiUri( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} else {
			$this->showNoRedirectPage();
		}
		/*
		if ( $redirect instanceof Title ) {
			// Redirect to a page title with possible query parameters
			$url = $redirect->getFullUrlForRedirect( $query );
			$this->getOutput()->redirect( $url );
		} elseif ( $redirect === true ) {
			// Redirect to index.php with query parameters
			$url = wfAppendQuery( wfScript( 'index' ), $query );
			$this->getOutput()->redirect( $url );
		} else {
			$this->showNoRedirectPage();
		}
		*/
		
	}

	public function getRedirect( $par ) {
		$subpage = $par;
		print_r( $subpage );
		//
	}

	/**
	 * Redirects Special:.../json to iiif-mw-img API (Image Information Request)
	 * @example orig: https://codecs.vanhamel.nl/Special:IIIFServ/image/{repo}/18814/info.json
	 * @example dest: https://codecs.vanhamel.nl/api.php?action=iiif-mw-img&format=jsonfm&pageid=18814&source={repo}
	 * @param array $parts
	 */
	private static function getIIR( $urlParts ) {
		// $services = MediaWikiServices::getInstance();
		// api.php?action=iiif-mw-img&format=json&pageid=a // pageid
		$repo = $urlParts[1];
		$id = $urlParts[2];
		$host = IIIFUtils::getUrlBase();
		$apiUrl = $host . "/api.php?action=iiif-mw-img&format=json&formatversion=2&pageid={$id}&source={$repo}";
		return $apiUrl;
	}

	/**
	 * @deprecated moved to IIIFMwImageUtils::getIIRImage()
	 * @return string|bool
	 */
	private static function getIIRImage( $urlParts ) {
		return IIIFMwImageUtils::getIIRImage( $urlParts );
	}

	/**
	 * Get Presentation API
	 * @example /Special:IIIFServ/presentation/.../{repo}/pageids/19152,18814,51926,50340,19133,19143
	 * Is this possible? with Pres API?
	 * https://codecs.vanhamel.nl/Special:IIIFServ/presentation/{repo}/smwquery/((Concept:...))
	 * https://codecs.vanhamel.nl/Special:IIIFServ/presentation/{repo}/pageids/34234,5656,65656
	 * 
	 * Special:IIIFServ/presentation/{source}/pageids/{pageids}
	 * => Special:IIIFServ/presentation/{...}/{source}/{repo}/pageids/{pageids}
	 * 
	 * Segment
	 * (0) "presentation"
	 * (1) manifest, sequence, canvas, annotation
	 * (2) [repo] local, commons, etc.
	 * (3) depends: e.g. pageids/files/smwquery
	 * (4)
	 */
	private static function getPresentationApiUri( $urlParts, $apiVersion = "2" ) {
		$uriStr = "";
		$baseUrl = IIIFUtils::getUrlBase();
		$uriBase = $baseUrl . "/api.php?action=iiif-mw-pres&format=json&formatversion=2&version={$apiVersion}"; // to be renamed
		$segments = [
			"module" => "presentation",
			"resourceType" => $urlParts[1],
			// source/repo, e.g. local, commons, etc.
			"source" => $urlParts[2]
		];
		switch( $segments["resourceType"] ) {
			case "annotations":
			case "annotationpage":
			case "annotation":
			case "canvas":
			case "image":
			case "sequence":
				$id = $urlParts[3];
				$uriStr = "&resourcetype={$segments["resourceType"]}&source={$segments["source"]}&id=" . $id;
			break;
			case "manifest":
			default:
				$type = $segments["type"] = $urlParts[3];
				//$segments["items"] = $urlParts[4];
				$repo = $segments["source"];
				switch ( $type ) {
					case "pageids":
					case "files":
					case "smwquery":
						$uriStr = "&source={$repo}&{$type}=" . $urlParts[4];
					break;
				}
		}
		//$repo = $urlParts[2]; 
		//$type = $urlParts[3];
		return $uriBase . $uriStr;
	}

	/**
	 * Redirect to collection API
	 * @see IIIFCollectionAPI
	 */
	private static function getCollectionApiUri( $urlParts ) {
		$baseUrl = IIIFUtils::getUrlBase();
		$uriBase = $baseUrl . "/api.php?action=iiif-collection&format=json&formatversion=2";
		if ( $urlParts[1] == 'smwconfig' ) {
			// @example Special:IIIFServ/collection/smwconfig/{pageid}
			$smwConfig = ( array_key_exists( 2, $urlParts) ) ? $urlParts[2] : null;
			$uriStr = ( $smwConfig !== null ) ? "&smwconfig=" . $smwConfig : "";
		} elseif ( $urlParts[1] == 'concept' ) {
			// @example Special:IIIFServ/collection/concept/{conceptname}
			// Use array_slice because concept name may contain "/"
			$conceptArr = ( array_key_exists( 2, $urlParts) ) ? array_slice( $urlParts, 2 ) : null;
			$concept = implode( "/", $conceptArr );
			$uriStr = ( $concept !== null ) ? "&concept=" . $concept : "";
		} elseif ( $urlParts[1] == 'smwquery' ) {
			// @example Special:IIIFServ/collection/smwquery/{escapedquery}
			$smwEscapedQuery = ( array_key_exists( 2, $urlParts) ) ? $urlParts[2] : null;
			$uriStr = ( $smwEscapedQuery !== null ) ? "&smwquery=" . $smwEscapedQuery : "";
		} else {
			$uriStr = "";
		}
		return $uriBase . $uriStr;
	}

	/**
	 * Redirect. See iiif.api-wiki.php
	 * orig: Special:IIIFServ/wiki/{pageID}
	 * @param mixed $urlParts
	 * @return string
	 */
	private static function getWikiApiUri( $urlParts ): string {
		$baseUrl = IIIFUtils::getUrlBase();
		$pageID = $urlParts[1];
		$uri = $baseUrl . "/api.php?action=iiif-wiki&id={$pageID}&format=json&formatversion=2";
		return $uri;
	}

	/**
	 * 
	 * @param mixed $urlParts
	 * @return string
	 */
	private static function getManifestApiUri( $urlParts ): string {
		// 1 = 'smwconfig'
		$smwConfig = $urlParts[2];
		$obj = $urlParts[3];
		$manifest = implode( "/", array_slice( $urlParts, 4 ) );
		$uri = IIIFUtils::getUrlBase() .  "/api.php?action=iiif-manifest&smwconfig={$smwConfig}&obj={$obj}&format=json&formatversion=2&manifest={$manifest}";
		return $uri;
	}

	/**
	 * @deprecated moved to IIIFMwImageUtils::createThumbnail()
	 */
	private static function createThumbnail( File $img, $width, $height = -1 ): string {
		return IIIFMwImageUtils::createThumbnail( $img, $width, $height );
	}

	protected function showNoRedirectPage() {
		$class = static::class;
		//throw new MWException( "This class ($class) does not redirect." );
	}

}
