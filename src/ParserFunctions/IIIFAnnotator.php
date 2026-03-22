<?php

namespace IIIF\ParserFunctions;

use MediaWiki\Parser\Parser;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Html\Html;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;

class IIIFAnnotator {

	/**
	 * Parser function #iiif-annotator
	 * @return void
	 */
	public function runIIIFAnnotator( $parser, $frame, $params ) {

		$paramsAllowed = [
			"manifest" => null,
			"profile" => null,
			"profileid" => null,
			"target" => null,
			"targetslot" => null,
			"canvasindex" => "0",
			"mode" => "annotator"
		];
		list( $manifest, $profile, $profileId, $target, $targetSlot, $canvasIndex, $mode ) = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );
		$contentModel = $target !== null && str_starts_with( $target, "IIIF:" ) ? "iiifjson" : "json";

		// Parameters starting with @...
		$customOptions = [];
		foreach( $params as $param ) {
			$paramExpanded = $frame->expand( $param );
			$keyValPair = explode('=', $paramExpanded, 2);
			$paramName = trim( $keyValPair[0] );
			if ( substr( $paramName, 0, 1 ) === '@' && array_key_exists( 1, $keyValPair ) ) {
				$customOptions[$paramName] = trim( $keyValPair[1] );
			}
		}

		$outputPage = $parser->getOutput();
		$outputPage->addModules( [ "ext.iiif.annotator" ] );
		$outputPage->addModuleStyles( [ "ext.iiif.styles" ] );
		// @todo remove default
		$attribs = [
			"class" => "iiif-widget iiif-annotator-widget",
			"data-id" => rand(1000000,9999999),
			"data-manifest" => $manifest,
			"data-profile" => $profile,
			"data-profile-id" => $profileId,
			"data-target" => $target,
			"data-target-content-model" => $contentModel,
			"data-canvas-index" => $canvasIndex,
			"data-mode" => $mode
		];
		if( !empty( $customOptions ) ) {
			$attribs["data-custom-options"] = json_encode( $customOptions, JSON_INVALID_UTF8_SUBSTITUTE );
		}

		// MCR
		if ( $targetSlot !== null && $targetSlot !== ""
			&& ExtensionRegistry::getInstance()->isLoaded( 'WSSlots' ) ) {
			// @todo Maybe add check if slot is valid?
			$attribs["data-target-slot"] = $targetSlot;
		}

		$res = Html::rawElement(
			"div",
			$attribs,
			"<div class='iiif-loader'></div>"
		);
		return $res;
	}

}
