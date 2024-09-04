<?php
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

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
		global $wgCanonicalServer, $wgScriptPath;
		$urlBase = $wgCanonicalServer . $wgScriptPath;
		return $urlBase;
	}

	/**
	 * Get json-encoded content from URL and return array
	 * Does not allow for http: schemes
	 * @param string $manifestUrl
	 * 
	 * @return array|null
	 */
	public static function getArrayFromJsonUrl( $url, $useCurl = false ) {
		/* cURL session?
		$ch = curl_init( $apiUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close( $ch );
		*/
		$parsedUrlArr = parse_url( $url );
		if ( $parsedUrlArr['scheme'] !== 'https' ) {
			return null;
		}
		$json = file_get_contents( $url );
		// associative array:
		$arr = json_decode( $json, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			// JSON is valid
			return $arr;
		}
		return null;
	}

	public static function isJson( $string ) {
		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;
	 }

	/**
	 * Return possible thumbnail widths
	 * @todo make configurable in settings
	 */
	public static function getThumbnailWidths(): array {
		$widths = [ 320, 640, 800, 1024, 1280, 2560 ];
		return $widths;
	}

	public static function printArray( $arr ) {
		print_r( "<pre>" );
		print_r( $arr );
		print_r( "</pre>" );
	}

	public static function getFilefromPageID( $id ) {
		$services = MediaWikiServices::getInstance();
		$localRepo = $services->getRepoGroup()->getLocalRepo();
		$titleObj = \Title::newFromID( $id, 0 ); // instance of Title
		$file = $localRepo->findFile( $titleObj );
		return $file;
	}

	public static function getFullpageNameFromPageID( $id ): string {
		$titleObj = \Title::newFromID( $id, 0 );
		return $titleObj->getFullText();
	}

	public static function getRawContentFromPageID( $id ) {
		$titleObj = \Title::newFromID( $id, 0 );
		$res = self::getRawContentFromTitleObj( $titleObj );
		return $res;
	}
	public static function getRawContentFromPageName( $fullpagename ) {
		$titleObj = \Title::newFromText ( $fullpagename );
		$res = self::getRawContentFromTitleObj( $titleObj );
		return $res;
	}

	/**
	 * Largely reproduced from CODECS Resources extension 2023
	 */
	public static function getRawContentFromTitleObj( $titleObj ) {
        $article = new \Article( $titleObj );
        $title = $article->getTitle();
        $context = $article->getContext();
        $request = $context->getRequest();

        $rev = MediaWikiServices::getInstance()
			->getRevisionLookup()
			//->getRevisionByTitle( $title, $article->getOldId() );
            ->getRevisionByTitle( $title, $article->getRevIdFetched() );
        if ( ! $rev ) {
            return false;
        }
        $slot = $request->getText( 'slot', SlotRecord::MAIN ); // main

        $lastmod = wfTimestamp( TS_RFC2822, $rev->getTimestamp() );
        $request->response()->header( "Last-modified: $lastmod" );

        $content = ( $rev->hasSlot( $slot ) ) ? $rev->getContent( $slot ) : null;
        // excluding lots of checks we should be including - https://github.com/Open-CSP/WSSlots/blob/master/src/Actions/SlotAwareRawAction.php#L28 - e.g. check for slot, revision, ?section,
        $text = $content->getText();
        return $text;
    }

	/**
	 * Convert Mirador annotation (stored as V3) to V2
	 * Based on https://github.com/ProjectMirador/mirador-annotations/blob/master/src/SimpleAnnotationServerV2Adapter.js#L73
	 */
	public static function convertMiradorAnnotationItemV3toV2( $v3anno ) {
		$id = ( !empty($v3anno['id']) ) ? $v3anno['id'] : "";
		$targetSourceId = ( !empty( $v3anno['target']['source']['id'] ) ) 
			? $v3anno['target']['source']['id']
			: "";
		$sourceId = $v3anno['target']['source']; // or source.id
		//print_r( "targetSourceid is $targetSourceId / targetSource is $sourceId ");
		//"on": "http://example.org/iiif/book1/canvas/p1#xywh=100,150,500,30"
		$on = [
			"@type" => "oa:SpecificResource",
			"full" => $sourceId
			// selector will be added later
			// rotation?
		];
		$resource = [
			"@type" => "oa:SpecificResource",
			"full" => [
				"@type" => "cnt:ContentAsText", // dctypes:Text
				"chars" => "Rubrics are Red, ..."
			]
		];
		
		// https://iiif.io/api/presentation/2.1/ full and selector belong to resource not on? 		
		$v2anno = [
			"@id" => $id,
			"@context" => "http://iiif.io/api/presentation/2/context.json",
			"@type" => "oa:Annotation",
			"motivation" => "oa:commenting",
			"on" => $on,
			"resource" => $resource // added
		];
		// "full" => $onfull

		if ( self::isSequentialArray( $v3anno['body'] ) ) {
			foreach( $v3anno['body'] as $body ) {
				$v2anno['resource'][] = self::convertAnnoBodyV3toV2( $body );
			}
		} else {
			$v2anno['resource'] = self::convertAnnoBodyV3toV2( $v3anno['body'] );
		}

		// @todo if array item does not exist
		
		if ( !empty( $v3anno['target']['selector'] ) ) {
			$targetSelector = $v3anno['target']['selector'];
			//$selector = $v3anno['body']['target']['selector']; //?
			// if this exists then
			if ( self::isSequentialArray($targetSelector) ) {	
				$selectors = [];
				//$targetSelector = $v3anno['target']['selector'];//?
				foreach( $targetSelector as $sel ) {
					$selectors[] = self::convertAnnoSelectorV3toV2( $sel );
				}
				// @DG changed 'on' to 'resource'
				$v2anno['on']['selector'] = [
					"@type" => "oa:Choice",
					"default" => $selectors[0],
					"item" => $selectors[1],
				];
			} else {
				//$targetSelector = $v3anno['target']['selector'];//?
				// @DG changed 'on' to 'resource'
				$v2anno['on']['selector'] = self::convertAnnoSelectorV3toV2( $targetSelector );
			}
			// @todo handle non-existing arrays
			if ( !empty($v3anno['target']['source']['partOf']) ) {
				$v2anno['on']['within'] = [
					"@id" => $v3anno['target']['source']['id'],
					"@type" => "sc:Manifest"
				];
			} else {
				// @todo
				//$v3anno['target']['source']
			}
		}
		// if selector does not exist then nothing...
		return $v2anno;
	}

	/**
	 * Called a resource in V2
	 */
	public static function convertAnnoBodyV3toV2( $v3body ): array {
		$type = ( array_key_exists( 'purpose', $v3body) && $v3body['purpose'] == "tagging" ) ? "oa:Tag" : "cnt:ContentAsText"; // dctypes:Text or cnt:ContentAsText
		$v2body = [
			"@type" => $type,
		];
		if ( array_key_exists( 'format', $v3body) ) {
			$v2body['format'] = $v3body['format']; // e.g. "text/html"
		}
		if ( array_key_exists( 'value', $v3body) ) {
			$v2body['chars'] = $v3body['value'];
			//$v2body['full']['chars'] = $v3body['value'];
		}
		if ( array_key_exists( 'language', $v3body) ) {
			$v2body['language'] = $v3body['language'];
		}
		return $v2body;
	}

	/**
	 * 
	 */
	public static function isSequentialArray( $arr ): bool {
		// don't use  ( count( $arr ) === count( array_filter( $arr, 'is_numeric' ) ) 
		if ( gettype($arr) == 'array' && array_key_exists( 0, $arr ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function convertAnnoSelectorV3toV2( $v3selector ) {
		$res = [];
		if ( !empty($v3selector['type']) ) {
			switch ( $v3selector['type'] ) {
				case 'SvgSelector':
				$res = [
					"@type" => "oa:SvgSelector",
					"value" => $v3selector['value']
				];
				break;
				case 'FragmentSelector':
				$res = [
					"@type" => "oa:FragmentSelector",
					"value" => $v3selector['value']
				];
				break;
			}
		}
		return $res;
	}

	/**
	 * Convert wikitext to html using a fresh new Parser.
	 * @todo Partial method.
	 */
	public static function convertStrToWikitext( $str, $parser = null ) {
		$newParser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$newParser->setOutputType( 'html' );
		$newParser->setOptions( \ParserOptions::newFromAnon() );
		$newParsed = $newParser->parse( $str,
			\RequestContext::getMain()->getTitle(),
			$newParser->getOptions()
		)->getText();
		// print_r( $newParsed );		
		return $newParsed;
	}

}
