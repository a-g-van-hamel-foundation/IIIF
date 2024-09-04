<?php
/**
 * List basic information about the extension
 *
 * @author: Dennis Groenewegen
 * @file
 * @ingroup
 */
//namespace TF\Special;

class IIIFSpecial extends \SpecialPage {

	public function __construct( $name = 'IIIF' ) {
		parent::__construct( $name );
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() {
		return false;
	}

	public function execute( $subPage ) {
		$outputPage = \RequestContext::getMain()->getOutput();
		$res = '';
		$this->setHeaders();

		//$this->getOutput()->addModuleStyles( [ 'ext.iiif.special' ] );
		$pageHeader = self::buildPageHeader();

		$res = $pageHeader;
		$outputPage->addWikiTextAsContent( $res );

	}

	private function buildPageHeader() {
		$res = "<div>For now, see [https://codecs.vanhamel.nl/Show:Lab/IIIF the CODECS website].</div>";

		return $res;
	}

}
