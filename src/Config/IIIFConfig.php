<?php

/**
 * Return config data required for constructing SMW queries.
 */

namespace IIIF\Config;

use IIIFUtils;

class IIIFConfig {

	public static $smwConfigPropNames = [ 'IIIFv2QueryArg', 'IIIFv3QueryArg', 'IIIFAnnotTextProp', 'IIIFAnnotTargetProp', 'IIIFAnnotSortProp', 'IIIFAnnotDoParse', 'IIIFCollectionLabel', 'IIIFCollectionSummary', 'IIIFCollectionItemURI', 'IIIFCollectionItemResource', 'IIIFCollectionQueryArg', 'IIIFCollectionSortProp' ];
	// e.g. "[[Class::Subject:manuscript items]] [[Is item in MS::@]] [[Has target URI::$canvasId]]", where @ repl. by $resourcePage
	public static $IIIFv2QueryArg = "";
	// e.g. "[[Class::Subject:manuscript items]] [[Has target URI::@]]", where @ repl. by $canvasId
	public static $IIIFv3QueryArg = "";
	// Property containing the readable text of the annotation:
	// @todo Allow for multiple values
	public static $IIIFAnnotTextProp = "";
	// Array version
	public static $annotTextProps = [];
	// Property that links annotations to the canvas URI,
	// corresponding to 'target' in v3, 'on' in v2.
	public static $IIIFAnnotTargetProp = "";
	public static $annotTargetProp = "";
	public static $IIIFAnnotSortProp = "";
	public static $IIIFAnnotDoParse = true;
	public static $IIIFCollectionLabel = "";
	public static $IIIFCollectionSummary = "";
	public static $IIIFCollectionItemURI = "";
	public static $IIIFCollectionItemResource = "";
	public static $IIIFCollectionQueryArg = "";
	public static $IIIFCollectionSortProp = "";

	public static function getConfigProps( $smwConfig = "" ) {
		if ( $smwConfig === "" ) {
			$smwConfigProps = self::setPropsFromGlobalConfig();
		} else {
			// config from wiki page
			$smwConfigStr = IIIFUtils::getRawContentFromPageID( $smwConfig );
			$smwConfigArr = json_decode( $smwConfigStr, true );
			// print_r( $smwConfigArr );
			$smwConfigProps = self::setPropsFromSemanticAnnotationsConfig( $smwConfigArr );
			if ( $smwConfigProps == false ) {
				// fallback
				$smwConfigProps = self::setPropsFromGlobalConfig();
			}
		}
		
		// Process
		$annotTextPropsOld = explode( ",", self::$IIIFAnnotTextProp );
		$annotTextProps = [];
		foreach( $annotTextPropsOld as $prop ) {
			$annotTextProps[] = str_replace( " ", "_", trim( $prop ) );
		}
		self::$annotTextProps = $smwConfigProps["annotTextProps"] = $annotTextProps;
		
		// Process
		self::$annotTargetProp = $smwConfigProps["annotTargetProp"] = str_replace( " ", "_", trim( self::$IIIFAnnotTargetProp ) );

		return $smwConfigProps;
	}

	/**
	 * Set class properties from global configuration.
	 */
	private static function setPropsFromGlobalConfig() {
		global $wgIIIFv2QueryArg;
		global $wgIIIFv3QueryArg;
		global $wgIIIFAnnotTextProp;
		global $wgIIIFAnnotTargetProp;
		global $wgIIIFAnnotSortProp;
		global $wgIIIFAnnotDoParse;
		global $wgIIIFCollectionItemURI;
		global $wgIIIFCollectionItemResource;
		global $wgIIIFCollectionQueryArg;
		global $wgIIIFCollectionSortProp;
		$res = [
			"IIIFv2QueryArg" => trim( $wgIIIFv2QueryArg ),
			"IIIFv3QueryArg" => trim( $wgIIIFv3QueryArg ),
			"IIIFAnnotTextProp" => trim( $wgIIIFAnnotTextProp ),
			"IIIFAnnotTargetProp" => trim( $wgIIIFAnnotTargetProp ),
			"IIIFAnnotSortProp" => trim( $wgIIIFAnnotSortProp ),
			"IIIFAnnotDoParse" => trim( $wgIIIFAnnotDoParse ),
			"IIIFCollectionLabel" => ( self::$IIIFCollectionLabel == "" ) ? self::createDefaultIIIFCollectionLabel() : self::$IIIFCollectionLabel,
			"IIIFCollectionSummary" => self::$IIIFCollectionSummary,
			"IIIFCollectionItemURI" => trim( $wgIIIFCollectionItemURI ),
			"IIIFCollectionItemResource" => trim( $wgIIIFCollectionItemResource ),
			"IIIFCollectionQueryArg" => trim( $wgIIIFCollectionQueryArg ),
			"IIIFCollectionSortProp" => trim( $wgIIIFCollectionSortProp )
		];
		foreach ( $res as $k => $v ) {
			self::${$k} = $v;
		}
		return $res;
	}

	/**
	 * Set class properties from configuration in wiki page.
	 * "SMWConfig" or "SemanticAnnotationsConfig"
	 */
	private static function setPropsFromSemanticAnnotationsConfig( array|null $config ): array|bool {
		if ( $config == null || !array_key_exists( "config", $config ) ) {
			return false;
		}
		$configArr = $config["config"];

		$res = [];
		foreach( self::$smwConfigPropNames as $prop ) {
			$v = $configArr[$prop]["value"] ?? $configArr[$prop] ?? " ";
			self::${$prop} = $res[$prop] = trim( $v );
		}
		return $res;
	}

	public static function showExampleQueries( $smwConfig = "" ) {
		$smwConfigProps = self::getConfigProps( $smwConfig );
		$queryArg2 = $smwConfigProps['IIIFv2QueryArg'] ?? "";
		$queryArg3 = $smwConfigProps['IIIFv3QueryArg'] ?? "";
		$targetProp = $smwConfigProps['annotTargetProp'] ?? "";
		$sort = $smwConfigProps['IIIFAnnotSortProp'] ?? "";
		$textProps = $smwConfigProps['annotTextProps'] ?? [];
		$propStr = "";
		foreach( $textProps as $prop ) {
			$propStr .= "|?$prop ";
		}
		$strAskV2 = <<<WIKI
		{{#ask: {$queryArg2}
		|?{$targetProp}
		{$propStr}
		|sort={$sort}
		}}
		WIKI;
		$strV2 = ( $queryArg2 !== "" ) ? "<div class='col-md-4'>Presentation 2 <pre>" . htmlentities($strAskV2) . "</pre></div>" : "";

		$strAskV3 = <<<WIKI
		{{#ask: {$queryArg3}
		|?{$targetProp}
		{$propStr}
		|sort={$sort}
		}}
		WIKI;
		$strV3 = ( $queryArg3 !== "" ) ? "<div class='col-md-4'>Presentation 3 <pre>" . htmlentities($strAskV3) . "</pre></div>" : "";

		$collQueryArg = $smwConfigProps['IIIFCollectionQueryArg'] ?? "";
		$itemURI = $smwConfigProps['IIIFCollectionItemURI'] ?? "";
		$itemResource = $smwConfigProps['IIIFCollectionItemResource'] ?? "";
		$collSortProp = $smwConfigProps['IIIFCollectionSortProp'] ?? "";
		$strCollAsk = <<<WIKI
		{{#ask: {$collQueryArg}
		|?{$itemURI}
		|?{$itemResource}
		|sort={$collSortProp}
		}}
		WIKI;
		$collLink = "<a href='/Special:IIIFServ/collection/smwconfig/{$smwConfig}'>Special:IIIFServ/collection/smwconfig/{$smwConfig}</a>";
		$strColl = ( $collQueryArg !== "" ) ? "<div class='col-md-4'>Collection: <pre>" . htmlentities( $strCollAsk ) . "</pre>{$collLink}</div>" : "";

		$str = "<br><div class='row'>" . $strV2 . $strV3 . $strColl . "</div>";
		return $str;
	}

	private static function createDefaultIIIFCollectionLabel() {
		global $wgSitename;
		return "Collection presented by {$wgSitename}";
	}

}
