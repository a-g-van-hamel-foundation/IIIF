<?php

use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use MediaWiki\Content\ValidationParams;

class IIIFJsonContentHandler extends \JsonContentHandler {

	public function __construct(
		$modelId = 'iiifjson', // alt. CONTENT_MODEL_IIIF_JSON
		$formats = [ CONTENT_FORMAT_JSON ]
	) {
		parent::__construct( $modelId, $formats );
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return IIIFJsonContent::class;
	}

	/**
	 * {@inheritDoc}
	 */
	public function makeEmptyContent() {
		$class = $this->getContentClass();
		return new $class( '{}' );
	}

	public function supportsPreloadContent(): bool {
		return true;
	}

	// validateSave

	/**
	 * {@inheritDoc}
	 */
	public function supportsSections() {
		return false;
	}

	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	) {
		'@phan-var IIIFJsonContent $content';
		// FIXME: WikiPage::doUserEditContent generates parser output before validation.
		// As such, native data may be invalid (though output is discarded later in that case).

		$title = \Title::castFromPageReference( $cpoParams->getPage() );
		$pageId = $title->getId();
		$context = new \RequestContext();
		$outputPage = \RequestContext::getMain()->getOutput();

		$parserOutput->addModuleStyles( [ "ext.iiif.styles" ] );

		$header = self::buildHeader( $title, $outputPage );

		$footer = "";
		$jsonContentStr = $content->getText();
		$jsonContentType = self::getContentType( $jsonContentStr );
		if ( $jsonContentType == "SemanticAnnotationsConfig" ) {
			$q = IIIFConfig::showExampleQueries( $pageId );
			$footer = $q;
		}
	
		if ( $cpoParams->getGenerateHtml() ) {
			if ( $content->isValid() ) {
				$jsonContent = $content->rootValueTable( $content->getData()->getValue() );
				$parserOutput->setText( $header . $jsonContent . $footer );
			} else {
				$error = wfMessage( 'invalid-json-data' )->parse();
				$parserOutput->setText( $error );
			}

			$parserOutput->addModuleStyles( [ 'mediawiki.content.json' ] );
		} else {
			$parserOutput->setText( null );
		}
	}

	/**
	 * Create header before content
	 * @param Title $title
	 * @param mixed $outputPage
	 * @return string
	 */
	private static function buildHeader( \Title $title, $outputPage ) {
		$pageId = $title->getId();
		$pageName = $title->getFullText();
		$urlName = urlencode( $pageName );

		// @todo check if the file exists
		$filePath = "/extensions/IIIF/assets/IIIF-logo.svg";
		$img = "<img src='$filePath' alt='IIIF logo' style='height:15px;'>";
		//$btnClass = "cdx-button cdx-button--action-default";
		$btnClass = "btn-iiif";

		$res = "<div class='iiif-header text-small'><!--
			--><div class='iiif-header-left'>$img</div><!--
			--><div class='iiif-header-right'><!--
			--><span class='label-iiif'>page id: {$pageId}</span><!--
			--><a href='/Special:IIIFServ/wiki/{$pageId}''><button class='{$btnClass}'>View JSON</button></a><!--
			--><a href='/index.php?title=$urlName&action=raw' download='$pageName-$pageId.json'><button class='{$btnClass}'>Download JSON</button></a><!--
			--></div><!--
		--></div>";
		return $res;
	}

	/**
	 * @todo maybe, not implemented
	 * get value of "type" attribute or something other
	 * based on JSON, identify type of content
	 * and offer appropriate tools/features
	 * e.g. add or link to Mirador viewer, 
	 * show query for type=SemanticAnnotationsConfig
	 */
	private static function getContentType( string $content ) {
		$json = json_decode( $content, true );
		$type = "unknown";
		if ( array_key_exists( "@context", $json ) && $json["@context"] == "http://iiif.io/api/presentation/3/context.json" ) {
			$type = "IIIF Presentation 3";
			// "type": "Manifest"
		} elseif ( array_key_exists( "@context", $json ) && $json["@context"] == "http://iiif.io/api/presentation/2/context.json" ) {
			$type = "IIIF Presentation 2";
			// "@type":"sc:Manifest"
		} elseif ( array_key_exists( "type", $json ) ) {
			// first type listed only?
			if ( gettype( $json["type"] ) == "string" ) {
				switch( $json["type"] ) {
					case "SemanticAnnotationsConfig":
						$type = "SemanticAnnotationsConfig";
						break;
					case "ParallelisationSchema": 
						// // Experimental: https://codecs.vanhamel.nl/Json:ParallelisationSchema/LU
						$type = "ParallelisationSchema";
						break;
				}
			}
		}
		return $type;
	}

}
