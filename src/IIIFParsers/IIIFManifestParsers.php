<?php

/**
 * Used to draw general information from the Manifest
 * Supports V2 and V3
 */

namespace IIIF\IIIFParsers;

use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFManifestParsers {

	private $apiVersion;

	public function __construct( $apiVersion ) {
		$this->apiVersion = $apiVersion;
	}

	public function createSummary( mixed $manifest ) {
		if ( $manifest === null ) {
			return "<div class='manifest-summary'></div>";
		}
		// First build the array
		if ( $this->apiVersion === "2" ) {
			$summary = [
				"id" => $manifest["@id"] ?? "",
				"label" => $manifest["label"] ?? "",
				"description" => $manifest["description"] ?? ""
			];			
		} elseif( $this->apiVersion === "3" ) {
			$summary = [
				"id" => $manifest["id"] ?? "",
				"label" => IIIFParserUtils::getV3ValueAsString( $manifest["label"] ?? null ),
				"description" => IIIFParserUtils::getV3ValueAsString( $manifest["summary"] ?? null )
			];
		}
		$summary["metadata"] = $this->createMetaDataSummary( $manifest["metadata"] ?? [] );

		$summary["rights"] = $this->apiVersion === "3" ? ( $manifest["rights"] ?? "" ) : ( $manifest["license"] ?? "" );
		$attribution = $this->apiVersion === "3"
			? ( $manifest["requiredStatement"] ?? "" )
			: ( $manifest["attribution"] ?? "" );
		$summary["attribution"] = $this->createAttributionSummary( $attribution );

		// Create and return string
		$str = "";
		$str .= $summary["label"] !== "" ? "<h2>{$summary['label']}</h2>" : "<h2>(No label)</h2>";
		$str .= "<div class='item'><a href='{$summary['id']}'>{$summary['id']}</a></div>";
		$str .= $summary["description"] !== "" ? "<div class='item'>{$summary['description']}</div>" : "";
		$str .= $summary["attribution"] !== "" ? "<div class='item'>{$summary['attribution']}</div>" : "";
		$str .= $summary["rights"] !== "" ? "<div class='item'>{$summary['rights']}</div>" : "";
		$str .= $summary["metadata"] !== "" ? "<div class='manifest-metadata'>{$summary['metadata']}</div>" : "";
		return "<div class='manifest-summary'>" . $str . "</div>";
	}


	private function createMetaDataSummary( array $metadata ) {
		$newArr = [];
		foreach( $metadata as $m ) {
			if( $this->apiVersion === "3" ) {
				$label = IIIFParserUtils::getV3ValueAsString( $m["label"] );
				$value = IIIFParserUtils::getV3ValueAsString( $m["value"] );
			} else {
				$label = $m["label"] ?? "";
				if ( gettype($m["value"]) === "array" ) {
					$value = implode( " -- ", $m["value"] );
				} else {
					$value = $m["value"] ?? "";
				}
			}
			$newArr[] = "<div class='metadata-item'><label class='metadata-label'>$label: </label><div>$value</div></div>";
		}
		return implode( "", $newArr );
	}

	/**
	 * V2 attribution similar to V3 requirementStatement
	 */
	private function createAttributionSummary( $attribution ) {
		if( $this->apiVersion === "3" && gettype($attribution) === "array" ) {
			$label = IIIFParserUtils::getV3ValueAsString( $attribution["label"] );
			$value = IIIFParserUtils::getV3ValueAsString( $attribution["value"] );
			return "$label: " . ($value ?? "");
		} elseif( $this->apiVersion === "2" && $attribution !== "" ) {
			return "Attribution: " . $attribution ?? "";
		}
		return "";
	}

	// @todo
	// ... = provider V3
	// ... = homepage V3
	// logo V2/V3
	// thumbnail V2/V3
}
