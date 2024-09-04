<?php

class IIIFJsonContent extends \JsonContent {

	public const CONTENT_MODEL_ID = 'iiifjson';
	public const MODEL = 'iiifjson';

	public function __construct( $text, $modelId = CONTENT_MODEL_IIIF_JSON ) {
		parent::__construct( $text, $modelId );
	}

	public function testmakeEmptyContent() {
		$class = $this->getContentClass();
		$text = <<<WIKI
{
	"@context": "http://iiif.io/api/presentation/3/context.json",
	"id": "https://example.org/iiif/collection/top",
	"type": "",
	"label": { "en": [ "..." ] },
	"summary": { "en": [ "..." ] },
	"requiredStatement": {
		"label": { "en": [ "Attribution" ] },
		"value": { "en": [ "Provided by Example Organization" ] }
	},
	"items": []
}
WIKI;
		$text = "{}";
		return new $class( $text );
	}

	// const ?
}
