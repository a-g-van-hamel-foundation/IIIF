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

	public function __construct( $name = 'IIIF' ) {
		parent::__construct( $name );
		global $IP;
		$extAssets = MediaWikiServices::getInstance()->getMainConfig()->get( 'ExtensionAssetsPath' );
		$this->extensionPath = $IP . $extAssets . "/IIIF";
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

		// Markdown pages
		switch( $subPage ) {
			case "namespace":
				$md = $this->getMarkdownDoc( "src/Special/iiif-namespace.md" );
				$outputPage->setPageTitle( "IIIF namespace" );
			break;
			case "config":
				$md = $this->getMarkdownDoc( "src/Special/config.md" );
				$outputPage->setPageTitle( "Configuration" );
			break;
			case "case-mw-images":
				$md = $this->getMarkdownDoc( "src/Special/case-mw-images.md" );
				$outputPage->setPageTitle( "Serving images from MediaWiki" );
			break;
			case "case-collections":
				$md = $this->getMarkdownDoc( "src/Special/case-collections.md" );
				$outputPage->setPageTitle( "Creating collections with Semantic MediaWiki" );
			break;
			case "case-smw-annotations":
				$md = $this->getMarkdownDoc( "src/Special/case-smw-annotations.md" );
				$outputPage->setPageTitle( "From Semantic MediaWiki data to IIIF annotations" );
			break;
			case "iiif-manifest":
				$md = $this->getMarkdownDoc( "src/Special/api-iiif-manifest.md" );
				$outputPage->setPageTitle( "API module: <code>iiif-manifest</code>" );
			break;
			case "iiif-mw-img":
				$md = $this->getMarkdownDoc( "src/Special/api-iiif-mw-img.md" );
				$outputPage->setPageTitle( "API module: <code>iiif-mw-img</code>" );
			break;
			case "iiif-mw-pres":
				$md = $this->getMarkdownDoc( "src/Special/api-iiif-mw-pres.md" );
				$outputPage->setPageTitle( "API module: <code>iiif-mw-pres</code>" );
			break;
			case "iiif-annotations":
				$md = $this->getMarkdownDoc( "src/Special/api-iiif-annotations.md" );
				$outputPage->setPageTitle( "API module: <code>iiif-annotations</code>" );
			break;
			case "iiif-collection":
				$md = $this->getMarkdownDoc( "src/Special/api-iiif-collection.md" );
				$outputPage->setPageTitle( "API module: <code>iiif-collection</code>" );
			break;
			case "iiif-wiki":
				$md = $this->getMarkdownDoc( "src/Special/api-iiif-wiki.md" );
				$outputPage->setPageTitle( "API module: <code>iiif-wiki</code>" );
			break;
			case "redirect":
				$md = $this->getMarkdownDoc( "src/Special/api-redirect.md" );
				$outputPage->setPageTitle( "Redirect service" );
			break;
			// @todo - add osd
			//"Special:IIIF/iiif-annotator
			case "iiif-annotator":
				$md = $this->getMarkdownDoc( "src/Special/iiif-annotator.md" );
				$outputPage->setPageTitle( "Annotation tool" );
			break;
			case "pf-iiif-annotator":
				$md = $this->getMarkdownDoc( "src/Special/pf-iiif-annotator.md" );
				$outputPage->setPageTitle( "Annotation tool: <code>#iiif-annotator</code>" );
			break;
			case "pf-iiif-annotator-data":
				$md = $this->getMarkdownDoc( "src/Special/pf-iiif-annotator-data.md" );
				$outputPage->setPageTitle( "Annotation tool: <code>#iiif-annotator-data</code>" );
			break;
			case "iiif-annotator-form":
				$md = $this->getMarkdownDoc( "src/Special/iiif-annotator-form.md" );
				$outputPage->setPageTitle( "Annotation tool: creating forms" );
			break;
			case "pf-iiif-get-canvases":
				$md = $this->getMarkdownDoc( "src/Special/pf-iiif-get-canvases.md" );
				$outputPage->setPageTitle( "Parser function: <code>#iiif-get-canvases</code>" );
			break;
			case "settings": 
				$md = null;
				$outputPage->setPageTitle( "IIIF: current and default settings" );
				$res = $this->getConfigSettings();
			break;
			// Other, unofficial:
			case "tify":
				$md = null;
				$res = $this->getTIFYTest( $outputPage );
			break;
			case "annotator":
				$md = null;
				$res = $this->getAnnotatorTest( $outputPage );
			break;
			// Default
			case "introduction":
			default:
				$md = $this->getMarkdownDoc( "src/Special/introduction.md" );
				$outputPage->setPageTitle( "IIIF extension" );
		}

		if ( $md !== null ) {			
			$res = "<div class='iiif-row'><div class='iiif-col iiif-col-main'>$md</div><div class='iiif-col iiif-col-sidebar'>$navMenu</div></div>";
			$outputPage->addHTML( $res );
			return;
		} else {
			$outputPage->addWikiTextAsContent( $res );
		}
	}

	private function getMarkdownDoc( $filePath ) {
		global $IP;
		$fileContents = file_get_contents( $IP . "/extensions/IIIF/{$filePath}" );
		// replace variables, here urlBase
		$urlBase = IIIFUtils::getUrlBase();
		$newFileContents = str_replace( "{urlBase}", $urlBase, $fileContents );

		$Parsedown = new Parsedown();
		$parsed = $Parsedown->text( $newFileContents );
		return $parsed;
	}


	// @todo remove when test is no longer needed
	private function getTIFYTest( $outputPage ) {
		$outputPage->addModules( [ "ext.iiif.tify" ] );
		$res = "<div id='iiif-tify-viewer' style='height: 640px'></div>";
		return $res;
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
		//$res = "<div class=\"iiif-widget\" data-id='234234234' data-manifest='https://www.isos.dias.ie/static/manifests/RIA_MS_23_P_16.json'>Testing...</div>";
		return $res;
	}

	private function buildNavMenu( $outputPage ) {
		$menuItems = [
			"Special:IIIF" => "Introduction",
			"Special:IIIF/namespace" => "IIIF namespace",
			"Configuration",
			"Special:IIIF/config" => "Configuration",
			"API usage guides",
			"Special:IIIF/case-mw-images" => "Serve images from MediaWiki",
			"Special:IIIF/case-collections" => "Create collections with SMW",
			"Special:IIIF/case-smw-annotations" => "From SMW data to IIIF annotations",
			"API modules",
			"Special:IIIF/iiif-manifest" => "iiif-manifest",
			"Special:IIIF/iiif-annotations" => "iiif-annotations",
			"Special:IIIF/iiif-mw-pres" => "iiif-mw-pres",
			"Special:IIIF/iiif-mw-img" => "iiif-mw-img",
			"Special:IIIF/iiif-collection" => "iiif-collection",
			"Special:IIIF/iiif-wiki" => "iiif-wiki",
			"Special:IIIF/redirect" => "Redirect service (Special:IIIFServ)",
			"Annotation tool",
			"Special:IIIF/iiif-annotator" => "About the annotator",
			"Special:IIIF/pf-iiif-annotator" => "<code>#iiif-annotator</code>",
			"Special:IIIF/iiif-annotator-form" => "Creating forms",
			"Special:IIIF/pf-iiif-annotator-data" => "<code>#iiif-annotator-data</code>",
			"Other",
			"Special:IIIF/pf-iiif-get-canvases" => "<code>#iiif-get-canvases</code>"
		];
		$text = "";
		foreach( $menuItems as $page => $label ) {
			if ( gettype( $page ) === "integer" ) {
				// header
				$text .= "<li class='subheading'>$label</li>";
			} else {
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

}
