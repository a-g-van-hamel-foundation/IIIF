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
			// standalone or windows
			"mode" => "standalone",
			// use either formid or form
			"formid" => "",
			"form" => "",
			// page and slot to save data to
			"targetpage" => "",
			"targetpageid" => "0", //todo
			"targetslot" => "main",
			// IIIF manifest and viewer to use
			"manifest" => "",
			"iiifviewer" => "tify",
			// if in 'windows' mode, allow styling of wrapper element
			"wrapperclass" => "",
			"wrapperstyle" => ""
		];
		[ $mode, $formId, $form, $targetPage, $targetPageId, $targetSlot, $manifest, $iiifViewer, $wrapperClass, $wrapperStyle ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

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
		$parser->getOutput()->addModules( [ "ext.iiif.toc" ] );

		$attributes = [
			"class" => "iiif-toc-widget",
			"data-mode" => $mode,
			"data-form-id" => $formId,
			"data-target-page" => $targetPage,
			"data-target-page-id" => $targetPageId,
			"data-target-slot" => $targetSlot,
			"data-iiif-viewer" => "false"
		];
		if ( $manifest !== "" ) {
			$attributes["data-iiif-manifest"] = $manifest;
		}
		if ( $iiifViewer !== "" ) {
			$attributes["data-iiif-viewer"] = $iiifViewer;
		}
		if ( $wrapperClass !== "" ) {
			$attributes["data-wrapper-class"] = $wrapperClass;
		}
		if ( $wrapperStyle !== "" ) {
			$attributes["data-wrapper-style"] = $wrapperStyle;
		}
		if( !empty( $customOptions ) ) {
			$attributes["data-custom-options"] = json_encode( $customOptions, JSON_INVALID_UTF8_SUBSTITUTE );
		}

		$res = Html::rawElement( "div", $attributes, "<div class='iiif-loader'></div>" );
		return $res;
	}

}
