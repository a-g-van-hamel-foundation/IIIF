<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\OutputPage;
use MediaWiki\ParserOutput;
use MediaWiki\PPFrame;

class IIIFHooks {

	public static function onParserFirstCallInit( Parser $parser ) {
		$flags = Parser::SFH_OBJECT_ARGS;
		$parser->setFunctionHook( "iiif-get-canvases", [ "IIIF\ParserFunctions\IIIFGetCanvases", "runGetCanvasDataForTemplate" ], $flags );
		$parser->setFunctionHook( "iiif-annotator", [ "IIIF\ParserFunctions\IIIFAnnotator", 'runIIIFAnnotator' ], $flags );
		$parser->setFunctionHook( "iiif-annotator-data", [ "IIIF\ParserFunctions\IIIFAnnotatorData", 'runGetAnnotationDataForTemplate' ], $flags );
		return true;
	}

	public static function onRegister() {
		// Must match the name used in the 'ContentHandlers' section of extension.json
		define( "CONTENT_MODEL_IIIF_JSON", "iiifjson" );
	}

	public static function onContentHandlerDefaultModelFor( Title $title, &$model ) {		
		if ( $title->getNamespace() === NS_IIIF ) {
			$model = CONTENT_MODEL_IIIF_JSON;
			return false;
		}
		return true;
	}

	/**
	 * Add links to special page of AdminLinks extension
	 * 
	 * @param ALTree &$adminLinksTree
	 * @return bool
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ): bool {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Admin Links' ) == false ) {
			return true;
		}
		
		$linkSection = $adminLinksTree->getSection( 'CODECS' );
		if ( is_null( $linkSection ) ) {
			$section = new ALSection( 'CODECS' );
			$adminLinksTree->addSection(
				$section,
				wfMessage( 'adminlinks_general' )->text()
			);
			$linkSection = $adminLinksTree->getSection( 'CODECS' );
			$extensionsRow = new ALRow( 'extensions' );
			$linkSection->addRow( $extensionsRow );
		}

		$extensionsRow = $linkSection->getRow( 'extensions' );

		if ( is_null( $extensionsRow ) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$linkSection->addRow( $extensionsRow );
		}
		
		global $wgScript;
		$realUrl = str_replace( '/index.php', '', $wgScript ) . '/index.php';
		$extensionsRow->addItem(
			ALItem::newFromExternalLink(
				$realUrl . '/Special:IIIF',
				'IIIF'
			)
		);

		return true;
	}

	/**
	 * If installed, activate CodeEditor in the NS_IIIF namespace
	 * @param Title $title
	 * @param mixed $lang
	 * @param string $model
	 * @param string $format
	 * @return bool
	 */
	public static function onCodeEditorGetPageLanguage( Title $title, &$lang, string $model, string $format ) {
		if ( \ExtensionRegistry::getInstance()->isLoaded( 'CodeEditor' ) == false ) {
			return true;
		}
		if ( $title->getNamespace() === NS_IIIF ) {
			$lang = 'json';
			return false;
		}
		return true;
	}

}
