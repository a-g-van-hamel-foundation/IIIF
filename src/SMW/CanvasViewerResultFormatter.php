<?php

/**
 * @link https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultPrinters/ResultPrinter.php
 * @link https://www.semantic-mediawiki.org/wiki/Writing_result_printers
 */

namespace IIIF\SMW;

//use MediaWiki\MediaWikiServices;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\ResultPrinter;
use IIIF\IIIFUtils;
use IIIF\SMW\IIIFSMW2CanvasItems;

class CanvasViewerResultFormatter extends ResultPrinter {

	private static array $services = [];

	public function __construct( string $format, bool $inline = true ) {
		//$this->service = self::$services[$format];
		parent::__construct( $format, $inline );
	}

	/**
	 * @see IResultPrinter::getName
	 * @return string
	 */
	public function getName() {
		return $this->msg( "iiif-printer-iiif-canvas-viewer-name" );
	}

	/**
	 * Supersedes getParameters
	 * @see IResultPrinter::getParamDefinitions
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions[] = [
			"name" => "template",
			"message" => "iiif-printer-iiif-canvas-viewer-paramdesc-template",
			"default" => null
		];

		return array_merge( $definitions, $this->getParameters() );
	}

	public function getResources() {
		return [
			"modules" => [ "ext.iiif.resultformat.canvasviewer" ],
			"styles" => [ "ext.iiif.styles" ]
		];
	}

	public function getResultText( QueryResult $queryResult, $outputMode ) {
		$q = $queryResult->toArray();
		$params = $this->params;
		$template = array_key_exists( "template", $params )
			? $params["template"]
			: null;
		$valueSep = $params["valuesep"] ?? ";";

		// Transform result
		$iiifSMW2CanvasItems = new IIIFSMW2CanvasItems();
		$iiifSMW2CanvasItems->setConversionOptions( $template, $valueSep );
		[ $manifestURLs, $canvases, $canvasItems, $tileSources, $annotationPages, $profiles ] = $iiifSMW2CanvasItems->convert( $q );

		$queryCondition = $queryResult->getQueryString();
		$summaryList = "";
		foreach( $manifestURLs as $url ) {
			$summaryList .= "<li><a href='$url'>$url</a></li>";
		}
		// @todo localisation
		$summary = "<div class='manifest-summary'><div class='item'>This image collection was generated from annotations on the following IIIF Manifests:</div><ul class='item iiif-manifests-urls'>$summaryList</ul></div>";

		// ResourceLoader
		$resources = $this->getResources();
		$this->registerResources( $resources["modules"], $resources["styles"] );

		if ( $this->linkFurtherResults( $queryResult ) ) {
			// May not be appropriate for this result format
		}

		$manifestStr = htmlspecialchars(json_encode($canvases));
		$html = \Html::rawElement(
			"div",
			[
				"data-id" => "iiif-canvas-viewer-" . rand(1000000,9999999),
				"class" => "iiif-annotation-canvasviewer iiif-canvas-viewer",
				"data-manifest-json" => $manifestStr,
				// @todo
				"data-manifest" => IIIFUtils::getUrlBase() . `/Special:IIIF/` . rand(1000000,9999999),
				"data-canvasitems" => htmlspecialchars(json_encode( $canvasItems ) ),
				"data-tilesources" => htmlspecialchars(json_encode( $tileSources) ),
				"data-annotationpages" => htmlspecialchars(json_encode( $annotationPages ) ),
				// See doc on Special:IIIF/iiif-canvas-viewer for the difference btw them
				"data-presentation-method" => $template !== null ? "template" : "profile",
				"data-profiles" => htmlspecialchars( json_encode( $profiles ) ),				
				"data-canvas-index" => "0",
				"data-mode" => "viewer",
				// @todo - for future use
				"data-query-condition" => htmlspecialchars( json_encode( $queryCondition ) ),
				"data-summary" => htmlspecialchars( json_encode( $summary ) )
			],
			"<div class='iiif-loader'></div>"
		);
		return \Html::rawElement( "div", [ "class" => "iiif-canvas-viewer--wrapper" ], $html );;
	}

}
