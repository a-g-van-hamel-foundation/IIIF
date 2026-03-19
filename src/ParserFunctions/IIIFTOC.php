<?php

/**
 * Parser fnction for the TIFY viewer.
 * Added in v0.6
 */

namespace IIIF\ParserFunctions;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;

class IIIFTOC {
	public function run( Parser $parser, PPFrame $frame, $params ) {
		$paramsAllowed = [
			"lib" => "vue-draggable-plus",
			// use either formid or form
			"formid" => "",
			"form" => "",
			// page to save data to
			"targetpage" => "",
			"targetpageid" => "0", //todo
			"targetslot" => "main",
			"manifest" => "",
		];
		[ $lib, $formId, $form, $targetPage, $targetPageId, $targetSlot, $manifest ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

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

		if ( $formId === "" && $form !== "" ) {
			$formId = Title::newFromText( $form )->getId();
		}
		if ( $targetPageId === "0" && $targetPage !== "" ) {
			$targetPageId = Title::newFromText( $targetPage )->getId();
		} elseif( $targetPageId !== "0" ) {
			// @todo test
			$targetPage = Title::newFromId( $targetPageId )->getPrefixedText();
		}

		$parser->getOutput()->addModuleStyles( [ "ext.iiif.styles" ] );
		if ( $lib === "vue-draggable-plus" ) {
			$parser->getOutput()->addModules( [ "ext.iiif.toc" ] );
		} else {
            // deprecated
			$parser->getOutput()->addModules( [ "ext.iiif.draggable" ] );
		}

		$attributes = [
			"class" => "iiif-toc-widget",
			"data-form-id" => $formId,
			"data-target-page" => $targetPage,
			"data-target-page-id" => $targetPageId,
			"data-target-slot" => $targetSlot
		];
		if ( $manifest !== "" ) {
			$attributes["data-iiif-manifest"] = $manifest;
		}
		if( !empty( $customOptions ) ) {
			$attributes["data-custom-options"] = json_encode( $customOptions, JSON_INVALID_UTF8_SUBSTITUTE );
		}

		$res = Html::rawElement( "div", $attributes, "<div class='iiif-loader'></div>" );
		return $res;
	}

}
