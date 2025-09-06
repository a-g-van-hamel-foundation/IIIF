<?php

/**
 * Provides documentation about the extension
 * in Markdown pages.
 * Markdown pages may use one variable (`urlBase`).
 *
 * @author: D. Groenewegen
 * @file
 * @ingroup
 */

namespace IIIF\Special;

use \Parsedown;
use MediaWiki\MediaWikiServices;
use IIIFUtils;

class IIIFSpecial extends \SpecialPage {

	private $extensionPath;
	private $toc = [];

	public function __construct( $name = 'IIIF' ) {
		parent::__construct( $name );
		global $IP;
		$extAssets = MediaWikiServices::getInstance()->getMainConfig()->get( 'ExtensionAssetsPath' );
		$this->extensionPath = $IP . $extAssets . "/IIIF";
		$this->setTableOfContents();
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() {
		return false;
	}

	public function execute( $subPage ) {
		$outputPage = $this->getOutput();
		// or ? $this->getContext()->getOutput();
		$res = '';
		$this->setHeaders();
		$outputPage->addModuleStyles( [ "ext.iiif.styles" ] );
		$navMenu = $this->buildNavMenu( $outputPage );

		// Alternative pages not part of the documentation
		if ( $subPage === "settings" ) {
			$md = null;
			$outputPage->setPageTitle( "IIIF: current and default settings" );
			$res = $this->getConfigSettings();
			$outputPage->addWikiTextAsContent( $res );
			return;
		} elseif ( $subPage === "annotator" ) {
			$md = null;
			$res = $this->getAnnotatorTest( $outputPage );
			$outputPage->addWikiTextAsContent( $res );
			return;
		}

		// Markdown pages
		if ( array_key_exists( $subPage, $this->toc ) ) {
			$currentPage = $this->toc[$subPage];
		} else {
			$currentPage = $this->toc["introduction"];
		}
		$md = $this->getMarkdownDoc( $currentPage["file"] );
		if ( $md === null ) {
			return;
		}
		$outputPage->setPageTitle( $currentPage["pagetitle"] );

		$res = "<div class='iiif-row'><div class='iiif-col iiif-col-main'>$md</div><div class='iiif-col iiif-col-sidebar'>$navMenu</div></div>";
		$outputPage->addHTML( $res );
		return;
	}

	private function getMarkdownDoc( $filePath ) {
		$fileContents = file_get_contents( $this->extensionPath . "/{$filePath}" );
		// replace variables, here urlBase
		$urlBase = IIIFUtils::getUrlBase();
		$newFileContents = str_replace( "{urlBase}", $urlBase, $fileContents );

		$Parsedown = new Parsedown();
		$parsed = $Parsedown->text( $newFileContents );
		return $parsed;
	}

	private function buildNavMenu( $outputPage ) {
		$text = "";
		foreach( $this->toc as $k => $tocItem ) {
			if( $tocItem["type"] === "subheading"  ) {
				$label = $tocItem["menutitle"] ?? "";
				$text .= "<li class='subheading'>$label</li>";
			} else {
				$page = "Special:IIIF/" . $tocItem["subpage"] ?? "";
				$label = $tocItem["menutitle"] ?? "";
				$text .= "<li>[[$page|$label]]</li>";
			}
		}		
		$res = "<ul class='iiif-list-group'><li class='heading'>IIIF extension</li>$text</ul>";
		return $outputPage->parseAsContent( $res, true );
	}

	/**
	 * Get a list of default and current config settings.
	 * @return string
	 */
	private function getConfigSettings() {
		$extensionJsonFile = $this->extensionPath . "/extension.json";
		if ( file_exists( $extensionJsonFile ) ) {
			$fileContents = file_get_contents( $extensionJsonFile );
			if ( $fileContents == null ) {
				return "";
			}
		}
		$ext = json_decode( $fileContents, true );
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();

		$res = "";
		foreach( $ext["config"] as $k => $v ) {			
			$descr = $v['description'] ?? "";
			$val = $v['value'] ?? null;
			$item = "<div style='font-size:smaller'>// $descr</div>";
			$item .= gettype( $v['value'] ) === "boolean"
				? "default: <code><nowiki>\$wg{$k} = " . ( $v['value'] ? "true" : "false" ) . ";</nowiki></code>\n"
				: "default: <code><nowiki>\$wg{$k} = \"{$v['value']}\";</nowiki></code>\n";
			$currentSetting = $mainConfig->get( $k );
			$item .= gettype( $currentSetting ) === "boolean"
				? "current: <code><nowiki>\$wg{$k} = " . ( $currentSetting ? "true" : "false" ) . ";</nowiki></code>\n"
				: "current: <code><nowiki>\$wg{$k} = \"$currentSetting\";</nowiki></code>\n";
			$res .= "<div style='margin-bottom:1em;'>" . $item . "</div>";
		}
		return "<div>" . $res . "</div>";
	}

	private function setTableOfContents() {
		$tocFile = $this->extensionPath . "/src/Special/toc.json";
		if ( !file_exists( $tocFile ) ) {
			return;
		}
		$jsonContents = file_get_contents( $tocFile );
		$toc = json_decode( $jsonContents, true );
		if ( $toc !== false ) {
			$this->toc = $toc;
		}
	}

	// @todo remove when test is no longer needed
	private function getAnnotatorTest( $outputPage ) {
		$outputPage->addModules( [ "ext.iiif.annotator" ] );
		$manifest = "https://www.isos.dias.ie/static/manifests/RIA_MS_23_P_16.json";
		$manifest = "https://www.isos.dias.ie/static/manifests/RIA_MS_D_iv_2.json";
		$attribs = [
			"class" => "iiif-widget",
			"data-id" => rand(1000000,9999999),
			"data-manifest" => $manifest
		];
		$res = \Html::rawElement(
			"div",
			$attribs,
			"Testing"
		);
		return $res;
	}

}
