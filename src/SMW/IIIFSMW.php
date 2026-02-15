<?php

namespace IIIF\SMW;

use MediaWiki\MediaWikiServices;
//use SMW\Localizer as Localizer;
use SMW\DIWikiPage;
use SMWQueryProcessor;
use IIIF\Config\IIIFConfig;
use IIIF\IIIFUtils;

/**
 * Methods for working with Semantic MediaWiki. 
 */

class IIIFSMW {

	public static $smwConfigProps = [];

	/**
	 * Get pages or subobjects for resource
	 * $resource can be a page id as well as a full pagename (e.g. "Dublin, Trinity College, MS 1339");
	 * Custom queries supported for either v2 or v3, not both
	 * @todo depreciate $smwQueryEscaped
	 * 
	 * @param string $resource
	 * @param string $canvasId
	 * @param string|false $smwQueryEscaped
	 * 
	 * @return array
	 */
	public static function getItemsForResource(
		string|int $resource,
		string|bool $canvasId = false,
		$smwQueryEscaped = false,
		$smwConfig = ""
	): array {
		// Exits silently if SMW is not installed.
        self::checkForSMW();
		self::$smwConfigProps = IIIFConfig::getConfigProps( $smwConfig );

		$resourcePage = is_numeric( $resource )
			? IIIFUtils::getFullpageNameFromPageID( $resource )
			: $resource;
		$version = ( $canvasId == false ) ? 3 : 2;

		if ( $version == 3 ) {
			// v3
			if ( $smwQueryEscaped == false ) {
				// replace placeholder with resource
				$queryArg = str_replace( "@", "{$resourcePage}", trim( self::$smwConfigProps['IIIFv3QueryArg'] ) );
			} else {
				// @deprecated
				$queryArg = self::processSemanticQuery( $smwQueryEscaped, $resourcePage );
 			}
		} else {
			//v2
			if ( $smwQueryEscaped == false ) {
				$queryArg = str_replace( "@", "{$canvasId}", trim( self::$smwConfigProps['IIIFv2QueryArg'] ) );
			} else {
				// @deprecated
				$queryArg = self::processSemanticQuery( $smwQueryEscaped, $resourcePage );
			}
		}
		$sort = ( self::$smwConfigProps['IIIFAnnotSortProp'] == "" ) ? "" : trim( self::$smwConfigProps['IIIFAnnotSortProp'] );
        $printout = "|named args=yes|link=none|limit=9999|sort={$sort}|searchlabel=";

        $result = self::getAllObjectsForQuery( $queryArg, $printout );
        return $result;
    }

    /**
	 * Returns an array of the names of objects that are the result of an SMW query.
	 *
	 * @param string $queryArg
	 * @param string $printout
	 * @return array
	 */
	public static function getAllObjectsForQuery( 
		string $queryArg,
		string $printout,
		array|null $propNames = null
	): array {
		$store = self::getSMWStore();
		$queryRes = self::getQueryResultForQuery( $queryArg, $printout );
		$rows = $queryRes->getResults();

		$res = [];
		foreach ( $rows as $diWikiPage ) {
			$objectTitle = $diWikiPage->getTitle(); // Title object
			$pageURI = $diWikiPage->getSerialization();
			$pageURIArr = explode( "#", $pageURI, 3 );
			$uriStr = "https://$_SERVER[HTTP_HOST]/";
			$page = $pageURIArr[0];
			$namespaceName = self::getNamespaceNameFromIndex( $pageURIArr[1] );
			$hash = $pageURIArr[2]; // includes hash and underscore
			$uriStr .= ( $namespaceName == "" ) ? "" : "{$namespaceName}:";
			$uriStr .= $page . $hash;

			$titleDI = DIWikiPage::newFromTitle( $objectTitle );
			$titleData = $store->getSemanticData( $titleDI );

			$res[] = self::getSelectPropertyValuesForTitleData( $uriStr, $titleData, $propNames );
		}
		return $res;
	}

	/**
	 * @return SMW\\Query\\QueryResult
	 */
	public static function getQueryResultForQuery( 
		string $queryArg,
		string $printout
	) {
		$rawQuery = $queryArg . $printout;
		// @todo || may be used in a query!
		$rawQueryArray = explode( "|", $rawQuery );
		[ $queryString, $processedParams, $printouts ] = SMWQueryProcessor::getComponentsFromFunctionParams( $rawQueryArray, false );
		SMWQueryProcessor::addThisPrintout( $printouts, $processedParams );
		$processedParams = SMWQueryProcessor::getProcessedParams( $processedParams, $printouts );

		// Run query and get results
		$queryObj = SMWQueryProcessor::createQuery( 
			$queryString,
			$processedParams,
			SMWQueryProcessor::SPECIAL_PAGE,
            '',
            $printouts
        );
		$store = self::getSMWStore();
		return $store->getQueryResult( $queryObj );
	}

	/**
	 * Helper method for 'smwquery' parameter
	 * 
	 * @param string $query
	 * @param string $substr
	 * $return string
	 */
	public static function processSemanticQuery( $query, $substr = '' ) {
			$query = str_replace(
				[ "&lt;", "&gt;", "(", ")", '%', '@' ],
				[ "<", ">", "[", "]", '|', $substr ],
				$query
			);
			return $query;
	}

    /**
     * Exits if SMW is not installed
     */
    public static function checkForSMW() {
        $store = self::getSMWStore();
        if ( $store == null ) {
             return [];
        }
    }

    /**
	 * Helper function to get the SMW data store, if SMW is installed.
	 * @return Store|null
	 */
	public static function getSMWStore() {
		if ( class_exists( '\SMW\StoreFactory' ) ) {
			return \SMW\StoreFactory::getStore();
		} else {
			return null;
		}
	}

	/**
	 * Get values for select properties
	 */
	public static function getSelectPropertyValuesForTitleData(
		string $uriStr,
		$titleData, 
		array|null $propNames = null
	) {
		$allPropsArr = $titleData->getProperties();

		if ( $propNames == null ) {
			$propNames = self::$smwConfigProps['annotTextProps'];
			$propNames[] = self::$smwConfigProps['annotTargetProp'];
		}

		// Filter properties
		$newPropsArr = [];
		foreach ( $propNames as $name ) {
			if ( in_array( $name, $allPropsArr ) ) {
				$newPropsArr[$name] = $allPropsArr[$name];
			}
		}

		$valArr = [];
		$valArr['smwObjectURI'] = $uriStr;
		foreach ( $newPropsArr as $smwProp ) {
			//$smwProp instanceof SMW\DIProperty;
			$smwPropValsArr = $titleData->getPropertyValues( $smwProp );
			$smwPropLabel = $smwProp->getLabel();
			foreach ( $smwPropValsArr as $smwPropValue ) {
				$valArr[ $smwPropLabel ][] = $smwPropValue->getSerialization();
			}
		}

		return $valArr;
	}

	/**
	 * For index, get namespace name in content language
	 * @todo maybe use : str_replace( '_', ' ', $name )
	 * getNsText vs getFormattedNsText ?
	 */
	public static function getNamespaceNameFromIndex( $index ) {
		/* SMW method
		$localizer = Localizer::getInstance();
		$language = $localizer->getContentLanguage();
		*/
		$language = MediaWikiServices::getInstance()->getContentLanguage();
		$name = $language->getFormattedNsText( $index );
		return $name;
	}

	/**
     * Maybe SMW has a native function for this
     * doUnserialize()
     * @todo getNamespaceNameFromIndex is almost identical to one in SMW file
     */
    public static function resolveDIWikiPage( $diWikipage ) {
        //doUnserialize
        $arr = explode( '#', $diWikipage, 4 );
        $namespaceNumber = intval( $arr[1] );
        $prefix = ( $namespaceNumber !== 0 ) ? self::getNamespaceNameFromIndex( $namespaceNumber ) . ":" : "";
        $pagename = $prefix . $arr[0];
        $str = str_replace( "_", " ", $pagename);
        return $str;
    }

	/**
	 * Utility method for simplifying a SMW property printout
	 * Can be used to prepare data before transferring
	 * everything to wiki templates
	 * 
	 * @param array $printout
	 * @param string $link - cf. SMW link=none?
	 * @param mixed $valueSep - if not null (default), the sep will be used to implode the array
	 * 
	 * @return array|string
	 **/
	public static function simplifyPrintoutArray(
		array $printout,
		$wikilink = "none",
		mixed $valueSep = null
	) {
		if ( count($printout) === 0 ) {
			return $valueSep !== null ? "" : [];
		}
		if( gettype( $printout[0] ) === "array" && isset( $printout[0]["fulltext"] ) ) {
			// data type Page
			$newPrintout = [];
			foreach( $printout as $p ) {
				if ( $wikilink === "none" ) {
					$newPrintout[] = $p["fulltext"];
				} else {
					// @todo $p["displaytitle"] ?? $p["fulltext"]?
					$newPrintout[] = "[[" . $p["fulltext"] . "]]";
				}
			}
		} else {
			// other datatypes
			$newPrintout = $printout;
		}

		return $valueSep !== null
			? implode( $valueSep, $newPrintout )
			: $newPrintout;
	}

}
