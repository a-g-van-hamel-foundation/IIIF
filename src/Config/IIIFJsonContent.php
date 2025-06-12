<?php

namespace IIIF\Config;

use JsonContent;

class IIIFJsonContent extends JsonContent {

	public const CONTENT_MODEL_ID = 'iiifjson';
	public const MODEL = 'iiifjson';

	public function __construct( $text, $modelId = CONTENT_MODEL_IIIF_JSON ) {
		parent::__construct( $text, $modelId );
	}

}
