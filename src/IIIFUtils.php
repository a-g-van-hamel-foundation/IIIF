<?php

//use Title;
use MediaWiki\MediaWikiServices;
use ParserOptions;
use RequestContext;
use MediaWiki\Revision\SlotRecord;
//use WSSlots\WSSlots;
use IIIF\IIIFParsers\IIIFAnnotationParsers;

class IIIFUtils {

	/** 
	 * Get the base url ending with the script path (without trailing '/')
	 * e.g. https://www.example.com or https://example.com/wiki
	 * cf. $host = "https://$_SERVER[HTTP_HOST]";
	 */
	public static function getUrlBase() {
		// Globals deprecated in the future? 
		// if so, use MediaWiki\MediaWikiServices::getUrlUtils()->getCanonicalServer()
		// https://doc.wikimedia.org/mediawiki-core/master/php/UrlUtils_8php_source.html
		// $canonicalServer = MediaWikiServices::getUrlUtils()->getCanonicalServer();
		global $wgCanonicalServer, $wgScriptPath;
		$urlBase = $wgCanonicalServer . $wgScriptPath;
		return $urlBase;
	}

	/**
	 * Get JSON-encoded content from URL and return array
	 * Disallows http: schemes
	 * @param string $manifestUrl
	 * 
	 * @return array|null
	 */
	public static function getArrayFromJsonUrl( $url, $useCurl = true ) {
		$parsedUrlArr = parse_url( $url );
		if ( $parsedUrlArr["scheme"] !== "https" ) {
			return null;
		}
		if ( $useCurl ) {
			$json = self::getFileContentsByCURL( $url );			
		} else {
			// Recommended to use stream_context_create($options);
			$json = file_get_contents( $url, false );
		}
		if ( $json === "" ) {
			return null;	
		}
		$arr = json_decode( $json, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			// JSON is valid
			return $arr;
		}
		return null;
	}

	public static function getFileContentsByCURL( $apiUrl ) {
		$httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
		// rely on default user agent
		// @todo consider custom one eg "userAgent" => "IIIF extension/0.0 ($urlBase)"
		// $urlBase = self::getUrlBase();
		$options = [ 
			"followRedirects" => true
		];
		$res = $httpRequestFactory->get( $apiUrl, $options, __METHOD__ );
		return $res;
	}

	/**
	 * @deprecated Superseded by getFileContentsByCURL()
	 * Kept in for now
	 * @param mixed $apiUrl
	 * @return bool|string
	 */
	public static function getFileContentsByCURLStandard( $apiUrl ) {
		$ch = curl_init( $apiUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$urlBase = self::getUrlBase();
		curl_setopt( $ch, CURLOPT_USERAGENT, "IIIF extension/0.0 ($urlBase)" );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		$output = curl_exec( $ch );
		curl_close( $ch );
		return $output;
	}

	public static function isJson( $string ) {
		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;
	 }

	public static function getFilefromPageID( $id ) {
		$services = MediaWikiServices::getInstance();
		$localRepo = $services->getRepoGroup()->getLocalRepo();
		$titleObj = Title::newFromID( $id, 0 ); // instance of Title
		$file = $localRepo->findFile( $titleObj );
		return $file;
	}

	public static function getFullpageNameFromPageID( $id ): string {
		$titleObj = Title::newFromID( $id, 0 );
		return $titleObj->getFullText();
	}

	public static function getRawContentFromPageID( $id, $slotName = "main" ) {
		$titleObj = Title::newFromID( $id, 0 );
		$res = self::getRawContentFromTitleObj( $titleObj, $slotName );
		return $res;
	}

	public static function getRawContentFromPageName( $fullpagename, $slotName = "main" ) {
		$titleObj = Title::newFromText ( $fullpagename );
		$res = self::getRawContentFromTitleObj( $titleObj, $slotName );
		return $res;
	}

	/**
	 * Largely reproduced from CODECS Resources extension 2023
	 */
	public static function getRawContentFromTitleObj( $titleObj, string $slotName = "main" ) {
		$article = new \Article( $titleObj );
		$title = $article->getTitle();

        $revRecord = MediaWikiServices::getInstance()
			->getRevisionLookup()
			//->getRevisionByTitle( $title, $article->getOldId() );
            ->getRevisionByTitle( $title, $article->getRevIdFetched() );
		if ( !$revRecord ) {
			return false;
		}

		$webRequest = $article->getContext()->getRequest();
		if ( $slotName === "main" || $slotName === "" ) {
			// Get normalised name of main slot
			$slot = $webRequest->getText( 'slot', SlotRecord::MAIN );
		} else {
			$slot = $slotName;
		}

		$lastmod = wfTimestamp( TS_RFC2822, $revRecord->getTimestamp() );
		$webRequest->response()->header( "Last-modified: $lastmod" );

		$content = $revRecord->hasSlot( $slot ) ? $revRecord->getContent( $slot ) : null;
		// excluding lots of checks we should be including - cf. https://github.com/Open-CSP/WSSlots/blob/master/src/Actions/SlotAwareRawAction.php#L28 - e.g. check for slot, revision, ?section,
		return $content !== null ? $content->getText() : "";
    }

	/**
	 * Checks if the array is sequential not numeric
	 */
	public static function isSequentialArray( $arr ): bool {
		// don't use  ( count( $arr ) === count( array_filter( $arr, 'is_numeric' ) ) 
		if ( gettype($arr) == 'array' && array_key_exists( 0, $arr ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Convert wikitext to html using a fresh new Parser
	 * misnomer @todo rename to e.g. parseWikitext( ... )
	 * @todo Partial method.
	 */
	public static function convertStrToWikitext( mixed $str, mixed $parser = null ) {
		if ( $str === null ) {
			return "";
		}
		if( $parser === null ) {
			$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		}
		$parser->setOutputType( 'html' );
		$parser->setOptions( ParserOptions::newFromAnon() );
		$pageRef = RequestContext::getMain()->getTitle();
		if ( $pageRef === null ) {
			return "";
		}
		$newParsed = $parser->parse(
			$str,
			RequestContext::getMain()->getTitle(),
			$parser->getOptions()
		)->getText();
		return $newParsed;
	}

	/**
	 * Fetch fetch nested array values, or
	 * get a default (null or otherwise)
	 * @param array $path - keys
	 * @param array $array - the array to traverse
	 * @param mixed $default
	 * @return array|string|null
	 */
	public static function getArrayPath( array $path, array $array, $default = null ) {
		$nextItem = $array;
		foreach( $path as $k ) {
			if( gettype($nextItem) === "array" && array_key_exists( $k, $nextItem ) ) {
				$nextItem = $nextItem[$k];
			} else {
				return $default;
			}
		}
		return $nextItem;
	}

	/**
	 * Moved from IIIFJson
	 * @param array $templateArr
	 * @param string $targetType
	 * @param string $targetName
	 * @param array $userParams
	 * @return string
	 */
	public static function convertArrayToWikiInstances(
		array $templateArr,
		string $targetType,
		string $targetName,
		array $userParams = []
	) {
		$str = "";
		if ($targetType == "template") {
			$prefix = "{{";
		} elseif ($targetType == "widget") {
			$prefix = "{{#widget:";
		} elseif ($targetType == "module") {
			$prefix = "{{#invoke:";
		} elseif ($targetType == "subobject") {
			// Afterthought not implemented, but note they're nameless
			$prefix = "{{#subobject:";
		}
		foreach ( $templateArr as $instance ) {
			$str .= $prefix . $targetName . "\n";
			foreach ($instance as $k => $v) {
				$str .= "|{$k}={$v}" . "\n";
			}
			foreach( $userParams as $name => $val ) {
				$str .= "|{$name}={$val}";
			}
			$str .= "}}";
		}
		return $str;
	}

	/**
	 * Moved from IIIFJson
	 * @param array $arr
	 * @param array $userParams
	 * @return array<array>
	 */
	public static function appendCustomToArray(
		array $arr,
		array $userParams
	) {
		$newArr = [];
		foreach ( $arr as $k => $instance ) {
			$newInstance = [];
			foreach( $instance as $k => $v ) {
				$newInstance[$k] = $v;
			}
			foreach( $userParams as $name => $v ) {
				$newInstance[$name] = $v;
			}
			$newArr[] = $newInstance;
		}
		return $newArr;
	}

	/**
	 * Dev: For testing purposes only.
	 * @internal
	 */
	public static function printArray( $arr ) {
		print_r( "<pre>" );
		print_r( $arr );
		print_r( "</pre>" );
	}

	// DEPRECATED:

	/**
	 * @deprecated
	 */
	public static function getThumbnailWidths(): array {
		$widths = [ 320, 640, 800, 1024, 1280, 2560 ];
		return $widths;
	}

	/**
	 * @deprecated use IIIFAnnotationParsers::convertMiradorAnnotationItemV3toV2
	 */
	public static function convertMiradorAnnotationItemV3toV2( $v3anno ) {
		return IIIFAnnotationParsers::convertMiradorAnnotationItemV3toV2( $v3anno );
	}

	/**
	 * @deprecated moved to IIIFAnnotationParsers class
	 */
	public static function convertAnnoBodyV3toV2( $v3body ): array {
		return IIIFAnnotationParsers::convertAnnoBodyV3toV2( $v3body );
	}

	/**
	 * @deprecated use IIIFAnnotationParsers::convertAnnoSelectorV3toV2
	 */
	public static function convertAnnoSelectorV3toV2( $v3selector ) {
		return IIIFAnnotationParsers::convertAnnoSelectorV3toV2( $v3selector );
	}


}
