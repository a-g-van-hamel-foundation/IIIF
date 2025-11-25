<?php

/**
 * Action API service for returning IIIF Annotations
 * to facilitate the manifest API.
 * Supports AnnotationList (V2) and AnnotationPage (V3).
 * Based on queries for SMW pages or subobjects.
 * Allows for multiple IIIFAnnotTextProp properties (see config)
 * 
 * Structure: `/api.php?action=iiif-annotations&format=json&formatversion=2&smwconfig={configid}&obj={pageid}&v={apiversionnumber}&canvas={canvasid}`
 */

namespace IIIF\API;

use ApiBase;
use MediaWiki\MediaWikiServices;
use SMW\DIWikiPage;
use Wikimedia\ParamValidator\ParamValidator;
use IIIF\Config\IIIFConfig;
use IIIFUtils;
use IIIF\SMW\IIIFSMW;
use IIIF\IIIFParsers\IIIFAnnotationParsers;

class IIIFAnnotationsAPI extends ApiBase {

	static $smwConfigProps = [];

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * @todo depreciate smwquery
	 * @return bool
	 * @throws ApiUsageException
	 * @throws Exception
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$obj = $params["obj"] !== "false" ? $params['obj'] : false;
		$apiVersion = $params['v'];
		$canvasId = $params['canvas'] !== 'false' ? $params['canvas'] : false;

		$smwQueryEscaped = $params["smwquery"];
		$smwConfig = $params["smwconfig"];
		$annotId = $params["annotid"];
		$destination = $params["dest"] ?? false;

		$id = IIIFUtils::getUrlBase() . "$_SERVER[REQUEST_URI]";
		$apiResult = $this->getResult();

		$items = [];

		if ( $smwQueryEscaped !== "" || $obj !== false ) {
			// Get annotations for object based on query
			self::$smwConfigProps = IIIFConfig::getConfigProps( $smwConfig );

			$annotationCollectionItems = IIIFSMW::getItemsForResource( $obj, $canvasId, $smwQueryEscaped, $smwConfig );

			foreach( $annotationCollectionItems as $item ) {
				if ( $apiVersion == '3' ) {
					$items[] = IIIFAnnotationParsers::preprocessAnnotationV3( $item, "commenting", self::$smwConfigProps );
				} elseif( $apiVersion == '2' ) {
					$items[] = IIIFAnnotationParsers::preprocessAnnotationV2( $item, "commenting", self::$smwConfigProps );
				}
			}
		} elseif ( $annotId !== "" && $destination == "localstorage" ) {
			// Reproduces items to be used in local storage
			// e.g. /api.php?action=iiif-annotations&format=json&annotid={annotid}&v=3&dest=localstorage
			$items = self::getLocalStorageStr( $annotId, "3" );
		} elseif ( $annotId !== "" && $destination !== "localstorage" ) {
			//print_r( "annot page id: $annotId / canvas: $canvasId / apiVersion: $apiVersion " );
			$items = self::getItemsFromPage( $annotId, $canvasId, $apiVersion );
		}

		if ( $destination == 'localstorage' ) {
			self::setupLocalStorageService( $apiResult, $id, $items, $annotId );
		} elseif ( $apiVersion == '3' ) {
			self::setupAnnotationCollectionV3( $apiResult, $id, $items );
		} elseif( $apiVersion == '2' ) {
			self::setupAnnotationCollectionV2( $apiResult, $id, $items );
		}
		return true;
	}

	/**
	 * Set up AnnotationPage for Presentation API v3
	 */
	public static function setupAnnotationCollectionV3( $apiResult, $id, $items ) {
		$arr = array(
			"@context" => [
				"http://www.w3.org/ns/anno.jsonld",
				"http://iiif.io/api/presentation/3/context.json"
			],
			"id" => $id, 
			"type" => "AnnotationPage",
			"items" => $items
		);
		foreach( $arr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
	}

	/**
	 * Fetch annotations from wiki page in JSON format.
	 */
	private static function getItemsFromPage( $annotId, $canvasId = false, $apiVersion = '2' ) {
		$annotStr = IIIFUtils::getRawContentFromPageID( $annotId );
		if ( str_starts_with( $annotStr, "[" ) == false ) {
			print_r( 'Let us add [] ' );
			$annotStr = "[" . $annotStr . "]"; // @todo Need to fix elsewhere
		}
		$annotArr = json_decode( $annotStr, true);
		if ( $apiVersion == '3' ) {
			$annotItems = [];
			foreach ( $annotArr as $annotPage ) {
				// Assuming each AnnotationPage has one item only
				$annotItems[] = $annotPage['items'][0];
			}
			$res = $annotItems;
		} elseif ( $apiVersion == '2' ) {
			$sourceId = "localStorage://?canvasId=" . $canvasId;
			// Get key of array item where id = $sourceId
			$canvasItemKey = array_search( $sourceId, array_column( $annotArr, 'id') );
			if ( $canvasItemKey !== false) {
				$canvasPage = $annotArr[ $canvasItemKey ];
				$canvasItems = ( $canvasPage !== null && array_key_exists( 0, $canvasPage['items'] ) )
					? $canvasPage['items']
					: [];
				$res = [];
				foreach ( $canvasItems as $canvasItem ) {
					$res[] = IIIFAnnotationParsers::convertMiradorAnnotationItemV3toV2( $canvasItem );
				}
			} else {
				$res = [];
			}
		} else {
			$res = [];
		}
		return $res;
	}

	/**
	 * Set up AnnotationList for IIIF Presentation API v2
	 * 
	 * @param object $apiResult
	 * @param string $id
	 * @param array $annotationCollectionItems
	 */
	public static function setupAnnotationCollectionV2( $apiResult, $id, $items ) {
		$arr = array(
			"@context" => "http://iiif.io/api/presentation/2/context.json",
			"@id" => $id,
			"@type" => "sc:AnnotationList",
			"resources" => $items
		);
		foreach( $arr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
	}

	/**
	 * @deprecated moved to IIIFAnnotationParsers
	 */
	public static function preprocessAnnotationV3( $item, $motivation = "commenting" ) {
		return IIIFAnnotationParsers::preprocessAnnotationV3( $item, $motivation );
	}

	/**
	 * @deprecated Moved to IIIFAnnotationParsers
	 */
	public static function preprocessAnnotationV2( $item, $motivation = "oa:commenting" ) {
		return IIIFAnnotationParsers::preprocessAnnotationV2( $item, $motivation );
	}

	/**
	 * @deprecated Moved to IIIFAnnotationParsers
	 * One target property and one/multple annotation properties (concatenated)
	 */
	public static function getPropertyValuesForAnnotation( $item ): array {
		return IIIFAnnotationParsers::getPropertyValuesForAnnotation( $item );
	}

	/**
	 * Maybe SMW has a native function for this.
	 * doUnserialize()
	 * @todo getNamespaceFromNumber is almost identical to one in SMW file
	 */
	private static function resolveDIWikiPage( $diWikipage ) {
		//doUnserialize
		$arr = explode( '#', $diWikipage, 4 );
		$namespaceNumber = intval( $arr[1] );
		$prefix = ( $namespaceNumber !== 0 ) ? IIIFSMW::getNamespaceNameFromIndex( $namespaceNumber ) . ":" : "";
		$pagename = $prefix . $arr[0];
		$str = str_replace( "_", " ", $pagename);
		return $str;
	}

	/**
	 * IIIF API v3 only
	 */
	private static function getLocalStorageStr( $annotId, $apiVersion = '3' ) {
		$annotStr = IIIFUtils::getRawContentFromPageID( $annotId );
		if ( str_starts_with( $annotStr, "[" ) == false ) {
			$annotStr = "[" . $annotStr . "]"; // @todo Need to fix elsewhere
		}
		$annotArr = json_decode( $annotStr, true);
		$res = [
			"raw" => $annotStr,
			"json" => $annotArr
		];
		return $res;
	}

	public static function setupLocalStorageService( $apiResult, $id, $items, $pageid ) {
		$arr = array(
			"id" => $id,
			"type" => "LocalStorageService",
			"pageid" => $pageid,
			"description" => "The raw text required for the creation of local storage items in Mirador with the Mirador Annotations plugin (0.4.0)."
		);
		foreach( $items as $k => $v ) {
			$arr[$k] = $v;
		}
		foreach( $arr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() : array {
		$arr = [
			"manifest" => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			"obj" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "false"
			],
			// What to return - manifest, annotationPage, ...
			"v" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "2"
			],
			// v2 only:
			"canvas" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "false"
			],
			"smwconfig" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			],
			"smwquery" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
				//ApiBase::PARAM_ISMULTI
			],
			"annotid" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			],
			"dest" => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
		return $arr;
	}

	/**
	 * @todo
	 */
	protected function getExamplesMessages() : array {
		return [
			'action=iiif-annotations&obj=...&v=2' => 'apihelp-iiif-example-0',
			'action=iiif-annotations&obj=...&v=3' => 'apihelp-iiif-example-1',
		];
	}

}
