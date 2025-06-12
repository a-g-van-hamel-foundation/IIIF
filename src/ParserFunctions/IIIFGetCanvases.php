<?php

namespace IIIF\ParserFunctions;

use MediaWiki\MediaWikiServices;
use IIIF\IIIFParsers\IIIFParserUtils;
use IIIF\IIIFParsers\IIIFCanvasParsers;
use IIIFJson;
use IIIFUtils;

class IIIFGetCanvases {

		/**
		 * Parser function for fetching data about canvases
		 * and send them to a MW template.
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
			$manifestArr = IIIFUtils::getArrayFromJsonUrl( $paramManifestURL );
			if ( $manifestArr == null ) {
				return;
			}

			$apiVersion = IIIFParserUtils::getPresentationApiVersion( $manifestArr );
			if ( $apiVersion === "2" ) {
				$canvasArr = IIIFCanvasParsers::getCanvasesV2( $manifestArr );
			} else {
				// V3
				$canvasArr = IIIFCanvasParsers::getCanvasesV3( $manifestArr );
			}
			//print_r( $canvasArr );
			$output = ( $paramTemplateName !== "" ) ? 'template' : 'json';
			if ( $output == 'template' ) {
				$res = IIIFJson::convertArrayToWikiInstances( 
					$canvasArr, 'template', $paramTemplateName, 
					$userParam1, $userParam2, $userParam3
				);
			} else {
				$newArr = IIIFJson::appendCustomToArray( $canvasArr, $userParam1, $userParam2, $userParam3 );
				$json = json_encode( $newArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
				$res = ( $json !== false ) ? $json : "";
			}
			return [ $res, 'noparse' => false, 'isHTML' => false ];
		}

}
