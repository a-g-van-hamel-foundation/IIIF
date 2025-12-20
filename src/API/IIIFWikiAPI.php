<?php

/**
 * Returns JSON from a wiki page
 * Structure: {host}/api.php?action=iiif-wiki&format=json&id={pageid}
 */

namespace IIIF\API;

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;
use IIIF\IIIFUtils;

class IIIFWikiAPI extends \ApiBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$pageID = $params['id'] ?? "";
		// MediaWikiServices::getInstance();
		// do checks first if it exists and contains JSON!
		$content = IIIFUtils::getRawContentFromPageID( $pageID );
		if ( IIIFUtils::isJson( $content ) ) {
			$jsonArr = json_decode( $content, true );
		} else {
			$jsonArr = [];
		}

		$apiResult = $this->getResult();		
		foreach( $jsonArr as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
		// $apiResult->addValue( null, "test", $content );
	}

	/**
	* @return array
	*/
	public function getAllowedParams() : array {
		$arr = [
			"id" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => true
			],
			"format" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "json"
			]
		];
		return $arr;
	}

}
