<?php

/**
 * 
 */

namespace IIIF\SMW;

use IIIFUtils;
use IIIF\SMW\IIIFSMW;
use IIIF\IIIFParsers\IIIFImageUtils;

class IIIFSMW2Annotations {

	private $manifestURLs = [];
	private $manifests = [];
	private $valuesep = ";";
	private $template = null;
	private $mimeType = "image/jpeg";

	public function __construct() {
		//	
	}

	public function setConversionOptions( mixed $template = null, $valuesep = ";", $mimeType = null ) {
		if( $template !== null && $template !== "" ) {
			$this->template = $template;
		}
		$this->valuesep = $valuesep;
		if( $mimeType !== null && $mimeType !== "" ) {
			$this->mimeType = $mimeType;
		}
	}

	/**
	 * Accepts a queryResult and returns data 
	 * as required for the ResultFormatter
	 * @param array $queryResult
	 * @return array<array|mixed>
	 */
	public function convert(
		array $queryResult,
		string $size = ",250"
	) {
		//
		$items = [];
		foreach( $queryResult["results"] as $k => $result ) {
			
			//$manifestURL = IIIFUtils::getArrayPath( [ "printouts", "manifest", 0 ], $result, null );
			$tileSource = IIIFUtils::getArrayPath( [ "printouts", "tilesource", 0 ], $result, null );
			if ( $tileSource === null ) {
				continue;
			}
			$xywhRaw = IIIFUtils::getArrayPath( [ "printouts", "xywh", 0 ], $result, "" );
			$xywh = IIIFImageUtils::roundXywhToIntegers( $xywhRaw );
			$rotation = "0";

			// @todo - format .jpg is common but not a given
			$fileExt = IIIFImageUtils::translateMimetypeToFileExtension( $this->mimeType );
			$imgSrc = "$tileSource/$xywh/$size/$rotation/default.$fileExt";
			$imgSrcFull = "$tileSource/$xywh/full/$rotation/default.$fileExt";

			$canvasLabel = IIIFUtils::getArrayPath( [ "printouts", "canvaslabel", 0 ], $result, "" );
			$description = IIIFUtils::getArrayPath( [ "printouts", "description", 0 ], $result, "" );

			$datasetBody = [];
			//IIIFUtils::printArray( $result["printouts"]  );
			foreach( $result["printouts"] as $k => $printout ) {
				$datasetBody[$k] = IIIFSMW::simplifyPrintoutArray( $printout );
			}

			if ( $this->template !== null && $this->template !== "" ) {
				$caption = $this->createCaption( $result["printouts"], $this->template );
				// @todo maybe use recursiveTagParseFully() as a lighter alternative
				$captionHtml = IIIFUtils::convertStrToWikitext( $caption );
				$resourceLabel = null;
			} else {
				$resourceLabel = IIIFUtils::getArrayPath( [ "printouts", "resourcelabel", 0 ], $result, "" );
				if ( gettype($resourceLabel) == "array" && array_key_exists( "fulltext", $resourceLabel ) ) {
					// @todo
					$resourceLabel = $resourceLabel["displaytitle"] ?? $resourceLabel["fulltext"];
				}
				$captionHtml = $resourceLabel !== ""
					? "<div class='caption-label'>$resourceLabel - $canvasLabel</div>"
					: "<div class='caption-label'>$canvasLabel</div>";
				if ( $description !== "" ) {
					$captionHtml .= "<div class='caption-desc'>$description</div>";
				}
			}

			$items[] = [
				"tilesource" => $tileSource,
				"xywh" => $xywh,
				"src" => $imgSrc,
				"srcfull" => $imgSrcFull,
				"caption" => $captionHtml,
				"resourcelabel" => $resourceLabel,
				"canvaslabel" => $canvasLabel,
				"body" => $datasetBody
			];
			// add other stuff, too
		}
		//
		return $items;
	}

	/**
	 * Returns unprocessed template string for a caption
	 * @param mixed $printouts
	 * @param mixed $templateName
	 * @return string
	 */
	private function createCaption( $printouts, $templateName ) {
		$templateParams = [];
		foreach( $printouts as $k => $p ) {
			$templateParams[$k] = IIIFSMW::simplifyPrintoutArray( $p, "none", $this->valuesep );
		}
		$templateStr = IIIFUtils::convertArrayToWikiInstances( [ $templateParams ], "template", $templateName );
		return $templateStr;
	}

}
