"use strict";

/**
 * Importing and exporting AnnotationPages/Annotations
 * using Annotorious' data model for the 'interior' items
 * @link https://openseadragon.github.io/docs/OpenSeadragon.Viewer.html
 */

// initialAnnotations - what structure?
// initialAnnotations[tileSourceURL] suggests that tileSources are used as keys

Object.defineProperty(exports, "__esModule", {
	value: true
});

exports["default"] = void 0;

const SequenceModePlugin = function SequenceModePlugin(
	anno,
	viewer,
	initialAnnotationPages = [],
	canvasItems = []
) {
	// Convert array to an object 'keyed' by canvasId
	var annotationPages = {};
	initialAnnotationPages.forEach( function(page) {
		var canvasId = page.references.canvasId;
		annotationPages[canvasId] = page;
	});

	var addOrUpdateAnnotationPages = function addOrUpdateAnnotationPages(annotation) {
		var currentPage = viewer.currentPage();
		var currentTileSourceUrl = viewer.tileSources[currentPage];
		var currentCanvasId = canvasItems[currentPage].canvasId;
		var currentCanvasLabel = canvasItems[currentPage].label;

		// Create annotationPage if it does not exist
		if( annotationPages[currentCanvasId] === undefined ) {
			var annotationPageId = currentCanvasId + "/annotPage";
			annotationPages[currentCanvasId] = {
				type: "AnnotationPage",
				id: annotationPageId,
				items: [],
				references: {
					id: annotationPageId,
					index: currentPage,
					canvasId: currentCanvasId,
					canvasLabel: currentCanvasLabel,
					tileSource: currentTileSourceUrl
				}
			};
		}

		// Possibly update existing annotation
		var annotationFound = false;
		annotationPages[currentCanvasId].items.forEach( function(item, index) {
			if ( item.id && item.id === annotation.id) {
				//console.log( "Annotation found, updating it now for index...", index );
				annotationPages[currentCanvasId].items[index] = annotation;
				annotationFound = true;
			}
		});
		// Or else add new annotation
		if ( !annotationFound ) {
			//console.log( "Annotation not found so adding it" );
			annotationPages[currentCanvasId].items.push( annotation );
		}
		// console.log( "addOrUpdate: annotationPages[currentCanvasId]", annotationPages[currentCanvasId] );
	};

	var removePagedAnnotation = function removePagedAnnotation(annotation) {
		var currentPage = viewer.currentPage();
		var currentCanvasId = canvasItems[currentPage].canvasId;

		// Check if annotationPage or annotationPage.items are defined 
		if ( annotationPages[currentCanvasId] === undefined 
			|| annotationPages[currentCanvasId].items === undefined ) {
			return true;
		}

		var annotationIndex = getAnnotationItemIndexByAnnotationId( annotationPages[currentCanvasId], annotation.id );

		if( annotationIndex !== false ) {
			annotationPages[currentCanvasId].items.splice( annotationIndex, 1 );
		} else {
			//console.log( "Annotation index not found for", annotation.id );
			return true;
		}
	};

	// Replaces native methods
	anno.on('createAnnotation', addOrUpdateAnnotationPages);
	anno.on('updateAnnotation', addOrUpdateAnnotationPages);
	anno.on('deleteAnnotation', removePagedAnnotation);

	/**
	 * 
	 */
	// For current page, import annotationPage from initialAnnotation(Page)s 
		// (if any) unless we have it already
		// Perhaps create new AnnotationPage, without items
		// Finally, use setAnnotation for all items
	viewer.addHandler('open', function () {
		var currentPage = viewer.currentPage();
		var currentTileSourceUrl = viewer.tileSources[ currentPage ];
		var currentCanvasId = canvasItems[currentPage].canvasId;
		var currentCanvasLabel = canvasItems[currentPage].label;

		if ( typeof annotationPages[currentCanvasId] === 'undefined' ) {
			annotationPages[currentCanvasId] = {
				type: "AnnotationPage",
				id: currentCanvasId + "/annotPage",				
				items: [],
				references: {
					id: currentCanvasId + "/annotPage",
					index: currentPage,
					canvasId: currentCanvasId,
					canvasLabel: currentCanvasLabel,
					tileSource: currentTileSourceUrl
				}
			};
		}
		anno.setAnnotations( annotationPages[currentCanvasId].items || [] );
	});

	viewer.addHandler('page', function () {
		anno.cancelSelected();
		anno.clearAnnotations();
	});

	function getAnnotationItemIndexByAnnotationId( annotationPage, annotationId ) {
		var val = false;
		annotationPage.items.forEach( function(item, index) {
			if ( item.id && item.id === annotationId ) {
				val = index;
			}
		});
		return val;
	}

	// Export an array of annotations that can be imported again
	anno.exportAllAnnotations = function () {
		// sequential array
		// remove null, et al
		const res = Object.values(annotationPages)
			.filter(function (val) {
				return !!val;
			})
			.filter( (page) => page.items !== undefined && page.items.length > 0 );
		return res;
	}

};
var _default = SequenceModePlugin;
exports["default"] = _default;
