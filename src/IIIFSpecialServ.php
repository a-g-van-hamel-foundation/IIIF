<?php
use MediaWiki\MediaWikiServices;
//use MediaWiki\Title\Title;
//namespace Iiif\Special

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
 **/

class IIIFSpecialServ extends RedirectSpecialPage {

	public function __construct(
		$name = "IIIFServ"
	) {
		parent::__construct( $name );
		//$this->mAllowedRedirectParams = [ 'width', 'height' ];
	}
	
	public function execute( $subpage ) {
		$urlParts = explode( "/", $subpage );
		$repo = $urlParts[1];
		// e.g. 18814/full/1024,270/0/default.jpg
		// https://codecs.codicology.eu/images
		// https://codecs.codicology.eu/images/thumb
		// original:
		// $redirect = $this->getRedirect( $subpage );
		// $query = $this->getRedirectQuery( $subpage );
		$finalPart = end($urlParts);
		$finalPartArr = explode( ".", $finalPart );

		if ( $urlParts[0] == "image" && $finalPartArr[0] == "default" ) {
			// Redirect to thumbnail
			if ( $repo == "local" || $repo == "" ) {
				$thumbnail = self::getIIRImage( $urlParts );
			} else {
				$thumbnail = IIIFMwRemote::getIIRImageRemotely( $urlParts );
			}
			$this->getOutput()->redirect( $thumbnail );
		} elseif ( $urlParts[0] == "image" ) {
			// ?action=iiif-mw-img
			// Assuming end($urlParts) == "info.json"
			// OR without "info.json" - not strictly necessary for level 0 though.
			$apiUrl = self::getIIR( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $urlParts[0] == "presentation" ) {
			// ?action=iiif-mw-pres
			$apiUrl = self::getPresentationApiUri( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $urlParts[0] == "collection" ) {
			// ?action=iiif-collection
			$apiUrl = self::getCollectionApiUri( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $urlParts[0] == "wiki" ) {
			// ?action=iiif-wiki
			$apiUrl = self::getWikiApiUri( $urlParts );
			$this->getOutput()->redirect( $apiUrl );
		} elseif ( $urlParts[0] == "manifest" && $urlParts[1] == "smwconfig" ) {
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
		$services = MediaWikiServices::getInstance();
		// api.php?action=iiif-mw-img&format=json&pageid=a // pageid
		$repo = $urlParts[1];
		$id = $urlParts[2];
		$host = IIIFUtils::getUrlBase();
		$apiUrl = $host . "/api.php?action=iiif-mw-img&format=json&pageid={$id}&source={$repo}";
		return $apiUrl;
	}

	/**
	 * Constructs thumbnail on the basis of the subpage
	 * /{repo}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
	 * Special:IIIFServ/image/local/18814/full/1024,270/0/default.jpg
	 * Example subpage: local/imgidentifier/full/1024,270/0/default.jpg
	 * local/125,15,200,200/max/0/default.jpg
	 * 
	 */
	private static function getIIRImage( $subpageArr ) {
		$services = MediaWikiServices::getInstance();
		$localRepo = $services->getRepoGroup()->getLocalRepo();
		$parts = $subpageArr;
		// Ignore $parts[0], which should be "image"
		$repo = $parts[1];
		$prefixAndIdArr = explode( ":", $parts[2] );
		if ( $prefixAndIdArr < 2 ) {
			$pageidArr = explode( "-", $parts[2] );
		} else {
			// has prefix for repo - should not be necessary though
			$repo = $prefixAndIdArr[0];
			$pageidArr = explode( "-", $prefixAndIdArr[1] );
		}
		$pageidArr = explode( "-", $parts[2] ); // assuming we're doing this
		$pageid = $pageidArr[0];
			// @todo: deal with possible revid = $pageidArr[1] ?
		$region = $parts[3]; // assuming full or max - we don't deal with regions just yet
		$size = $parts[4]; // comma-separated
		$sizeArr = explode( ",", $size );
		$width = ( array_key_exists( 0, $sizeArr ) ) ? intval($sizeArr[0]) : 0;
		$height = ( array_key_exists( 1, $sizeArr ) && $sizeArr[1] !== "" ) ? intval($sizeArr[1]) : -1;
		// Currently ignoring third and fourth param of size

		$rotation = $parts[5]; // assuming 0
		//$qualityformatArr = explode( ".", $parts[6] );
		$quality = "default"; // assuming ... 
		//$format = end( $qualityformat );

		$titleObj = \Title::newFromID( $pageid, 0 );
		$file = $localRepo->findFile( $titleObj );
		if ( $file == false ) {
			return "";
		}

		$baseUrl = IIIFUtils::getUrlBase();
		$thumbnail = $baseUrl . self::createThumbnail( $file, $width, $height );
		return $thumbnail;
	}

	/**
	 * https://codecs.codicology.eu/Special:IIIFServ/presentation/{repo}/pageids/19152,18814,51926,50340,19133,19143
	 */
	private static function getPresentationApiUri( $urlParts ) {
		$baseUrl = IIIFUtils::getUrlBase();
		$uriBase = $baseUrl . "/api.php?action=iiif-mw-pres&format=json"; // to be renamed
		// is this possible? with Pres API?
		// https://codecs.codicology.eu/Special:IIIFServ/presentation/{repo}/smwquery/((Concept:...))
		// https://codecs.codicology.eu/Special:IIIFServ/presentation/{repo}/pageids/34234,5656,65656
		$repo = $urlParts[1]; // local, commons, etc.
		$type = $urlParts[2];
		$uriStr = "";
		switch ( $type ) {
			case "pageids":
			case "files":
			case "smwquery":
				$uriStr = "&source={$repo}&{$type}=" . $urlParts[3];
			break;
		}
		return $uriBase . $uriStr;
	}

	/**
	 * Redirect to collection API
	 * @see IIIFCollectionAPI
	 */
	private static function getCollectionApiUri( $urlParts ) {
		$baseUrl = IIIFUtils::getUrlBase();
		$uriBase = $baseUrl . "/api.php?action=iiif-collection&format=json";
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
		$uri = $baseUrl . "/api.php?action=iiif-wiki&id={$pageID}&format=json";
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
		$uri = IIIFUtils::getUrlBase() .  "/api.php?action=iiif-manifest&smwconfig={$smwConfig}&obj={$obj}&format=json&manifest={$manifest}";
		return $uri;
	}

	/**
	 * Create thumbnail
	 * @param File $img
	 * @todo maybe check if we can make the requested thumbnail, and get transform parameters.
	 * cf. ApiQueryImageInfo : $finalThumbParams = $this->mergeThumbParams( $img, $scale, $params['urlparam'] );
	 * @link https://phabricator.wikimedia.org/source/mediawiki/browse/master/includes/api/ApiQueryImageInfo.php
	 * @link https://phabricator.wikimedia.org/source/mediawiki/browse/REL1_39/includes/filerepo/file/File.php
	 */
	private static function createThumbnail( File $img, $width, $height = -1 ): string {
		$thumb = $img->createThumb( $width, $height );
		return $thumb;
	}

}
