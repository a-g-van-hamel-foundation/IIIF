<?php

namespace IIIF\ParserFunctions;

use MediaWiki\Parser\Parser;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Title\Title;
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
			"targetid" => null,
			"targetslot" => null,
			"canvasindex" => "0",
			"mode" => "annotator"
		];
		list( $manifest, $profile, $profileId, $target, $targetId, $targetSlot, $canvasIndex, $mode ) = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );
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

		if ( $profileId !== null && $profile == null ) {
			$profileTitle = Title::newFromId( $profileId );
			if ( $profileTitle !== null ) {
				$profile = $profileTitle->getPrefixedText();
			}
		} elseif( $profile !== null && $profileId == null ) {
			$profileTitle = Title::newFromText( $profile );
			$profileId = $profileTitle !== null ? $profileTitle->getId() : "0";
		}

		if ( $targetId !== null && $target == null ) {
			$targetTitle = Title::newFromId( $targetId );
			if ( $targetTitle !== null ) {
				$target = $targetTitle->getPrefixedText();
			}
		} elseif ( $target !== null && $targetId == null ) {
			$targetTitle = Title::newFromText( $target );
			$targetId = $targetTitle !== null ? $targetTitle->getId() : "0";
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
			"data-target-id" => $targetId,
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

	/**
	 * Documentation according to TemplateData guidelines.
	 * 
	 * MediaWiki itself offers no specs for describing a parser 
	 * function's parameters but TemplateData is considering
	 * taking on this role.
	 * 
	 * @link https://phabricator.wikimedia.org/T54607
	 */
	public function getTemplateData(): array {
		return [
			"description" => "Parser function for launching the IIIF annotation tool",
			"format" => "block",
			"params" => [
				"manifest" => [
					"label" => "IIIF manifest URL",
					"description" => "IIIF manifest URL.",
					"type" => "url",
					"required" => true
				],
				"profile" => [
					"label" => "profile page",
					"description" => "The pagename of the associated form profile in the IIIF namespace (see Special:IIIF). If no profile is provided, the form will offer a textarea with 'description' as its input name. Use either 'profile' or 'profileid'.",
					"type" => "wiki-page-name"
				],
				"profileid" => [
					"label" => "profile page ID",
					"description" => "The page ID of the associated form profile in the IIIF namespace (see Special:IIIF). If no profile is provided, the form will offer a textarea with 'description' as its input name. Use either 'profile' or 'profileid'.",
					"type" => "number"
				],
				"target" => [
					"label" => "target page",
					"description" => "Full name of the wiki page to be used for storing IIIF annotations in JSON. Use either this or the 'targetid' parameter. You are free to use the IIIF namespace. See also 'targetslot'.",
					"type" => "wiki-page-name"
				],
				"targetid" => [
					"label" => "target page ID",
					"description" => "Page ID of of the wiki page to be used for storing IIIF annotations in JSON. Use either this or the 'target' parameter. See also 'targetslot'.",
					"type" => "number"
				],
				"targetslot" => [
					"label" => "target slot",
					"description" => "The content slot of the wiki page to be used for storing IIIF annotations in JSON. Defaults to 'main'. Using a slot other than 'main' requires that the WSSlots extension is installed.",
					"type" => "line",
					"default" => "main"
				],
				"canvasindex" => [
					"label" => "canvas index",
					"description" => "Index of the canvas that should be opened when the viewer initialises. The viewer defaults to the first canvas.",
					"type" => "line"
				],
				"mode" => [
					"label" => "mode",
					"description" => "Optionally, set this to 'viewer' to load a presentation-only version. Instead of a form, it will offer an overview of previously entered data.",
					"type" => "line"
				]
			]
		];
	}

}
