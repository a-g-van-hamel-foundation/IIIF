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

		if ( $formId === "" && $form !== "" ) {
			$formId = Title::newFromText( $form )->getId();
		}
		if ( $targetPageId === "0" && $targetPage !== "" ) {
			$targetPageId = Title::newFromText( $targetPage )->getId();
		} elseif( $targetPageId !== "0" ) {
			// @todo test
			$targetPage = Title::newFromId( $targetPageId )->getPrefixedText();
		}

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

		$res = Html::rawElement( "div", $attributes, "..." );
		return $res;
	}
}
