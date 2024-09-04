<?php

use MediaWiki\MediaWikiServices;

/**
 * @todo Maybe rename to jsonUtils? 
 */

class IIIFJson {

		/**
		 * Get data about canvases
		 * And send to MW template
		 * {{#iiif-get-canvases:manifest=... |template=... }}
		 */
		public static function runGetCanvasDataForTemplate( $parser, $frame, $params ) {
				$paramManifestURL = $paramTemplateName = "";
				$userParam1 = $userParam2 = $userParam3 = "";
				$indexName = "indexnumber";
				$canvasArr = [];
				if ( $params == null || $params == 'undefined' ) {
					return false;
				}
				foreach ( $params as $i => $param ) {
						$paramExpanded = $frame->expand($param);
						$keyValPair = explode( '=', $paramExpanded, 2 );
						$paramName = trim( $keyValPair[0] );
						$value = ( array_key_exists(1, $keyValPair) ) ? trim( $keyValPair[1] ) : "";
						switch ( $paramName ) {
							case 'manifest': $paramManifestURL = $value;
							break;
							case 'template': $paramTemplateName = $value;
							break;
							case 'userparam1': $userParam1 = $value;
							break;
							case 'userparam2': $userParam2 = $value;
							break;
							case 'userparam3': $userParam3 = $value;
							break;
							case 'indexname': $indexName = $value;
							break;
						}
				}
				$manifestArr = self::getArrayFromManifest( $paramManifestURL );
				if ( $manifestArr == null ) {
					return;
				}
				// get presentation APi version
				$apiVersion = self::getPresentationApiVersion( $manifestArr );
				if ( $apiVersion == '2' ) {
					$canvasArr = self::getCanvasesV2( $manifestArr );
				} else {
					// @todo add support for V3
				}
				//print_r( $canvasArr );
				$output = ( $paramTemplateName !== "" ) ? 'template' : 'json';
				if ( $output == 'template' ) {
					$res = self::convertArrayToWikiInstances( 
						$canvasArr, 'template', $paramTemplateName, 
						$userParam1, $userParam2, $userParam3
					);
				} else {
					$newArr = self::appendCustomToArray( $canvasArr, $userParam1, $userParam2, $userParam3 );
					$json = json_encode( $newArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
					$res = ( $json !== false ) ? $json : "";
				}
				return [ $res, 'noparse' => false, 'isHTML' => false ];
		}

		public static function getCanvasesV2( $manifestArr ): array {
			// sc:Canvas
			$canvasesArr = $manifestArr['sequences'][0]['canvases'];
			$destArr = []; //destintation
			//$annotationArr
			foreach ( $canvasesArr as $key => $val ) {
				//print_r( $val );
				//$canvasType = $val["@type"] ?? "";
				$canvasId = $val["@id"] ?? "";
				$canvasDescription = $val["description"] ?? "";
				$canvasLabel = $val["label"] ?? "";
				$canvasImage = $val['images'][0]['resource']['@id'] ?? "";

				$destArr[$key]['key'] = "{$key}";
				$destArr[$key]['id'] = $canvasId;
				$destArr[$key]['label'] = $canvasLabel;
				$destArr[$key]['description'] = $canvasDescription;

				$destArr[$key]['image'] = $canvasImage;

				//$str = "<div>{$canvasLabel} - {$canvasId} - {$canvasDescription} - $canvasImage</div>";
				//print_r( $str  );
				// adjust $annotationsId by appending $canvasId
			 // $newAnnotationArr = $annotationArr;
				//$newAnnotationArr[0]["@id"] = $annotationArr[0]["@id"] . "&canvas={$canvasId}";
				//$manifestArr['sequences'][0]['canvases'][$key]["otherContent"] = $newAnnotationArr;
			}
			return $destArr;
	}
	
	/**
	 * @todo how to do this securely? Ideally you'd use the HTTPS protocol only and then validate the certificate to guard against any DNS hijacking possibilities. 
	 * @link https://presentation-validator.iiif.io/
	 * @todo Consider moving to IIIFUtils.php
	 * @param string $manifestUrl
	 * 
	 * @return array|null
	 */
	public static function getArrayFromManifest( $manifestUrl ) {
		$res = IIIFUtils::getArrayFromJsonUrl( $manifestUrl );
		return $res;
		/*
		$parsedUrlArr = parse_url( $manifestUrl );
		if ( $parsedUrlArr['scheme'] !== 'https' ) {
			return null;
		}
		$json = file_get_contents( $manifestUrl );
		// associative array:
		$arr = json_decode( $json, true ); 
		if ( json_last_error() === JSON_ERROR_NONE ) {
			// JSON is valid
			return $arr;
		}
		return null;
		*/
	}

	/**
	 * Get Presentation API version number from IIIF manifest
	 * @param array $obj
	 * 
	 * @return string
	 */
	public static function getPresentationApiVersion( $obj ) {
		//$obj = self::getArrayFromManifest( $manifest );
		//if ( $obj == null ) {
		//  return;
		//}
		$context = $obj['@context'];
		$context = preg_replace( "(^https?://)", "", $context);
		//print_r( $obj );
		if ( $context == "iiif.io/api/presentation/3/context.json" ) {
			$version = '3';
		} elseif ( $context == "iiif.io/api/presentation/2/context.json" ) {
			$version = '2';
		} elseif ( $context == "iiif.io/api/presentation/1/context.json" ) {
			//No support for annotations
			$version = '1';
		} else {
			$version = '2';
		}
		return $version;
	}

	public static function convertArrayToWikiInstances(
		array $templateArr,
		string $targetType,
		string $targetName,
		string $userParam1,
		string $userParam2,
		string $userParam3
	) {
		$str = "";
		if ($targetType == "template") {
			$prefix = "{{";
		} elseif ($targetType == "widget") {
			$prefix = "{{#widget:";
		} elseif ($targetType == "module") {
			$prefix = "{{#invoke:";
		} elseif ($targetType == "subobject") {
			// Afterthought not implemented, but note they're nameless
			$prefix = "{{#subobject:";
		}
		foreach ( $templateArr as $instance ) {
			$str .= $prefix . $targetName . "\n";
			foreach ($instance as $k => $v) {
				$str .= "|{$k}={$v}" . "\n";
			}
			$str .= "|userparam1={$userParam1}";
			$str .= "|userparam2={$userParam2}";
			$str .= "|userparam3={$userParam3}";
			$str .= "}}";
		}
		return $str;
	}

	public static function appendCustomToArray(
		array $arr,
		string $userParam1,
		string $userParam2,
		string $userParam3
	) {
		$newArr = [];
		foreach ( $arr as $k => $instance ) {
			$newInstance = [];
			foreach ($instance as $k => $v) {
				$newInstance[$k] = $v;
			}
			$newInstance['userparam1'] = ( $userParam1 !== "") ? $userParam1 : "";
			$newInstance['userparam2'] = ( $userParam2 !== "") ? $userParam2 : "";
			$newInstance['userparam3'] = ( $userParam3 !== "") ? $userParam3 : "";
			$newArr[] = $newInstance;
		}
		return $newArr;
	}

}
