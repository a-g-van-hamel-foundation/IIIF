<?php

/**
 * Conversion methods for V3->V2 and
 * SMW data -> IIIF Annotations
 */

namespace IIIF\IIIFParsers;

use IIIFUtils;

class IIIFAnnotationParsers {

	/**
	 * Convert V3 Annotation stored with Mirador to Annotation V2
	 * Initially based on https://github.com/ProjectMirador/mirador-annotations/blob/master/src/SimpleAnnotationServerV2Adapter.js#L73
	 */
	public static function convertMiradorAnnotationItemV3toV2( $v3anno ) {
		$id = ( !empty($v3anno["id"]) ) ? $v3anno["id"] : "";
		$sourceId = IIIFUtils::getArrayPath( [ "target", "source" ], $v3anno, "" );
		// "on": "http://example.org/iiif/book1/canvas/p1#xywh=100,150,500,30"
		$on = [
			"@type" => "oa:SpecificResource",
			"full" => $sourceId
			// selector will be added later
			// rotation?
		];

		// https://iiif.io/api/presentation/2.1/ full and selector belong to resource not on?
		$v2anno = [
			"@id" => $id,
			"@context" => "http://iiif.io/api/presentation/2/context.json",
			"@type" => "oa:Annotation",
			"motivation" => "oa:commenting",
			"on" => $on,
			// To be replaced later on
			"resource" => [
				"@type" => "oa:SpecificResource",
				"full" => [
					"@type" => "cnt:ContentAsText",
					"chars" => "..."
				]
			]
		];
		// "full" => $onfull

		if ( IIIFUtils::isSequentialArray( $v3anno['body'] ) ) {
			foreach( $v3anno["body"] as $body ) {
				$v2anno["resource"][] = self::convertAnnoBodyV3toV2( $body );
			}
		} else {
			$v2anno["resource"] = self::convertAnnoBodyV3toV2( $v3anno["body"] );
		}

		// @todo if array item does not exist

		if ( !empty( $v3anno['target']['selector'] ) ) {
			$targetSelector = $v3anno['target']['selector'];
			//$selector = $v3anno['body']['target']['selector']; //?
			// if this exists then
			if ( IIIFUtils::isSequentialArray($targetSelector) ) {	
				$selectors = [];
				//$targetSelector = $v3anno['target']['selector'];//?
				foreach( $targetSelector as $sel ) {
					$selectors[] = self::convertAnnoSelectorV3toV2( $sel );
				}
				// @DG changed 'on' to 'resource'
				$v2anno['on']['selector'] = [
					"@type" => "oa:Choice",
					"default" => $selectors[0],
					"item" => $selectors[1],
				];
			} else {
				//$targetSelector = $v3anno['target']['selector'];//?
				// @DG changed 'on' to 'resource'
				$v2anno['on']['selector'] = self::convertAnnoSelectorV3toV2( $targetSelector );
			}
			// @todo handle non-existing arrays
			if ( !empty($v3anno['target']['source']['partOf']) ) {
				$v2anno['on']['within'] = [
					"@id" => $v3anno['target']['source']['id'],
					"@type" => "sc:Manifest"
				];
			} else {
				// @todo
				//$v3anno['target']['source']
			}
		}
		// if selector does not exist then nothing...
		return $v2anno;
	}

	/**
	 * Convert annotation body (V3) to resource (V2)
	 * (called a 'resource' in V2)
	 */
	public static function convertAnnoBodyV3toV2( $v3body ): array {
		$type = ( array_key_exists( 'purpose', $v3body) && $v3body['purpose'] == "tagging" ) ? "oa:Tag" : "cnt:ContentAsText";
		$v2body = [
			"@type" => $type,
		];
		if ( array_key_exists( 'format', $v3body) ) {
			$v2body['format'] = $v3body['format']; // e.g. "text/html"
		}
		if ( array_key_exists( 'value', $v3body) ) {
			$v2body['chars'] = $v3body['value'];
			//$v2body['full']['chars'] = $v3body['value'];
		}
		if ( array_key_exists( 'language', $v3body) ) {
			$v2body['language'] = $v3body['language'];
		}
		return $v2body;
	}

	public static function convertAnnoSelectorV3toV2( $v3selector ) {
		$res = [];
		if ( !empty($v3selector['type']) ) {
			switch ( $v3selector['type'] ) {
				case 'SvgSelector':
				$res = [
					"@type" => "oa:SvgSelector",
					"value" => $v3selector['value']
				];
				break;
				case 'FragmentSelector':
				$res = [
					"@type" => "oa:FragmentSelector",
					"value" => $v3selector['value']
				];
				break;
			}
		}
		return $res;
	}

	/**
	 * Preprocess SMW as annotation item (IIIF v3)
	 * Supports alternative motivation.
	 * Originally part of IIIFAnnotationsAPI
	 * @param array $item
	 */
	public static function preprocessAnnotationV3( $item, $motivation = "commenting", $smwConfigProps = [] ) {
		$id = $item['smwObjectURI'];
		// $id = rand(1000, 9999);
		list( $target, $comment ) = self::getPropertyValuesForAnnotation( $item, $smwConfigProps );
		$body = [
			"type" => "TextualBody",
			"language" => "en",
			"format" => "text/html",
			"value" => $comment
		];
		$res3 = [
			"id" => $id,
			"type" => "Annotation", // or "Image"
			"motivation" => $motivation,
			"body" => $body,
			"target" => $target // canvas
		];
		return $res3;
	}

	/**
	 * Preprocess SMW as annotation item
	 * IIIF v2 Open Annotations
	 * Supports alternative $motivation e.g. "sc:painting"
	 * Originally part of IIIFAnnotationsAPI
	 * Using "cnt:ContentAsText" (cf. "dctypes:Text" references external content)
	 * @todo maybe default id to rand(1000, 9999) instead?
	 * @link https://iiif.io/api/presentation/2.1/
	 * @link https://iiif.io/api/presentation/2.0/
	 * @link https://iiif.io/api/cookbook/recipe/0019-html-in-annotations/
	 * @param array $item
	 */
	public static function preprocessAnnotationV2(
		array $item,
		string $motivation = "oa:commenting",
		array $smwConfigProps = []
	) {
		// $motivation = "oa:commenting";
		[ $target, $comment ] = self::getPropertyValuesForAnnotation( $item, $smwConfigProps );
		$res2 = [
			"@id" => $item['smwObjectURI'],
			"@type" => "oa:Annotation",
			"motivation" => $motivation,
			// body:
			"resource" => [
				"@type" => "cnt:ContentAsText",
				"format" => "text/html",
				"chars" => $comment,
				"language" => "en",
			],
			"on" => $target //canvas
		];
		return $res2;
	}

	/**
	 * One target property and one/multple annotation properties (concatenated)
	 * Originally in IIIFAnnotationAPI
	 * @param mixed $item
	 * @return array
	 */
	public static function getPropertyValuesForAnnotation( $item, $smwConfigProps = [] ): array {
		$target = "";
		$annotArr = [];
		foreach( $item as $key => $val ) {
			switch ($key) {
				case $smwConfigProps['IIIFAnnotTargetProp']:
					$target = $val[0];
					break;
				case 'smwObjectURI':
					// do nothing
					break;
				default:
					// Mirador does not render em tags
					$annotArr[] = str_replace( ['<em>', '</em>'], ['<i>', '</i>'], $val[0] );
			}
		}
		$comment = implode( "<br>", $annotArr );

		// @todo - offer config option to render wikitext as html
		$parseWikitext = $smwConfigProps['IIIFAnnotDoParse'] ?? false;
		if ( $parseWikitext ) {
			$comment = IIIFUtils::convertStrToWikitext( $comment, null );
		}

		return [ $target, $comment ];
	}

}
