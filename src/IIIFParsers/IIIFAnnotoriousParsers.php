<?php

/**
 * Helper methods for preparing IIIF data in OSD and Annotorious
 */

namespace IIIF\IIIFParsers;

use IIIFUtils;

class IIIFAnnotoriousParsers {

	// array|string
	private $dataMapping;
	private $sep = ";";

	private $dataModel = "W3C";

	public function __construct() {
		//$this->dataModel = "Annotorious";
	}

	public function convertAnnotationPages(
		array $annotationPages = [],
		mixed $dataMapping = "all",
		string $dataModel = "W3C",
		mixed $manifest = null
	) {
		$this->dataModel = $dataModel;
		$this->dataMapping = $dataMapping;
		$subobjects = [];

		// use array_reduce method
		foreach( $annotationPages as $annotationPage ) {
			if( array_key_exists( "references", $annotationPage ) ) {
				$refs = $annotationPage["references"];
				$canvasId = IIIFUtils::getArrayPath( [ "canvasId" ], $refs, null );
				$canvasLabel = IIIFUtils::getArrayPath( [ "canvasLabel" ], $refs, null );
				$canvasIndex = IIIFUtils::getArrayPath( [ "index" ], $refs, null );
				$tileSource = IIIFUtils::getArrayPath( [ "tileSource" ], $refs, null );
				// $items = $annotationPage["items"];
			} else {
				$canvasId = $canvasIndex = $tileSource = $canvasLabel = null;
			}

			foreach ( $annotationPage["items"] as $k => $item ) {
				switch( $this->dataModel ) {
					case "W3C":
						$dataset = IIIFUtils::getArrayPath( [ "body", 0, "Dataset" ], $item, [] );
						// Geometry. Assuming type:RECTANGLE
						$geometry = IIIFUtils::getArrayPath( [ "target", "selector", "value" ], $item, null );
						// example : "xywh=pixel:327.70835895383186,2126.9537529096556,1446.279908194182,315.7362981630413"
						if ( $geometry !== null ) {
							$xywh = str_replace( "xywh=pixel:", "", $geometry );
							$xywhArr = explode( ",", $xywh );
							$y = sprintf( '%08d', round( $xywhArr[1] ) );
						} else {
							$xywh = null;
							$y = $k;
						}
					break;
					case "Annotorious":
						$dataset = IIIFUtils::getArrayPath( [ "bodies", 0, "Dataset" ], $item, [] );
						// Assuming type:RECTANGLE
						$geometry = IIIFUtils::getArrayPath( [ "target", "selector", "geometry" ], $item, null );
						if ( $geometry !== null ) {
							$xywh = "{$geometry['x']},{$geometry['y']},{$geometry['w']},{$geometry['h']}";
							$y = sprintf( '%08d', round( $geometry['y'] ) );
						} else {
							$xywh = null;
							$y = $k;
						}
					break;
					default:
						$dataset = [];
						$xywh = null;
				}

				switch( $this->dataModel ) {
					case "W3C":
						$creator = array_key_exists( "creator", $item ) ? $item["creator"] : null;
						//$updater = IIIFUtils::getArrayPath( [ "target", "updatedBy" ], $item, null );
						$updater = null;
					break;
					case "Annotorious":
						$creator = $item["target"]["creator"];
						$updater = IIIFUtils::getArrayPath( [ "target", "updatedBy" ], $item, null );
					break;
					default:
						$creator = $updater = "";
				}

				// Set index to sort by annotation page + decimal sep + padded y
				// using 'y' in the absence of universal rules we can apply
				// @todo are negative numbers possible?
				$sortableNum = "{$canvasIndex}.$y";
				$subobjects[$sortableNum] = $this->buildTemplateInstance( $dataset, $manifest, $canvasId, $canvasIndex, $canvasLabel, $tileSource, $xywh, $creator, $updater );
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

	private function buildTemplateInstance( array $dataset, mixed $manifest, $canvasId, $canvasIndex, $canvasLabel, $tileSource, $xywh, $creator, $updater ) {
		// $dataset - usedata mapping
		$instance = [
			// dataset
			"canvasid" => $canvasId,
			"canvasindex" => $canvasIndex,
			"canvaslabel" => $canvasLabel,
			"tilesource" => str_replace( "/info.json", "", $tileSource ),
			"xywh" => $xywh, // region
			"creator" => $creator,
			"updater" => $updater
		];
		if ( $manifest !== null ) {
			$instance["manifest"] = $manifest;
		}

		// Map parameters as in TemplateFunc::TFConvert
		$dataArr = [];
		if ( $this->dataMapping === "all" ) {
			// Reproduce all parameters verbatim
			$dataArr = $dataset;
		} elseif( $this->dataMapping !== null ) {
			// Map to different parameters
			$dataArr = [];
			$dataList = explode( ',', $this->dataMapping );
			foreach ($dataList as $d) {
				$pair = explode('=', trim($d), 2);
				$from = trim( $pair[0] );
				$to = ( array_key_exists( 1, $pair )) ? trim( $pair[1] ) : "";
				$dataArr[$from] = $to;
			}
		}

		// Assign data to instance
		foreach( $dataArr as $k => $v ) {
			if( gettype($v) === "array" ) {
				$instance[$k] = implode( $this->sep, $v );
			} else {
				$instance[$k] = $v;
			}
		}
		return $instance;
	}

	/**
	 * @todo ?
	 * @param mixed $annotationPages
	 * @return void
	 */
	private function resortAnnotationPages( $annotationPages ) {
		//$annotationPages
	}

}
