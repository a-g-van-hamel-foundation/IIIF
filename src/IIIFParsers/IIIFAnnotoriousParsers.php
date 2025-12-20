<?php

/**
 * Helper methods for preparing IIIF data in OSD and Annotorious
 */

namespace IIIF\IIIFParsers;

use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFAnnotoriousParsers {

	// array|string
	private $dataMapping = "all";
	// Separator for multi-valued data in Dataset
	private $sep = ";";
	private $dataModel = "W3C";	

	public function __construct() {
		//$this->dataModel = "Annotorious";
	}

	// in progress
	public function setOptions(
		mixed $sep = null,
		mixed $dataModel = null
	) {
		if ( $sep !== null ) {
			$this->sep = $sep;
		}
		//$dataMapping
		if ( $dataModel !== null ) {
			$this->dataModel = $dataModel;
		}
	}

	public function convertAnnotationPages(
		array $annotationPages = [],
		mixed $dataMapping = "all",
		string $dataModel = "W3C",
		mixed $manifest = null,
		mixed $profileId = null,
		mixed $imageMimeType = null
	) {
		$this->dataModel = $dataModel;
		$this->dataMapping = $dataMapping;
		$subobjects = [];

		// use array_reduce method
		foreach( $annotationPages as $annotationPage ) {
			if( array_key_exists( "references", $annotationPage ) ) {
				$refs = $annotationPage["references"];
				$canvasId = IIIFUtils::getArrayPath( [ "canvasId" ], $refs, null );
				$canvasLabel = IIIFUtils::getArrayPath( [ "canvasLabel" ], $refs, "" );
				$canvasIndex = IIIFUtils::getArrayPath( [ "index" ], $refs, null );				
				$tileSource = IIIFUtils::getArrayPath( [ "tileSource" ], $refs, null );
				// $canvasWidth = IIIFUtils::getArrayPath( [ "" ], $refs, null );
				// $canvasHeight = IIIFUtils::getArrayPath( [ "" ], $refs, null );
				// format
				// $items = $annotationPage["items"];
			} else {
				$canvasId = $canvasIndex = $tileSource = $canvasLabel = null;
			}

			foreach ( $annotationPage["items"] as $k => $item ) {
				switch( $this->dataModel ) {
					case "W3C":
						$annotationId = $item["id"] ?? "";
						// @todo Ultimately, remove legacy structure with "Dataset" node
						$dataset = IIIFUtils::getArrayPath( [ "body", 0, "Dataset" ], $item, null )
							?? IIIFUtils::getArrayPath( [ "body", 0 ], $item, null )
							?? [];
						// Geometry. Assuming type:RECTANGLE
						$geometry = IIIFUtils::getArrayPath( [ "target", "selector", "value" ], $item, null );
						// example : "xywh=pixel:327.70835895383186,2126.9537529096556,1446.279908194182,315.7362981630413"
						if ( $geometry !== null ) {
							$xywhRaw = str_replace( "xywh=pixel:", "", $geometry );
							$xywh = IIIFImageUtils::roundXywhToIntegers( $xywhRaw );
							$xywhArr = explode( ",", $xywh );
							$y = sprintf( "%08d", round( $xywhArr[1] ) );
						} else {
							$xywh = "";
							$y = $k;
						}
					break;
					case "Annotorious":
						$annotationId = $item["id"] ?? "";
						$dataset = IIIFUtils::getArrayPath( [ "bodies", 0, "Dataset" ], $item, [] );
						// Assuming type:RECTANGLE
						$geometry = IIIFUtils::getArrayPath( [ "target", "selector", "geometry" ], $item, null );
						if ( $geometry !== null ) {
							$xywhRaw = "{$geometry['x']},{$geometry['y']},{$geometry['w']},{$geometry['h']}";
							$xywh = IIIFImageUtils::roundXywhToIntegers( $xywhRaw );
							$y = sprintf( '%08d', round( $geometry['y'] ) );
						} else {
							$xywh = "";
							$y = $k;
						}
					break;
					default:
						$dataset = [];
						$xywh = $annotationId = "";
				}

				switch( $this->dataModel ) {
					case "W3C":
						$creatorEntry = array_key_exists( "creator", $item ) ? $item["creator"] : "";
						// @todo Ultimately, remove legacy method where creator can be a string
						$creator = gettype($creatorEntry) === "string"
							? $creatorEntry
							: $creatorEntry["id"] ?? "";
						$timeCreated = array_key_exists( "created", $item ) ? $item["created"] : "";
						$timeModified = array_key_exists( "modified", $item ) ? $item["modified"] : $timeCreated;
						//$updater = IIIFUtils::getArrayPath( [ "target", "updatedBy" ], $item, null );
						$updater = "";
					break;
					case "Annotorious":
						// Legacy
						$creator = gettype($item["target"]["creator"] ) === "string" ? $item["target"]["creator"] : $item["target"]["creator"]["id"];
						$timeCreated = $timeModified = "";
						$updater = IIIFUtils::getArrayPath( [ "target", "updatedBy" ], $item, "" );
					break;
					default:
						$creator = $updater = $timeCreated = $timeModified = "";
				}

				// Set index to sort by annotation page + decimal sep + padded y
				// using 'y' in the absence of universal rules we can apply
				// @todo are negative numbers possible?
				$sortableNum = "{$canvasIndex}.$y";

				// @todo
				$instanceData = [
					"dataset" => $dataset,
					"canvasid" => $canvasId,
					"canvasindex" => $canvasIndex,
					"canvaslabel" => $canvasLabel,
					"mimetype" => $imageMimeType,
					"tilesource" => str_replace( "/info.json", "", $tileSource ),
					"annotationid" => $annotationId ?? "",
					"xywh" => $xywh ?? "",
					"creator" => $creator ?? "",
					"updater" => $updater ?? "",
					"timecreated" => $timeCreated ?? "",
					"timemodified" => $timeModified ?? "",
					"profileid" => $profileId ?? ""
				];
				if ( $manifest !== null ) {
					$instanceData["manifest"] = $manifest;
				}
				$subobjects[$sortableNum] = $this->buildTemplateInstance( $instanceData );
			}
		}

		// sequentialise and add index as a value
		ksort( $subobjects );
		$subobjects = array_values($subobjects);
		$res = [];
		foreach ( $subobjects as $k => $v ) {
			$v["index"] = $k;
			$res[] = $v;
		}
		return $res;
	}

	private function buildTemplateInstance(
		$instance
	) {
		// $dataset - usedata mapping

		// Map parameters as in TemplateFunc::TFConvert
		$dataArr = [];
		if ( $this->dataMapping === "all" ) {
			// Reproduce all parameters verbatim
			$dataArr = $instance["dataset"];
		} elseif( $this->dataMapping !== null ) {
			// Map to different parameters
			$dataArr = [];
			$dataList = explode( ',', $this->dataMapping );
			foreach ($dataList as $d) {
				$pair = explode('=', trim($d), 2);
				$from = trim( $pair[0] );
				$to = array_key_exists( 1, $pair ) ? trim( $pair[1] ) : "";
				$dataArr[$from] = $to;
			}
		}

		// Assign data to instance
		foreach( $dataArr as $k => $v ) {
			if( gettype($v) === "array" ) {
				// multi-valued data
				$instance[$k] = implode( $this->sep, $v );
			} else {
				$instance[$k] = $v;
			}
		}

		unset($instance["dataset"]);
		return $instance;
	}

	/**
	 * @todo ???
	 * @param mixed $annotationPages
	 * @return void
	 */
	private function resortAnnotationPages( $annotationPages ) {
		//$annotationPages
	}

}
