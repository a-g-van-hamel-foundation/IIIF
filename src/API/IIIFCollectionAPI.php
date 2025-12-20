<?php

/**
 * Returns a IIIF Collection (V3) of referenced Manifests (V2/V3) over the API.
 * @todo referenced Collections witin the Collection
 * @todo thumbnails?
 * @link https://iiif.io/api/cookbook/recipe/0032-collection/
 */

namespace IIIF\API;

use MediaWiki\MediaWikiServices;
use SMW\DIWikiPage;
use Wikimedia\ParamValidator\ParamValidator;
use IIIF\IIIFUtils;
use IIIF\Config\IIIFConfig;
use IIIF\SMW\IIIFSMW;

class IIIFCollectionAPI extends \ApiBase {

	public static $smwConfigProps = [];

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		// $manifest = ( array_key_exists( 'manifest', $params ) ) ? $params['manifest'] : "";
		$id = IIIFUtils::getUrlBase() . "$_SERVER[REQUEST_URI]";

		self::$smwConfigProps = IIIFConfig::getConfigProps( $params["smwconfig"] );

		// concept takes precedence over smwquery
		if ( $params["concept"] !== "" ) {
			$concept = trim( $params["concept"] );
			$smwQueryEscaped = "((Concept:$concept))";
		} elseif( $params["smwquery"] !== "" ) {
			$smwQueryEscaped = trim( $params["smwquery"] );
		} else {
			$smwQueryEscaped = null;
		}
		$resourceItems = self::getResourceCollection( $smwQueryEscaped );

		$apiResult = $this->getResult();
		self::setupManifestCollectionV3( $apiResult, $id, $resourceItems );
	}

	/**
	 * @todo Improve configurability: name, description, logo, ...
	 */
	public static function setupManifestCollectionV3( $apiResult, $id, $manifestCollectionItems, $smwQueryEscaped = "" ) {
		// @todo not necessarily a valid assumption.
		global $wgLanguageCode;
		$lg = $wgLanguageCode;
		$items = [];
		foreach( $manifestCollectionItems as $item ) {
			$items[] = self::preprocessManifestV3( $item );
		}

		global $wgSitename;
		$requiredStatement = [
			"label" => [
				$lg => [ "Attribution" ]
			],
			"value" => [
				"en" => [ $wgSitename ]
			]
		];
		$logoImg = IIIFUtils::getUrlBase() . "/extensions/IIIF/assets/Looking-glass.png"; 
		$logo = [
			[
				"id" => $logoImg,
				"type" => "Image",
				"format" => "image/png",
				"height" => 108,
				"width" => 107
			]
		];
		$arr = array(
			"@context" => "http://iiif.io/api/presentation/3/context.json",
			"id" => $id,
			"label" => [
				$lg => [ self::$smwConfigProps['IIIFCollectionLabel'] ?? "Collection" ]
			],
			"summary" => [ 
				$lg => [ self::$smwConfigProps['IIIFCollectionSummary'] ?? "" ]
			],
			"type" => "Collection",
			"requiredStatement" => $requiredStatement,
			"items" => $items,
			"logo" => $logo
		);
		foreach( $arr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
	}

	/**
	 * Preprocess reference to resource and manifest
	 */
	public static function preprocessManifestV3( $item, $smwconfig = null ) {

		$propNames = self::getPropNamesForCollection( false );
		$manifestProp = $propNames[0];
		$labelProp = $propNames[1];

		$manifestId = $labelStr = "";
		foreach( $item as $key => $val ) {
			switch ($key) {
				case $manifestProp:
					$manifestId = $val[0];
				break;
				case $labelProp:
					$labelPage = $val[0];
					$labelStr = IIIFSMW::resolveDIWikiPage( $labelPage );
				break;
			}
		}

		// @todo
		$thumbnailUri = IIIFUtils::getUrlBase() . "/extensions/IIIF/assets/Looking-glass.png";
		$thumbnail = [
			"id" => $thumbnailUri,
			"type" => "Image",
			"format" => "image/jpeg"
		];
		 $res3 = [
			"id" => $manifestId,
			"type" => "Manifest",
			"label" => [
				"none" => [ $labelStr ]
			]
			// "thumbnail" => $thumbnail
		];
		return $res3;
	}

	/**
	 * 
	 * //$queryArgDefault = "[[Class::Subject:CODECS external links]] [[Has link type::IIIF Manifest URL]]";
	 * @param mixed $smwQueryEscaped
	 * @return array
	 */
	public static function getResourceCollection( $smwQueryEscaped = null ) {
		// Argument for all resources with a manifest - use concept instead e.g. "Resources with IIIF manifests"
		$IIIFCollectionQueryArg = self::$smwConfigProps["IIIFCollectionQueryArg"];
		
		$queryArg = ( $smwQueryEscaped == null )
			? $IIIFCollectionQueryArg
			: IIIFSMW::processSemanticQuery( $smwQueryEscaped );
		$IIIFCollectionSortProp = self::$smwConfigProps["IIIFCollectionSortProp"];

		$printout = "|named args=yes|link=none|limit=9999|sort={$IIIFCollectionSortProp}|searchlabel=";
		$propNames = self::getPropNamesForCollection( true );
		$resources = IIIFSMW::getAllObjectsForQuery( $queryArg, $printout, $propNames );

		return $resources;
	}

	public static function getPropNamesForCollection(
		$underscores = true
	): array {
		$manifestProp = self::$smwConfigProps['IIIFCollectionItemURI'];
		$labelProp = self::$smwConfigProps['IIIFCollectionItemResource'];
		if ( $underscores == true ) {
			$manifestProp = str_replace( " ", "_", $manifestProp );
			//$manifestProp = "IIIF Manifest URL";
    		$labelProp = str_replace( " ", "_", $labelProp );
		}
    	$propNames = [
        	$manifestProp,
            $labelProp
    	];
		return $propNames;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() : array {
		$arr = [
			"manifest" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
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
			],
			"concept" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			]
		];
		return $arr;
	}

}
