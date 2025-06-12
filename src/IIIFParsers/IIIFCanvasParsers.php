<?php

/**
 * Utility tools for fetching and building Canvases
 * Focusing primarily on matters of formatting
 * (Pres API v2) "@type": "sc:Canvas" (https://iiif.io/api/presentation/2.1/#canvas)
 * (Pres API v3) "type": "Canvas" (https://iiif.io/api/presentation/3.0/#53-canvas)
 */

namespace IIIF\IIIFParsers;

use LocalFile;
use IIIF\API\IIIFMwImgAPI;
use IIIFMwRemote;
use IIIFUtils;
use IIIF\IIIFParsers\IIIFParserUtils;

class IIIFCanvasParsers {

	private $canvases = null;
	private $imageInformationRequestUrls = [];
	// Info for creating a table of contents
	private $tocItems = [];

	public function __construct() {
		//
	}

	/**
	 * Get the information from canvases as required for
	 * the OpenSeadragon-based viewer.
	 * 
	 * @return array
	 */
	public function getInfoFromCanvasesForOSD( $canvases, $presentationAPIVersion = "2" ) {		
		foreach( $canvases as $index => $canvas ) {
			if( $presentationAPIVersion == "2" ) {
				$service = IIIFUtils::getArrayPath(	[ "images", 0, "resource", "service" ], $canvas );
			} elseif( $presentationAPIVersion == "3" ) {
				$services = IIIFUtils::getArrayPath( [ "items", 0, "items", 0, "body", "service" ], $canvas );
				// @todo Assuming, for now, the first service listed is good enough
				// While valid, it may not be the best option
				$service = $services[0];
			}
			$imageApiVersion = $this->getImageAPIVersionFromService( $service );
			if ( $imageApiVersion !== null ) {
				$this->setCanvasInfo( $index, $canvas, $service, $presentationAPIVersion, $imageApiVersion );
			}
		}
		return [ $this->imageInformationRequestUrls, $this->tocItems ];
	}

	/**
	 * Creates simple reference object from canvas and image service.
	 */
	public function setCanvasInfo(
		$index,
		array $canvas,
		array $service,
		string $presentationAPIVersion = "2",
		string $imageAPIVersion = "2" ) {
		switch( $presentationAPIVersion ) {
			case "3":
				// Just get first label
				$canvasLabelEntry = $canvas["label"] ?? null;
				if ( $canvasLabelEntry !== null ) {
					$labelKey = array_keys( $canvas["label"] )[0];
					$canvasLabel = $canvas["label"][$labelKey][0] ?? strval( $index );
				} else {
					$canvasLabel = strval( $index );
				}
				$canvasId = $canvas["id"];
			break;
			case "2":
			case "1":
				$canvasLabel = $canvas["label"] ?? strval( $index );
				$canvasId = $canvas["@id"];
		}
		switch( $imageAPIVersion ) {
			case "1":
			case "2":
				$urlBase = $service["@id"] ?? null;
			break;
			case "3":
				$urlBase = $service["id"] ?? null;
			break;
			default: $urlBase = null;
		}
		$tocItem = [
			"index" => $index,
			"canvasId" => $canvasId,
			"label" => $canvasLabel,
			//"description" => $canvas["description"] ?? null,
			"url" => $urlBase . "/info.json",
			"meta" => [
				"version" => $imageAPIVersion
			]
		];
		if ( $urlBase !== null ) {
			$this->imageInformationRequestUrls[] = "$urlBase/info.json";
		}
		if ( $tocItem !== null ) {
			$this->tocItems[] = $tocItem;
		}
	}

	/**
	 * @deprecated in favour of setCanvasInfo()
	 * Get TOC item and tileSource for OSD (V2)
	 * type	"Canvas"
	 * 
	 * @param mixed $index
	 * @param mixed $canvas
	 * @param mixed $version
	 * @return void
	 */
	public function getCanvasInfoV2( $index, $canvas ) {
		$context = IIIFUtils::getArrayPath(
			[ "images", 0, "resource", "service", "@context" ],
			$canvas
		);
		// Defaults
		$tocItem = [];
		$imageInformationRequestUrl = "";

		switch( $context ) {
			case "http://iiif.io/api/image/1/context.json":
			case "http://iiif.io/api/image/2/context.json":
				$service = IIIFUtils::getArrayPath(
					[ "images", 0, "resource", "service" ],
					$canvas
				);
				//?
				$urlBase = $service["@id"] ?? null;
				$tocItem = [
					"index" => $index,
					"canvasId" => $canvas["@id"],
					"label" => $canvas["label"] ?? $index,
					//"description" => $canvas["description"] ?? null,
					"url" => $urlBase . "/info.json",
					"meta" => [
						"version" => "2"
					]
				];
			break;
			case "http://iiif.io/api/image/3/context.json":		
				// @todo !!!
				$urlBase = $service["id"] ?? null;
				// todo
				$tocItem = [
					"index" => $index,
					"canvasId" => $canvas["id"],
					"label" => $canvas["label"]["none"][0] ?? $index,
					"url" => $urlBase . "/info.json",
					"meta" => [ "version" => "3" ]
				];
			break;
			default:
				$urlBase = null;
		}
		if ( $urlBase !== null ) {
			$this->imageInformationRequestUrls[] = $urlBase . "/info.json";
		}
		if ( $tocItem !== null ) {
			$this->tocItems[] = $tocItem;
		}
	}

	/**
	 * @deprecated in favour of setCanvasInfo()
	 * Get TOC item and tileSource for OSD (V2)
	 * Presentation API v3
	 * @return
	 */
	public function getCanvasInfoV3( $index, $canvas ) {
		// id, type, label, height, width
		$service = IIIFUtils::getArrayPath( [ "items", 0, "items", 0, "body", "service", 0 ], $canvas );
		// @note only canvases with services are currently supported!
		if ( $service === null ) {
			return;
		}

		// @todo :
		// "Note that the @context key should not be present within the service,
		// but instead included at the beginning of the document. The example
		// below includes both version 2 and version 3 IIIF Image API services"
		$context = $service["@context"];
		switch( $context ) {
			//
		}
		$urlBase = IIIFUtils::getArrayPath( [ "id" ], $service );
		// Working example showed that "/info.json" needs to be attached
		$tocItem = [
			"index" => $index,
			"canvasId" => $canvas["id"],
			"label" => $canvas["label"]["none"][0] ?? $index,
			"url" => $urlBase . "/info.json",
			"meta" => [
				"version" => "3"
			]
		];
		if ( $urlBase !== null ) {
			$this->imageInformationRequestUrls[] = $urlBase . "/info.json";
		}
		if ( $tocItem !== null ) {
			$this->tocItems[] = $tocItem;
		}
	}

	/**
	 * Get canvases from V2 or V3 Manifest.
	 */
	public static function getCanvasesFromManifest( array $manifestArr, $presentationAPIVersion = null ) {
		if ( $presentationAPIVersion === null ) {
			$presentationAPIVersion = IIIFParserUtils::getPresentationApiVersion( $manifestArr );
		}
		$canvases = [];
		switch( $presentationAPIVersion ) {
			case "2": $canvases = IIIFUtils::getArrayPath( [ "sequences", 0, "canvases" ], $manifestArr );
			break;
			case "3": $canvases = IIIFUtils::getArrayPath( [ "items" ], $manifestArr );
			break;
		}
		return $canvases;
	}

	/**
	 * Get canvases (sc:Canvas) from V2 Manifest and
	 * return a summary
	 * Originally in IIIFJson
	 * @param mixed $manifestArr
	 * @return array<array>
	 */
	public static function getCanvasesV2( $manifestArr ): array {
		$canvasesArr = IIIFUtils::getArrayPath( [ "sequences", 0, "canvases" ], $manifestArr );
		if ( $canvasesArr === null ) {
			return [];
		}
		$destArr = [];
		foreach ( $canvasesArr as $key => $val ) {
			//$canvasType = $val["@type"] ?? "";
			$destArr[$key]["manifest"] = $manifestArr["@id"] ?? "";
			$destArr[$key]["key"] = "{$key}";
			$destArr[$key]["id"] = $val["@id"] ?? "";
			$destArr[$key]["label"] = $val["label"] ?? "";
			$destArr[$key]["description"] = $val["description"] ?? "";
			$destArr[$key]["image"] = IIIFUtils::getArrayPath( ["images", 0, "resource", "@id"], $val, "" );
			$service = IIIFUtils::getArrayPath( ["images", 0, "resource", "service" ], $val, null );
			$destArr[$key]["serviceid"] = $service !== null 
				? ( $service["@id"] ?? $service["id"] ?? "" )
				: "";
		}
		return $destArr;
	}

	/**
	 * V3 counterpart to getCanvasesV2
	 */
	public static function getCanvasesV3( $manifestArr ): array {
		$canvasesArr = IIIFUtils::getArrayPath( [ "items" ], $manifestArr );
		if ( $canvasesArr === null ) {
			return [];
		}
		$destArr = [];
		foreach ( $canvasesArr as $key => $val ) {
			$destArr[$key]["manifest"] = $manifestArr["id"] ?? "";
			$destArr[$key]["key"] = "{$key}";
			$destArr[$key]["id"] = IIIFUtils::getArrayPath( [ "id" ], $val, "" );
			$labelArr = $val["label"] ?? null;
			if ( $labelArr !== null ) {
				// @todo - for now, get the first label we find
				$firstK = array_keys($labelArr)[0];
				$destArr[$key]["label"] = $labelArr[$firstK][0];
			} else {
				$destArr[$key]["label"] = "";
			}
			$destArr[$key]["description"] = IIIFUtils::getArrayPath( ["description"], $val, "" );
			$destArr[$key]['image'] = IIIFUtils::getArrayPath( ["items", 0, "items", 0, "body", "id"], $val, "" );
			$service = IIIFUtils::getArrayPath( ["items", 0, "items", 0, "body", "service", 0 ], $val, null );
			$destArr[$key]["serviceid"] = $service !== null 
				? ( $service["@id"] ?? $service["id"] ?? "" )
				: "";
		}
		return $destArr;
	}

	/**
	 * IIIF Presentation v2
	 */
	public static function formatCanvasV2(
		string $baseUrl,
		string $repoSource,// eg local
		string $pageid,
		string $fileName,
		array $canvasMetadata,
		$sourceWidth,
		$sourceHeight,
		$imageResourceId,
		$smallThumb,
		$mediaType
	) {
		$canvasId = $baseUrl . "/Special:IIIFServ/presentation/canvas/{$repoSource}/" . $pageid . ".json";
		$imageAnnotationId = $baseUrl . "/Special:IIIFServ/presentation/annotation/{$repoSource}/" . $pageid . ".json";

		if( $repoSource === "local" ) {
			$service = IIIFMwImgAPI::buildIIR( $pageid, $sourceWidth, $sourceHeight, [], "2" );
			$resourceImageService = [
				"@context" => "http://iiif.io/api/image/2/context.json",
				"id" => $service["@id"],
				"profile" => "level2"
			];
		} else {
			$service = IIIFMwRemote::buildIIRRemotely( $pageid, $repoSource, $sourceWidth, $sourceHeight, "2" );
			$resourceImageService = [
				"@context" => "http://iiif.io/api/image/2/context.json",
				"@id" => $service["@id"],
				"profile" => [ "http://iiif.io/api/image/2/level2.json" ]
			];
		}

		$imageResource = [
			"@id" => $imageResourceId,
			"@type" => "dctypes:Image",
			"format" => $mediaType,
			"width" => $sourceWidth,
			"height" => $sourceHeight,			
			"service" => $service
		];
		$images = [];
		$images[] = [
			"@id" => $imageAnnotationId,
			"@type" => "oa:Annotation",
			"motivation" => "sc:painting",
			"on" => $canvasId,
			"resource" => $imageResource
		];
		$thumbnail = [
			"@id" => $smallThumb,
			"@type" => "dctypes:Image",
			"format" => $mediaType,
			"width" => 120,
			"height" => LocalFile::scaleHeight( $sourceWidth, $sourceHeight, 120 )
			// "service" => $service
		];
		$label = $repoSource === "local"
			? $fileName
			: str_replace( "File:", "", $fileName );
		$canvas = [
			"@type" => "sc:Canvas",
			"@id" => $canvasId,
			"label" => $label,
			"width" => $sourceWidth,
            "height" => $sourceHeight,
			"images" => $images,
			"thumbnail" => $thumbnail,
			"metadata" => $canvasMetadata
		];
		return $canvas;
	}

	/**
	 * IIIF Presentation v3
	 */
	public static function formatCanvasV3(
		string $baseUrl,
		string $repoSource,// eg local
		string $pageid,
		string $fileName,
		array $canvasMetadata,
		//string $uploader,
		$sourceWidth,
		$sourceHeight,
		$imageResourceId,
		$smallThumb,
		$mediaType
	) {
		$canvasId = $baseUrl . "/Special:IIIFServ/presentation/canvas/{$repoSource}/" . $pageid . ".json";
		$imageAnnotationId = $baseUrl . "/Special:IIIFServ/presentation/annotation/{$repoSource}/" . $pageid . ".json";
		
		if( $repoSource === "local" ) {
			$service = IIIFMwImgAPI::buildIIR( $pageid, $sourceWidth, $sourceHeight, [], "3" );
		} else {
			$service = IIIFMwRemote::buildIIRRemotely( $pageid, $repoSource, $sourceWidth, $sourceHeight, "3" );
		}
		$imageResource = [
			"id" => $imageResourceId,
			"type" => "Image",
			"height" => $sourceHeight,
			"width" => $sourceWidth,
			"service" => [ $service ]
		];
		// @link https://iiif.io/api/cookbook/recipe/0232-image-thumbnail-canvas/
		$thumbnail = [
			"id" => $smallThumb,
			"type" => "Image",
			"format" => $mediaType,
			"width" => 120,
			"height" => LocalFile::scaleHeight( $sourceWidth, $sourceHeight, 120 )
		];
		$label = $repoSource === "local"
			? $fileName
			: str_replace( "File:", "", $fileName );
		$canvas = [
			"id" => $canvasId,
			"type" => "Canvas",
			"label" => [
				"none" => [ $label ]
			],
			"height" => $sourceHeight,
			"width" => $sourceWidth,
			"thumbnail" => [ $thumbnail ],
			"items" => [
				//Painting Annotations
				[
					"id" => $baseUrl . "/Special:IIIFServ/presentation/annotationpage/{$repoSource}/" . $pageid,
					"type" => "AnnotationPage",
					"items" => [
						[
							"id" => $imageAnnotationId,
							"type" => "Annotation",
							"motivation" => "painting",
							// ~V2 'resource'
							"body" => $imageResource,
							// ~V2 'on'
							"target" => $canvasId
						]
					]
				]
			],
			"metadata" => $canvasMetadata
		];
		return $canvas;
	}

	/**
	 * V2 and V3
	 * @param mixed $service
	 * @return string|null
	 */
	private function getImageAPIVersionFromService( $service ) {
		$version = null;
		// Check type
		$type = null;
		if( array_key_exists( "type", $service ) ) {
			$type = $service["type"];
		} elseif( array_key_exists( "@type", $service ) ) {
			$type = $service["@type"];
		}
		switch( $type ) {			
			// ImageService1 and ImageService2 should not 
			// really occur. MAY be iiif:Image in 2.1
			case "ImageService3": $version = "3";
			break;
		}

		// Or check context/@context
		$context = null;
		if( array_key_exists( "@context", $service ) ) {
			$context = $service["@context"];
		} elseif( array_key_exists( "context", $service ) ) {
			$context = $service["context"];
		}
		switch( $context ) {
			case "http://iiif.io/api/image/1/context.json": $version = "1";
			break;
			case "http://iiif.io/api/image/2/context.json": $version = "2";
			break;
			case "http://iiif.io/api/image/3/context.json": $version = "3";
			break;
		}

		return $version;
	}

	/**
	 * Format ImageService according to the image API version provided.
	 * @param string $version - image API version
	 * @param mixed $id
	 * @param mixed $width
	 * @param mixed $height
	 * @param mixed $sizes
	 * @return array|array
	 */
	public static function formatImageService( $version, $id, $width, $height, $sizes ) {
		$service = [];
		if( $version === "3" ) {
			$service = [
				"@context" => "http://iiif.io/api/image/3/context.json",
				"type" => "ImageService3",
				"id" => $id,
				"protocol" => "http://iiif.io/api/image",
				// not [ "http://iiif.io/api/image/3/level0.json" ]
				"profile" => "level0",
				"width" => $width, // $sourceWidth ?
				"height" => $height,
				"sizes" => $sizes // $scaleArr
			];
		} elseif ( $version === "2" ) {
			$service = [
				"@context" => "http://iiif.io/api/image/2/context.json",
				//"@type" => "iiif:Image",// but not supported in 1 or 2.0
				"@id" => $id,
				"profile" => [
					"http://iiif.io/api/image/2/level0.json"
				],
				"protocol" => "http://iiif.io/api/image",
				"width" => $width,
				"height" => $height,
				"sizes" => $sizes
			];
		} elseif( $version === "1" ) {
			// Not supported
		}
		return $service;
	}

}
