<?php

/**
 * A MediaWiki API which accepts a IIIF manifest and annotation data
 * and returns a new IIIF-compliant JSON-LD manifest, with annotations merged in
 * Supports IIIF Presentation API v2 and v3
 * See extension.json for globals
 * 
 * @todo Methods to do with merging belong in IIIFParsers namespace
 * @todo Depreciate smwquery
 * @todo Allow for multiple wgIIIFAnnotTextProps and separate resulting vals with '<br>'
 * @todo Allow for other sets of annotations through 'smwquery=((Foo::@))' to override default.
 * https://doc.wikimedia.org/mediawiki-core/master/php/classApiBase.html
 */

namespace IIIF\API;

use Wikimedia\ParamValidator\ParamValidator;
use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFManifestAPI extends \ApiBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * Inherited method
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$manifest = $params['manifest'];
		$manifestArr = $manifest !== null
			? IIIFUtils::getArrayFromJsonUrl( $manifest ) : null;
		if ( $manifestArr == null ) {
			return;
		}

		$currentId = IIIFUtils::getUrlBase() . "$_SERVER[REQUEST_URI]";
		// Get version number of IIIF Presentation API
		$presVersion = IIIFParserUtils::getPresentationApiVersion( $manifestArr );

		$obj = $params["obj"];
		$smwQueryRaw = $params['smwquery'];
		//$smwQueryProcessed = ( $smwQueryRaw == null ) ? "" : IIIFSMW::processSemanticQuery( $smwQueryRaw, $obj );
		$smwConfig = $params["smwconfig"];
		$annotPageId = $params["annotid"] ?? false;
		$newManifestArr = $manifestArr; // default

		if ( $smwConfig !== "" && $obj !== "" ) {
			$newManifestArr = self::mergeManifestWithAnnotationsFromConfig( $manifestArr, $obj, $smwConfig, $smwQueryRaw, $currentId, $presVersion );
		} elseif ( $smwQueryRaw !== "" || $obj !== "" ) {
			// print_r( "obj: $obj / smwQueryRaw: $smwQueryRaw " );
			$newManifestArr = self::mergeManifestWithAnnotationsFromQuery( $manifestArr, $obj, $smwQueryRaw, $currentId, $presVersion );
		} elseif( $annotPageId !== false ) {
			// Get annotations from JSON in wiki page
			$newManifestArr = self::mergeManifestWithAnnotationsFromPage( $manifestArr, $annotPageId, $currentId, $presVersion );
			// https://codecs.codicology.eu/api.php?action=iiif-annotations&format=jsonfm&v=2&annotid=65239&canvas=https://digitalcollections.tcd.ie/concern/works/2801pk516/manifest/canvas/v405sd539
		}

		$apiResult = $this->getResult();
		foreach( $newManifestArr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}

	}

	/**
	 * Merge annotations based on query from config into IIF manifest.
	 * If $smwConfig is null, use global config
	 */
	private static function mergeManifestWithAnnotationsFromConfig(
		array $manifestArr,
		string $obj,
		string $smwConfig,
		string $smwQueryRaw,
		string $currentId,
		string $presVersion
	) {
		$urlObject = $obj !== "" ? rawurlencode( $obj ) : "";
		// suppose it's a page ID, @todo - check it exists!
		if ( $smwConfig !== "" ) {
			$smwConfigURLParam = "&smwconfig=" . rawurlencode( $smwConfig );
		} elseif( $smwQueryRaw !== "" ) {
			// @todo
			$smwQueryRaw = false;
			$smwConfigURLParam = $smwQueryRaw == false ? "" : "&smwquery=" . rawurlencode( $smwQueryRaw );
		} else {
			$smwConfigURLParam = "";
		}
		$annotationsId = IIIFUtils::getUrlBase() . "/api.php?action=iiif-annotations&format=json{$smwConfigURLParam}&obj={$urlObject}&v={$presVersion}";

		$newManifestArr = [];
		if ( $presVersion == '3' ) {
			// Append to existing annotations
			$annotationArr[] = [
				"id" => $annotationsId,
				"type" => "AnnotationPage"
			];
			$newManifestArr = self::refAnnotationsV3( $manifestArr, $annotationArr, $currentId );
		} elseif ( $presVersion == '2' ) {
			// Append to existing annotations
			$annotationArr[] = [
				"@id" => $annotationsId,
				"@type" => "sc:AnnotationList"
			];
			$newManifestArr = self::refAnnotationsV2( $manifestArr, $annotationArr, $currentId );
		}
		return $newManifestArr;
	}

	/**
	 * Merge annotations from query into manifest.
	 * @todo  Will likely be depreciated in favour of smwconfig.
	 */
	private static function mergeManifestWithAnnotationsFromQuery( 
		$manifestArr,
		$obj,
		$smwQueryRaw,
		$currentId,
		$presVersion
	) {	
		$urlObject = $obj !== "" ? rawurlencode( $obj ) : "";

		$smwQueryURLParam = ( $smwQueryRaw == false ) ? "" : "&smwquery=" . rawurlencode( $smwQueryRaw );
		$annotationsId = IIIFUtils::getUrlBase() . "/api.php?action=iiif-annotations&format=json&obj={$urlObject}&v={$presVersion}{$smwQueryURLParam}";

		// Append to existing annotations
		if ( $presVersion == '3' ) {	
			$annotationArr[] = [
				"id" => $annotationsId,
				"type" => "AnnotationPage"
			];
			$newManifestArr = self::refAnnotationsV3( $manifestArr, $annotationArr, $currentId );
		} elseif ( $presVersion == '2' ) {
			$annotationArr[] = [
				"@id" => $annotationsId,
				"@type" => "sc:AnnotationList"
			];
			$newManifestArr = self::refAnnotationsV2( $manifestArr, $annotationArr, $currentId );
		}
		return $newManifestArr;
	}

	/**
	 * Merges annotations from JSON in wiki page into the IIIF manifest.
	 * @param array $manifestArr
	 * @param string $annotPageId
	 * @param string $currentId
	 * @param string $presVersion
	 * @return array
	 */
	private static function mergeManifestWithAnnotationsFromPage( 
		array $manifestArr,
		string $annotPageId,
		string $currentId,
		string $presVersion = '2'
	) {
		$annotationsId = IIIFUtils::getUrlBase() . "/api.php?action=iiif-annotations&format=json&annotid={$annotPageId}&v={$presVersion}";

		$newManifestArr = [];
		// Append to existing annotations
		if ( $presVersion == '3' ) {
			$annotationArr[] = [
				"id" => $annotationsId,
				"type" => "AnnotationPage"
			];
			$newManifestArr = self::refAnnotationsV3( $manifestArr, $annotationArr, $currentId );
		} elseif ( $presVersion == '2' ) {
			$annotationArr[] = [
				"@id" => $annotationsId,
				"@type" => "sc:AnnotationList"
			];
			$newManifestArr = self::refAnnotationsV2( $manifestArr, $annotationArr, $currentId );
		}
		return $newManifestArr;
	}

	/**
	* @return array
	*/
	public function getAllowedParams() : array {
		$arr = [
			"manifest" => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			"obj" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			],
			"smwconfig" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			],
			"smwquery" => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			],
			"annotid" => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
		return $arr;
	}

	/**
	 * Merge referenced annotations into manifest and return new manifest as array
	 * W3C Web Annotations for IIIF Presentaiton API v3
	 * @param array $manifestArr
	 * @param array $annotationArr
	 * @param string $currentId
	 * 
	 * @return array
	 */
	public static function refAnnotationsV3( $manifestArr, $annotationArr, $currentId ) {
		// maybe add original id to "provider"
		$manifestArr['id'] = $currentId;
		// Append :
		foreach ( $annotationArr as $val ) {
			$manifestArr['items'][0]['annotations'][] = $val;
		}
		return $manifestArr;
	}

	/**
	 * Merge referenced annotations into manifest and return new manifest as array
	 * OpenAnnotation for IIIF Presentation API v2
	 * 'otherContent' is similar to 'associations' but is associated with sc:Canvas
	 * @todo show only those annotationLists that correspond to canvasid
	 * 
	 * @param array $manifestArr
	 * @param array $annotationArr
	 * @param string $currentId
	 * 
	 * @return array
	 */
	public static function refAnnotationsV2( $manifestArr, $annotationArr, $currentId ) {
		$manifestArr['@id'] = $currentId;
		$canvasesArr = $manifestArr['sequences'][0]['canvases'];
		foreach ( $canvasesArr as $key => $val ) {
			$canvasId = $val["@id"];
			// adjust $annotationsId by appending $canvasId
			$newAnnotationArr = $annotationArr;
			$newAnnotationArr[0]["@id"] = $annotationArr[0]["@id"] . "&canvas={$canvasId}";
			$manifestArr['sequences'][0]['canvases'][$key]["otherContent"] = $newAnnotationArr;
		}
		return $manifestArr;
	}

	/**
	 * Get Presentation API version number from IIIF manifest
	 * @deprecated Moved to IIIFParserUtils
	 */
	public static function getPresentationApiVersion( $obj ) {
		return IIIFParserUtils::getPresentationApiVersion( $obj );
	}

}
