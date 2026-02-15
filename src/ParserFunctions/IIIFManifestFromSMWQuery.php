<?php

/**
 * Parser function to create a manifest URL 
 * that embeds the SMWQuery
 */

namespace IIIF\ParserFunctions;

use Parser;
use PPFrame;
use IIIF\ParserFunctions\IIIFParserFunctionUtils;
use IIIF\IIIFUtils;

class IIIFManifestFromSMWQuery {

	/**
	 * {{#iiif-manifest-from-smquery: <query> |labelprop= |sort= |order= }}
	 * 
	 */
	public function run( Parser $parser, PPFrame $frame, $params ) {
		if ( !array_key_exists( 0, $params ) || trim( $params[0] ) == "" ) {
			// Nothing to process
			return "";
		}

		$paramsAllowed = [
			"labelprop" => "",
			"descriptionprop" => "",
			"sort" => "",
			"order" => ""
		];
		[ $labelProp, $descriptionProp, $sort, $order ] = array_values( IIIFParserFunctionUtils::extractParams( $frame, $params, $paramsAllowed ) );

		$query = trim( $params[0] );
		if ( trim($labelProp) !== "" ) {
			$query .= "|?{$labelProp}=label";
		}
		// maybe:
		if ( trim($descriptionProp) !== "" ) {
			$query .= "|?{$descriptionProp}=description";
		}
		if ( trim($sort) !== "" ) {
			$query .= "|sort={$sort}";
		}
		if ( trim($order) !== "" ) {
			$query .= "|order={$order}";
		}

		$base64 = IIIFUtils::base64urlEncode( trim($query) );

		$baseUrl = IIIFUtils::getUrlBase();
		$res = "$baseUrl/Special:IIIFServ/presentation3/manifest/local/smwquery/{$base64}";
		return [ $res, 'noparse' => false, 'isHTML' => false ];
	}

}
