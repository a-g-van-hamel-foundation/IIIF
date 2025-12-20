<?php

namespace IIIF\SMW;

//use MediaWiki\MediaWikiServices;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\ResultPrinter;
use IIIF\IIIFUtils;
use IIIF\SMW\IIIFSMW2Annotations;

class GalleryResultFormatter extends ResultPrinter {

	public function __construct( string $format, bool $inline = true ) {
		parent::__construct( $format, $inline );
	}

	/**
	 * @see IResultPrinter::getName
	 * @return string
	 */
	public function getName() {
		return $this->msg( "iiif-printer-iiif-annotation-gallery-name" );
	}

	/**
	 * Supersedes getParameters
	 * @see IResultPrinter::getParamDefinitions
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		// Default params
		$definitions = parent::getParamDefinitions( $definitions );

		//$definitions[ 'searchlabel' ]->setDefault( "test..." );

		// Define additional params
		$definitions[] = [
			"name" => "layout",
			"message" => "iiif-printer-iiif-annotation-gallery-paramdesc-layout",
			"default" => "even grid"
		];
		$definitions[] = [
			"name" => "columns",
			"message" => "iiif-printer-iiif-annotation-gallery-paramdesc-columns",
			"default" => "4"
		];
		$definitions[] = [
			"name" => "mimetype",
			"message" => "iiif-printer-iiif-annotation-gallery-paramdesc-mimetype",
			"default" => "image/jpeg"
		];
		$definitions[] = [
			"name" => "template",
			"message" => "iiif-printer-iiif-annotation-gallery-paramdesc-template",
			"default" => null
		];
		return array_merge( $definitions, $this->getParameters() );
	}

	public function isDeferrable() {
		// @todo Make sure "ext.iiif.styles" gets loaded, too
		return true;
	}

	public function getResources() {
		return [
			"modules" => [ "ext.iiif.resultformat.annotationgallery" ],
			"styles" => [ "ext.iiif.styles" ]
		];
	}

	public function getResultText( QueryResult $queryResult, $outputMode ) {
		//$queryCondition = $queryResult->getQuery()->getQueryString();
		$queryCondition = $queryResult->getQueryString();

		$params = $this->params;
		$q = $queryResult->toArray();

		// external formatter for $q
		$iiifSmw2Annotations = new IIIFSMW2Annotations();
		// @todo allow to set mimetype
		$iiifSmw2Annotations->setConversionOptions( $params["template"] ?? null, $params["valuesep"] ?? ";", $params["mimetype"] );
		$items = $iiifSmw2Annotations->convert( $q, ",250" );

		// ResourceLoader
		$resources = $this->getResources();
		$this->registerResources( $resources["modules"], $resources["styles"] );

		$settings = [
			"layout" => $params["layout"],
			"columns" => intval( $params["columns"] ?? "4" )
		];

		// @todo Do not require Special:Ask in the future
		$furtherResults = "";
		if ( $this->linkFurtherResults( $queryResult ) ) {
			$SMWInfolink = $this->getFurtherResultsLink( $queryResult, SMW_OUTPUT_HTML );
			$furtherResults = $SMWInfolink->getText( SMW_OUTPUT_HTML, null );
		}

		$html = \Html::rawElement(
			"div",
			[
				"class" => "iiif-annotation-gallery",
				"data-id" => "iiif-annotation-gallery-" . rand(1000000,9999999),
				"data-items" => htmlspecialchars( json_encode( $items ) ),
				"data-settings" => htmlspecialchars( json_encode( $settings ) ),
				// @todo - for future use
				"data-query-condition" => htmlspecialchars( json_encode( $queryCondition ) )
			],
			""
		);

		$this->isHTML = true;
		return \Html::rawElement( "div", [ "class" => "iiif-annotation-gallery--wrapper" ], $html . $furtherResults );
	}

}
