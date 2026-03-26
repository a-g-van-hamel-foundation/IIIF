<?php

/**
 * Utility tools for Ranges/Structures
 */

namespace IIIF\IIIFParsers;

use LocalFile;
use IIIF\API\IIIFMwImgAPI;
use IIIF\IIIFMwRemote;
use IIIF\IIIFUtils;
use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFRangeParsers {

	private $urlBase;
	// Something that uniquely identifies the resource and can be reused in the manifest
	private $sourceId;
	private $canonicalRangeParamsV2 = [];
	private $canonicalRangeParamsV3 = [];

	public function __construct() {
		$this->urlBase = IIIFUtils::getUrlBase();
		$this->canonicalRangeParamsV2 = [ "@id", "@type", "label", "viewingHint", "canvases", "ranges", "members" ];
		$this->canonicalRangeParamsV3 = [ "id", "type", "label", "items" ];
	}

	public function transformItemsToRanges( $manifest, $sourceContent ) {
		$version = IIIFParserUtils::getPresentationAPIVersion( $manifest );
		$newItems = [];
		$this->sourceId = $this->guessRoot( $manifest, $version );
		if ( array_key_exists( "items", $sourceContent ) ) {
			switch( $version ) {
				case "3":
					$newItems = $this->transformItemsToRangesV3($sourceContent["items"]);
					break;
				case "2":
					$newItems = $this->transformItemsToRangesV2($sourceContent["items"]);
					break;
			}
		}
		return $newItems;
	}

	/**
	 * @deprecated
	 * @param mixed $items
	 * @return array
	 */
	public function nestByIndentationNumberV3( $items ) {
		return $this->transformItemsToRangesV3( $items );
	}

	/**
	 * Convert our bespoke 'flat' format of TOC items
	 * to nested v3 Ranges.
	 * range {scheme}://{host}/{prefix}/{identifier}/range/{name}
	 */
	private function transformItemsToRangesV3( $items ) {
		$items = $this->addIncrementalNumbersToRanges( $items );
		// stack of references, indexed by level
		$newItems = [];
		$stack  = [ &$newItems ];

    	foreach ( $items as $item ) {
			// current level
			$level = intval( $item["indentLevel"] ?? 0 );
			// Ensure the stack has a valid parent at this level
			$parent = &$stack[$level];

			// Append item to current level
			// without nesting yet
			$item = $this->convertItemV3( $item );
			$parent[] = $item;			

			// Make the 'items' key of the element just added the next level's target
			$lastIndex = array_key_last( $parent );
			$stack[$level + 1] = &$parent[$lastIndex]['items'];

			// Initialise 'items' as empty array 
			// to make sure it's consistently present
			//$parent[$lastIndex]['items'] = [];
			$parent[$lastIndex]['items'] = $item["items"] ?? [];
		}
		return $newItems;
	}

	/**
	 * 
	 * @param mixed $item
	 */
	private function convertItemV3( $item ) {
		// set id
		$randomId = $item["id"];
		$item["id"] = "{$this->sourceId}/range/r";
		if ( array_key_exists( "incremental", $item ) ) {
			$item["id"] .= $item["incremental"];
		} else {
			$item["id"] .= $randomId;
		}
		// set label
		$item["type"] = "Range";
		if ( array_key_exists( "label", $item ) ) {
			// @todo: language!
			$item["label"] = [ "none" => [ $item["label"] ] ];
		} elseif( array_key_exists( "name", $item ) ) {
			// @todo: language!
			$item["label"] = [ "none" => [ $item["name"] ] ];
		}
		// set canvases
		if ( array_key_exists( "canvasids", $item ) && !empty($item["canvasids"]) ) {
			foreach( $item['canvasids'] as $canvasId ) {
				// @todo in time, handle canvas fragments
				$item["items"][] = [
					"id" => $canvasId,
					"type" => "Canvas"
				];
			}
		}		
		// Remove non-canonical entries
		foreach( array_keys( $item ) as $key ) {
			if ( array_search( $key, $this->canonicalRangeParamsV3 ) === false ) {
				// @todo Consider assigning non-canonical props to 
				// custom section - disable for now
				// $item["custom"][$key] = $item[$key];
				unset( $item[$key] );
			}
		}
		return $item;
	}

	/**
	 * Convert our bespoke 'flat' format of TOC items
	 * to nested v3 Ranges.
	 */
	private function transformItemsToRangesV2( $items ) {
		$items = $this->addIncrementalNumbersToRanges( $items );
		$topLevelRange = $this->buildTopLevelRangeV2( $items );

		$newItems = [];
		foreach( $items as $indx => $item ) {
			// (1) Look for any subranges that belong to the current one
			$subIncrementals = [];
			$level = intval( $item["indentLevel"] ?? 0 );

			// traverse $items to find subranges
			$subRanges = $this->findSubRangesToReference( $items, $indx, $level );
			if ( !empty($subRanges) ) {
				$item["ranges"] = $subRanges;
			}

			if ( !empty($subIncrementals) ) {
				//$item["ranges"] = $subIncrementals;
			}
			// (2) ...
			$newItems[] = $this->convertItemV2( $item );
		}
		return [ $topLevelRange, ...$newItems ];
	}

	/**
	 * V2's top-level structure/range is a special component.
	 * Here 'members' are Ranges exclusively, no Canvases,
	 * though specs allow for both.
	 */
	private function buildTopLevelRangeV2( $items ) {
		$members = $ranges = [];
		foreach( $items as $item ) {
			if ( $item["indentLevel"] === 0 ) {
				$members[] = [
					"@id" => "{$this->sourceId}/range/r" . $item["incremental"],
					"@type" => "sc:Range",
					"label" => $item["label"] ?? $item["name"]
				];
				$ranges[] = "{$this->sourceId}/range/r" . $item["incremental"];
			}
		}
		return [
			// @todo use r0
			"@id" => $this->sourceId . "/range/r0",
			"@type" => "sc:Range",
			"label" => "Table of Contents",
			"viewingHint" => "top",
			"ranges" => $ranges
			// "members" => $members,
		];
	}

	/**
	 * 
	 * @param mixed $item
	 */
	private function convertItemV2( $item ) {
		// id
		$randomId = $item["id"];
		$item["@id"] = "{$this->sourceId}/range/r";
		if ( array_key_exists( "incremental", $item ) ) {
			$item["@id"] .= $item["incremental"];
			//unset($item["incremental"]);
		} else {
			$item["@id"] .= $randomId;
		}
		// label
		$item["@type"] = "sc:Range";
		if ( array_key_exists( "label", $item ) ) {
			// Keep as is
		} elseif ( array_key_exists( "name", $item ) ) {
			$item["label"] = $item["name"];
		}
		// canvases
		if ( array_key_exists( "canvasids", $item ) && !empty($item["canvasids"]) ) {
			// Note that IIIF viewers struggle with empty arrays
			foreach( $item['canvasids'] as $canvasId ) {
				// @todo in time, handle canvas fragments
				$item["canvases"][] = $canvasId;
			}
		}

		foreach( array_keys( $item ) as $key ) {
			if ( array_search( $key, $this->canonicalRangeParamsV2 ) === false ) {
				// Assign non-canonical props to custom section
				// @todo disable for now - $item["custom"][$key] = $item[$key];
				unset( $item[$key] );
			}
		}

		return $item;
	}

	/**
	 * To each range, number sequence based on nested 
	 * level and iteration, e.g. 1, 1.1, 1.2, 2, 2.1, 2.1.1
	 * @param mixed $ranges
	 * @return array
	 */
	private function addIncrementalNumbersToRanges( $ranges ) {
		$newRanges = [];
		// latest number sequence
		$latest = [];
		foreach( $ranges as $range ) {
			$level = $range["indentLevel"];
			// increment
			++$latest[$level];
			// and reset everything that comes after
			for ( $x = $level + 1; $x < count($latest); $x++ ) {
				$latest[$x] = 0;
			}
			// add the string representation
			$range["incremental"] = implode( "-", 
				array_filter( array_values( $latest ), function( $n ) {
					return $n !== 0;
				} )
			);
			$newRanges[] = $range;
		}
		return $newRanges;
	}

	/**
	 * For now, use this to find root identifier, hopefully
	 */
	private function guessRoot( $manifest, $version ) {
		if ( $version == "2" ) {
			$id = $manifest["sequences"][0]["@id"];
		} elseif( $version == "3" ) {
			$id = $manifest["items"][0]["id"];
		}
		$idElements = explode( "/", $id );
		array_pop( $idElements );
		return implode( "/", $idElements );
	}

	private function findSubRangesToReference( array $items, $startIndex, $startLevel ) {
		$ranges = [];
		// e.g. 40 items, from 20 onwards means 21 items
		$limit = count($items) - $startIndex;
		for( $x = 1; $x < $limit; $x++ ) {
			if ( !array_key_exists( $startIndex + $x, $items ) ) {
				break;
			}
			$nextItem = $items[ $startIndex + $x ];
			$nextItemLevel = intval( $nextItem["indentLevel"] );
			// incremental to be used as part of the id
			// @todo what if none was found?
			$nextItemIncremental = $nextItem["incremental"] ?? "...";
			// deeper level = $startLevel + 1;
			if ( $nextItemLevel === ($startLevel + 1) ) {
				// Found a subrange
				$ranges[] = "{$this->sourceId}/range/r" . $nextItemIncremental;
			} elseif( $nextItemLevel > ($startLevel + 1) ) {
				// Subrange too deep; skip and continue
			} else {
				break;
			}
		}
		return $ranges;
	}

	// Has no use other than demonstrate format
	private function exampleItemsV3() {
		$items = [
			[
				"indentLevel" => "0",
				"type" => "Range",
				"label" => "Book I",
				"id" => 234234
			],
			[
				"indentLevel" => "1",
				"type" => "Range",
				"label" => "Book I, Chapter 1",
				"canvasids" => [ "p1", "p2" ],
				"id" => 234435
			],
			[
				"indentLevel" => "1",
				"type" => "Range",
				"label" => "Book I, Chapter 2",
				"canvasids" => [ "p3", "p4" ],
				"id" => 234436
			],
			[
				"indentLevel" => "2",
				"type" => "Range",
				"label" => "Book I, Chapter 2 : epilogue",
				"canvasids" => [ "p2" ],
				"id" => 234437
			],
			[
				"indentLevel" => "0",
				"type" => "Range",
				"label" => "Book II",
				"id" => 234438
			],
			[
				"indentLevel" => "1",
				"type" => "Range",
				"label" => "Book II, Chapter I",
				"canvasids" => [ "p5", "p6" ],
				"id" => 234439
			]
		];
		return $items;
	}

}
