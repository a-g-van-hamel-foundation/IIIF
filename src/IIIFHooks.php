<?php

namespace IIIF;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;
use MediaWiki\ChangeTags\Hook\ListDefinedTagsHook;
use MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook;
use MediaWiki\ChangeTags\Hook\ChangeTagsAllowedAddHook;
use ALTree;
use ALSection;
use ALItem;
use ALRow;
use IIIF\ParserFunctions\IIIFGetCanvases;
use IIIF\ParserFunctions\IIIFAnnotator;
use IIIF\ParserFunctions\IIIFAnnotatorData;
use IIIF\ParserFunctions\IIIFTify;
use IIIF\ParserFunctions\IIIFDraggable;
use IIIF\ParserFunctions\IIIFManifestFromSMWQuery;

class IIIFHooks implements
	ParserFirstCallInitHook,
	ContentHandlerDefaultModelForHook,
	ListDefinedTagsHook,
	ChangeTagsListActiveHook,
	ChangeTagsAllowedAddHook {

	public function __construct() {
	}

	public static function onRegister() {
		// Must match the name used in the 'ContentHandlers' section of extension.json
		define( "CONTENT_MODEL_IIIF_JSON", "iiifjson" );
		self::registerSMWResultFormats();
	}

	public function onParserFirstCallInit( $parser ) {
		$flags = Parser::SFH_OBJECT_ARGS;

		$parser->setFunctionHook(
			"iiif-get-canvases",
			function( Parser $parser, PPFrame $frame, array $args ) {
				$pf = new IIIFGetCanvases;
				return $pf->runGetCanvasDataForTemplate( $parser, $frame, $args );
			},
			$flags
		);
		$parser->setFunctionHook(
			"iiif-annotator",
			function( Parser $parser, PPFrame $frame, array $args ) {
				$pf = new IIIFAnnotator;
				return $pf->runIIIFAnnotator( $parser, $frame, $args );
			},
			$flags
		);
		$parser->setFunctionHook(
			"iiif-annotator-data",
			function( Parser $parser, PPFrame $frame, array $args ) {
				$pf = new IIIFAnnotatorData;
				return $pf->runGetAnnotationDataForTemplate( $parser, $frame, $args );
			},
			$flags
		);
		$parser->setFunctionHook(
			"iiif-tify",
			function( Parser $parser, PPFrame $frame, array $args ) {
				$pf = new IIIFTify;
				return $pf->run( $parser, $frame, $args );
			},
			$flags
		);
		$parser->setFunctionHook(
			"iiif-draggable",
			function( Parser $parser, PPFrame $frame, array $args ) {
				$pf = new IIIFDraggable;
				return $pf->run( $parser, $frame, $args );
			},
			$flags
		);
		$parser->setFunctionHook(
			"iiif-manifest-from-smwquery",
			function( Parser $parser, PPFrame $frame, array $args ) {
				$pf = new IIIFManifestFromSMWQuery;
				return $pf->run( $parser, $frame, $args );
			},
			$flags
		);
		return true;
	}

	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( $title->getNamespace() === NS_IIIF ) {
			$model = CONTENT_MODEL_IIIF_JSON;
			return false;
		}
		return true;
	}

	/** Register change tag with both ListDefinedTags hook
	 * and activate it with ChangeTagsListActive hook
	 */
	public function onListDefinedTags( &$tags ) {
		$tags[] = "iiif-annotator-edit";
		return true;
	}

	/** Register change tag with both ListDefinedTags hook
	 * and activate it with ChangeTagsListActive hook
	 */
	public function onChangeTagsListActive( &$tags ) {
		$tags[] = "iiif-annotator-edit";
		return true;
	}

	// Allow tag to be used by the API (ChangeTagsAllowedAdd)
	public function onChangeTagsAllowedAdd( &$allowedTags, $tags, $user ) {
		$allowedTags[] = "iiif-annotator-edit";
	}

	public static function registerSMWResultFormats() {
		//MediaWikiServices::getInstance()->getExtensionRegistry()
		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( $extensionRegistry->isLoaded( 'SemanticMediaWiki' ) ) {
			$GLOBALS['smwgResultFormats']['iiif-canvas-viewer'] = \IIIF\SMW\CanvasViewerResultFormatter::class;
			// alias for iiif-canvas-viewer - @todo depreciate
			$GLOBALS['smwgResultFormats']['iiif-annotations'] = \IIIF\SMW\CanvasViewerResultFormatter::class;
			$GLOBALS['smwgResultFormats']['iiif-annotation-gallery'] = \IIIF\SMW\GalleryResultFormatter::class;
		}
	}

	/**
	 * If installed, activate CodeEditor in the NS_IIIF namespace
	 * @param Title $title
	 * @param mixed $lang
	 * @param string $model
	 * @param string $format
	 * @return bool
	 */
	public static function onCodeEditorGetPageLanguage( $title, &$lang, string $model, string $format ) {
		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( $extensionRegistry->isLoaded( 'CodeEditor' ) == false ) {
			return true;
		}
		if ( $title->getNamespace() === NS_IIIF ) {
			$lang = 'json';
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
	public static function onAdminLinks( ALTree &$adminLinksTree ): bool {
		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( $extensionRegistry->isLoaded( 'Admin Links' ) == false ) {
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

}
