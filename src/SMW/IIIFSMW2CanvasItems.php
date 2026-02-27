<?php

/**
 * @todo make this more robust against incomplete data
 */

namespace IIIF\SMW;

use IIIF\IIIFUtils;
use IIIF\SMW\IIIFSMW;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIF\IIIFParsers\IIIFImageUtils;

class IIIFSMW2CanvasItems {

	private $manifestURLs = [];
	private $manifests = [];
	private $canvasIds = [];
	// only the canvases used
	private $canvases = [];
	// For reference by annotation tool
	private $canvasItems = [];
	private $tileSources = [];
	private $annotationPages = [];
	// Form profile schemas
	private $profiles = [];
	private $template = null;
	private $valuesep = ";";

	public function __construct() {
		//
	}

	public function setConversionOptions( mixed $template = null, $valuesep = ";" ) {
		if( $template !== null && $template !== "" ) {
			$this->template = $template;
		}
	}

	/**
	 * Accepts a queryResult and returns data 
	 * as required for the ResultFormatter
	 * @param array $queryResult
	 * @return array<array|mixed>
	 */
	public function convert( array $queryResult ) {
		$items = [];
		foreach( $queryResult["results"] as $k => $result ) {

			// Manifest stuff
			$manifestURL = IIIFUtils::getArrayPath( [ "printouts", "manifest", 0 ], $result, null );
			if ( $manifestURL === null ) {
				continue;
			}			
			// IIIFUtils::printArray( $result );
			if ( !in_array( $manifestURL, $this->manifestURLs ) ) {
				$this->manifestURLs[] = $manifestURL;
				$manifest = IIIFUtils::getArrayFromJsonUrl( $manifestURL );
				// may return null not array
				if ( $manifest === null ) {
					continue;
				}
				$this->manifests[$manifestURL] = $manifest;
			} else {
				$manifest = $this->manifests[$manifestURL];
			}

			//IIIFUtils::printArray( $manifest );

			// Get canvas from manifest
			$canvasId = $result["printouts"]["canvasid"][0];
			if ( !in_array( $canvasId, $this->canvasIds ) ) {
				$this->canvasIds[] = $canvasId;
				$this->tileSources[$canvasId] = IIIFUtils::getArrayPath( [ "printouts", "tilesource", 0 ], $result, "" ) . "/info.json";

				$version = IIIFParserUtils::getPresentationApiVersion( $manifest  );
				$canvases = IIIFCanvasParsers::getCanvasesFromManifest(
					$manifest,
					$version
				);

				$canvas = self::findCanvasById( $canvasId, $canvases, $version );

				if ( $canvas !== null ) {
					// V3 is what we want
					if ( $version === "2" ) {
						$canvasParser = new IIIFCanvasParsers();
						$canvas = $canvasParser->convertCanvasV2ToV3( $canvas );
					}
					$canvas["label"] = $this->createNewCanvasLabel( $canvas["label"], $manifest["label"], $manifest["@context"] );
					$this->canvases[] = $canvas;
				}
				// cf OSD
			}

			// AnnotationPages
			$tileSource = IIIFUtils::getArrayPath( [ "printouts", "tilesource", 0 ], $result, "" );
			// print_r("tileSource: " . $tileSource);
			if ( !array_key_exists( $canvasId, $this->annotationPages ) ) {
				$this->annotationPages[$canvasId] = [
					"type" => "AnnotationPage",
					"id" => $canvasId . "/annotPage",
					// To be populated below
					"items" => [],
					"references" => [
						// don't use index from printouts
						"index" => array_search($canvasId, $this->canvasIds),
						"id" => $canvasId . "/annotPage",
						"canvasId" => $canvasId,
						"canvasLabel" => $result["printouts"]["canvaslabel"][0] ?? "",
						"tileSource" => $tileSource
					]
				];
				$profileId = IIIFUtils::getArrayPath( [ "printouts", "profileid", 0 ], $result, null );
				if ( $profileId !== null ) {
					$this->annotationPages[$canvasId]["references"]["profileid"] = $profileId;
					if ( !in_array( $profileId, array_keys( $this->profiles ) ) ) {
						$profileJson = IIIFUtils::getRawContentFromPageID( $profileId, "main" );
						if ( $profileJson !== false ) {
							$this->profiles[$profileId] = json_decode( $profileJson, true );
						}
					}
				}
			}
			$annotationid = IIIFUtils::getArrayPath( [ "printouts", "id", 0 ], $result, "..." );
			$xywhRaw = IIIFUtils::getArrayPath( [ "printouts", "xywh", 0 ], $result, "" );
			// Rounding not strictly necessary for Annotorious but good practice?
			$xywh = IIIFImageUtils::roundXywhToIntegers( $xywhRaw );

			// Prepare Annotation's body (Dataset)
			$datasetBody = [
				"id" => $annotationid,
				"type" => "Dataset"
				//"description" => IIIFUtils::getArrayPath( [ "printouts", "description", 0 ], $result, "..." )
			];
			foreach( $result["printouts"] as $k => $printout ) {
				if ( !array_key_exists( 0, $printout ) ) {
					continue;
				}
				if( gettype( $printout[0] ) === "array" && isset( $printout[0]["fulltext"] ) ) {
					// data type Page
					// use "displaytitle" ?
					// @todo handle arrays
					$datasetBody[$k] = $printout[0]["fulltext"];
				} else {
					// @todo handle arrays
					$datasetBody[$k] = $printout[0];
				}
			}

			// Template
			if ( $this->template !== null ) {
				$dataset = $this->createTemplateDataset( $result["printouts"], $this->template );
				$datasetBody["templateoutput"] = IIIFUtils::convertStrToWikitext( $dataset );
			}

			// Prepare Annotation
			$this->annotationPages[$canvasId]["items"][] = [
				"id" => $annotationid,
				"type" => "Annotation",
				"target" => [
					"source" => $tileSource,
					"selector" => [ "type" => "FragmentSelector", "conformsTo" => "http://www.w3.org/TR/media-frags/", "value" => "xywh=pixel:" . strval($xywh) ]
				],
				"@context" => "http://www.w3.org/ns/anno.jsonld",
				"body" => $datasetBody
			];

			// Annotation data...todo
			// annotationPages
		}

		// Unify Annotations to V3
		$canvasParser = new IIIFCanvasParsers();
		[ $this->imageInformationRequestUrls, $this->canvasItems ] = $canvasParser->getInfoFromCanvasesForOSD( $this->canvases, "3" );

		// For now
		return [
			$this->manifestURLs,
			$this->canvases,
			$this->canvasItems,
			array_values( $this->tileSources ),
			array_values( $this->annotationPages ),
			$this->profiles
		];
	}

	public static function findCanvasById( $canvasId, $canvases, $presentationApiVersion ) {
		foreach( $canvases as $canvas ) {
			if ( $presentationApiVersion === "2" && $canvas["@id"] === $canvasId ) {
				return $canvas;
			}
			if ( $presentationApiVersion === "3" && $canvas["id"] === $canvasId ) {
				return $canvas;
			}
		}
		return null;
	}

	/**
	 * 
	 * @param mixed $canvasLabel V3
	 * @param mixed $manifestLabel V2 or V3
	 * @param string $context - Presentation API version
	 * @return array{none: array}
	 */
	private function createNewCanvasLabel( mixed $canvasLabel, mixed $manifestLabel, string $context ) {
		$languageKey = array_keys( $canvasLabel )[0];
		$canvasLabelTxt = $canvasLabel[$languageKey][0] ?? "";

		if ( $context === "http://iiif.io/api/presentation/3/context.json" ) {
			$languageKey = array_keys( $manifestLabel )[0];
			$manifestLabelTxt = $manifestLabel[$languageKey][0] ?? "";
		} else if( $context === "http://iiif.io/api/presentation/2/context.json" ) {
			$manifestLabelTxt = $manifestLabel;
		}
		return [
			$languageKey => [
				"$manifestLabelTxt - $canvasLabelTxt"
			]
		];
	}

	/**
	 * Create the unparsed wikitext for the template
	 * @param mixed $printouts
	 * @param mixed $templateName
	 */
	private function createTemplateDataset( array $printouts, string $templateName ) {
		$templateParams = [];
		foreach( $printouts as $k => $p ) {
			$templateParams[$k] = IIIFSMW::simplifyPrintoutArray( $p, "none", $this->valuesep );
		}
		
		$templateStr = IIIFUtils::convertArrayToWikiInstances( [ $templateParams ], "template", $templateName );
		return $templateStr;
	}

}
