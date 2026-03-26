<?php

/**
 * action=iiif-range
 * API for use with the TOC creator. Can be used to convert 
 * the TOC into IIIF ranges (v2/v3) and merge them into the 
 * manifest.
 * 
 * /api.php?action=iiif-range&manifest=<...>&sourcepage=<...>&sourceslot=main
 */

namespace IIIF\API;

use MediaWiki\Api\ApiBase;
use Wikimedia\ParamValidator\ParamValidator;
use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFRangeParsers;
use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFRangeAPI extends ApiBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		$params = $this->extractRequestParams();

		// source page for ranges
		$sourcePage = $params['sourcepage'] ?? "";
		if ( $sourcePage === "" ) {
			$sourcePage = IIIFUtils::getFullpageNameFromPageID( $params['sourcepageid'] );
		}
		$sourceSlot = $params['sourceslot'];
		$sourceContentRaw = IIIFUtils::getRawContentFromPageName( $sourcePage, $sourceSlot );
		$sourceContent = json_decode( $sourceContentRaw, true );

		// Manifest
		$manifest = $params["manifest"] ?? "";
		if ( $manifest === "" ) {
			$manifest = $sourceContent["manifest"] ?? null;
			if ( $manifest == null ) {
				return;
			}
		}
		$manifestArr = $manifest !== null
			? IIIFUtils::getArrayFromJsonUrl( $manifest ) 
			: null;
		if ( $manifestArr == null && !IIIFUtils::isJson( $manifestArr ) ) {
			return;
		}

		$version = IIIFParserUtils::getPresentationAPIVersion( $manifestArr );
		if ( $version == "2" ) {
			// move @context to top position
			$manifestArr = array_splice(
				$manifestArr, 
				array_search( '@context', array_keys($manifestArr)),1 ) + $manifestArr;
		}
		$IIIFRangeParser = new IIIFRangeParsers;
		$newItems = $IIIFRangeParser->transformItemsToRanges( $manifestArr, $sourceContent );

		$manifestArr["structures"] = $newItems;
		$manifestArr["description"] = "Original manifest expanded with Structures/Ranges";
		/* custom:
		$manifestArr["meta"] = [
			"manifest" => $params['manifest'],
			"manifestVersion" => $version,
			"sourcepage" => $params['sourcepage']
		];
		*/
		$apiResult = $this->getResult();
		foreach( $manifestArr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
	}

	public function getAllowedParams(): array {
		$arr = [
			"manifest" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			"sourcepage" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
			],
			"sourcepageid" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
			],
			"sourceslot" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "main"
			]
		];
		return $arr;
	}

}
