const { defineStore } = require( "pinia" );
const { computed, ref, ComputedRef, Ref } = require( "vue" );

/**
 * Pinia store for the TOC creator
 */
module.exports = exports = defineStore( "toc", () => {

	/**
	 * Array of objects identifying all the canvases of a Manifest.
	 * Each object consists of a 'value' holding the canvas ID and
	 * a 'label'.
	 * @type {Ref<?Array>}
	 */
	const canvasIdentifierObjects = ref( null );

	/**
	 * The canvas ID of the current canvas being on display.
	 * @type {Ref<?Object>}
	 */
	const canvasNavigationRequest = ref({
		index: 0,
		requestId: crypto.randomUUID()
	});

	function makeCanvasNavigationRequestById( canvasId ) {
		canvasNavigationRequest.value = {
			index: getCanvasIndexById( canvasId ) ?? 0,
			requestId: crypto.randomUUID()
		}
	}

	function makeCanvasNavigationRequestByIndex( index ) {
		canvasNavigationRequest.value = {
			index: index ?? 0,
			requestId: crypto.randomUUID()
		}
	}

	function getCanvasIndexById( canvasId ) {
		if ( canvasId == null || canvasIdentifierObjects.value == null ) {
			return null;
		}
		return canvasIdentifierObjects.value.findIndex( (item) => item.value == canvasId ) ?? null;
	}

	return {
		canvasIdentifierObjects,
		canvasNavigationRequest,
		makeCanvasNavigationRequestById,
		makeCanvasNavigationRequestByIndex,
		getCanvasIndexById
	}

});
