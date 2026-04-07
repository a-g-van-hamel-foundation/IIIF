<?php

/**
 * Simple handler for OpenSeadragon (module action: iiif-osd)
 * https://www.isos.dias.ie/static/manifests/NLI_MS_G_3.json
 * 
 * @todo IIIF Presentation v3
 */

namespace IIIF\API;

use MediaWiki\Api\ApiBase;
use Wikimedia\ParamValidator\ParamValidator;
use MediaWiki\Request\DerivativeRequest;
use MediaWiki\Api\ApiMain;
use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIF\IIIFParsers\IIIFManifestParsers;
use IIIF\Special\IIIFSpecialServ;

class IIIFAPIOSDHandler extends ApiBase {

	private $manifestArr = [];
	private $canvases = null;
	private $imageInformationRequestUrls = [];
	private $canvasItems;
	// string
	private $presentationAPIVersion;
	private $imageAPIVersion;
	private $metaMessages = [];

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$manifestUrl = $params["manifest"];

		// https://iiif.io/api/image/3.0/#22-image-information-request-uri-syntax
		// defaults
		$req = "ImageInformationRequests";
		$results = [];

		switch( $req ) {
			case "ImageInformationRequests":
				if ( $manifestUrl !== null ) {
					$this->manifestArr = $this->getManifestContent( $manifestUrl );
					if ( $this->manifestArr === null ) {
						$this->metaMessages[] = "Invalid manifest";
						break;
					}

					// Check IIIF version and get canvases
					$this->presentationAPIVersion = IIIFParserUtils::getPresentationApiVersion( $this->manifestArr );
					$this->canvases = IIIFCanvasParsers::getCanvasesFromManifest( $this->manifestArr, $this->presentationAPIVersion );
					if ( $this->canvases === null ) {
						break;
					}

					$canvasParser = new IIIFCanvasParsers();
					[ $this->imageInformationRequestUrls, $this->canvasItems ] = $canvasParser->getInfoFromCanvasesForOSD( $this->canvases, $this->presentationAPIVersion );
				}
			break;
		}

		// Annotations, if any, stored on a wiki page
		$annotationJson = [];
		if ( $params["annotations"] !== "" ) {
			$content = IIIFUtils::getRawContentFromPageName( $params["annotations"], $params["slot"] );
			if ( IIIFUtils::isJson( $content ) ) {
				$annotationJson = json_decode( $content, true );
			}
		}

		$iiifManifestParser = new IIIFManifestParsers( $this->presentationAPIVersion );
		$manifestSummary = $iiifManifestParser->createSummary( $this->manifestArr );

		$res = [
			"result" => [
				"imageInformationRequests" => $this->imageInformationRequestUrls,
				"canvasItems" => $this->canvasItems,
				"manifest" => $this->manifestArr,
				"annotations" => $annotationJson,
				"summary" => $manifestSummary
			],
			"meta" => [
				"module" => "iiif-osd",
				"manifest" => $manifestUrl,
				"requested" => $req,
				//"imageApiVersion" => $this->imageApiVersion,
				"message" => $this->metaMessages
			]
		];

		$apiResult = $this->getResult();
		foreach( $res as $key => $val ) {
			$apiResult->addValue( null, $key, $val );
		}
	}

	/**
	 * Summary of getManifestContent
	 * @param string $manifestUrl
	 */
	public function getManifestContent( string $manifestUrl ) {
		$urlBase = IIIFUtils::getUrlBase();
		if ( str_starts_with( $manifestUrl, "{$urlBase}/Special:IIIFServ/" ) ) {
			$urlParts = explode( "/", str_replace( "{$urlBase}/Special:IIIFServ/", "", $manifestUrl ) );
			// Get destination URL
			$iiifSpecialServ = new IIIFSpecialServ( "IIIFServ" );
			$apiUrl = $iiifSpecialServ->getRedirectUrl( $urlParts );
			return $this->getManifestContentInternally( $apiUrl );
		} elseif( str_starts_with( $manifestUrl, $urlBase . "/api.php?action=" ) ) {
			return $this->getManifestContentInternally( $manifestUrl );
		} else {
			// Regular for remote URLs
			$manifestArr = IIIFUtils::getArrayFromJsonUrl( $manifestUrl, true );
			if ( $manifestArr === null ) {
				// Fallback method
				$manifestArr = IIIFUtils::getArrayFromJsonUrl( $manifestUrl, false );
			}
			return $manifestArr;
		}
	}

	public function getManifestContentInternally( string $url ) {
		$urlComponents = parse_url( $url );
		if ( $urlComponents == false ) {
			return [];
		}
		parse_str( $urlComponents["query"], $apiParams);
		$request = new DerivativeRequest(
			$this->getRequest(),
			$apiParams,
			true
		);
		$api = new ApiMain( $request, true );
		$api->execute();
		return $api->getResult()->getResultData();
	}

	public function getAllowedParams() : array {
		return [
			"manifest" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			//
			"manifestaccess" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false
			],
			// Name of the wiki page from which to fetch our annotations
			"annotations" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ""
			],
			// Content slot (MCR)
			"slot" => [
				ParamValidator::PARAM_TYPE => "string",
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => "main"
			]
		];
	}

}
