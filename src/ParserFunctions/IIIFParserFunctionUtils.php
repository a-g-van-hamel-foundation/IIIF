<?php

namespace IIIF\ParserFunctions;

class IIIFParserFunctionUtils {

	public static function extractParams( $frame, array $params, $paramsAllowed ) {
		$incomingParams = [];
		foreach ( $params as $param ) {
			$paramExpanded = $frame->expand( $param );
			$keyValPair = explode('=', $paramExpanded, 2);
			$paramName = trim( $keyValPair[0] );
			$value = ( array_key_exists( 1, $keyValPair) ) ? trim( $keyValPair[1] ) : "";
			$incomingParams[$paramName] = $value;
		}
		$params = [];
		foreach ( $paramsAllowed as $paramName => $default ) {
			$params[$paramName] = ( array_key_exists( $paramName, $incomingParams ) ) ? $incomingParams[$paramName] : $default;
		}
		return $params;
	}

	/**
	 * Extract user parameters beg. with 'userparam'
	 * @param PPFrame $frame
	 * @param array $params
	 * @return array
	 */
	public static function extractUserParams( $frame, array $params ) {
		$userParams = [];
		foreach ( $params as $param ) {
			$paramExpanded = $frame->expand( $param );
			$keyValPair = explode('=', $paramExpanded, 2);
			$paramName = trim( $keyValPair[0] );
			if ( substr( $paramName, 0, 9 ) == 'userparam'
				&& array_key_exists( 1, $keyValPair )
			) {
				$userParams[$paramName] = array_key_exists( 1, $keyValPair) ? trim( $keyValPair[1] ) : "";
			}
		}
		return $userParams;
	}

}
