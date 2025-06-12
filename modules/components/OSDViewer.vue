<template>
<div class="osd-viewer">
	<div class="osd-viewer-toolbar">
		<osd-viewer-navigation
			class="osd-nav-toolbar"
			:id="toolbarId"
			:length="length"
			:initial-index="pageIndex"
			:menu-items="tocMenuItems"
			:is-enabled="isNavigationEnabled"
			@send-nav-direction="getNavDirection"
		></osd-viewer-navigation>
		<osd-viewer-tools
			:rotation="rotation"
			@send-rotation="setRotation"
			:full-screen="fullScreen"
			@send-full-screen="setFullScreen"
			@emit-filters="setFilters"
		></osd-viewer-tools>
	</div>
	<div
		:id="id"
		class="osviewer"
		style="height:calc(100vh - 100px);width:auto;"
	></div>
</div>
</template>

<script>
const { defineComponent, ref, computed } = require("vue");
const store = require("ext.iiif.annotator.store");
const OpenSeadragon = require("openseadragon");
const OSDViewerNavigation = require("./OSDViewerNavigation.vue");
const OSDViewerTools = require("./OSDViewerTools.vue");

module.exports = defineComponent( {
	name: "OSDViewer",
	components: {
		"osd-viewer-navigation": OSDViewerNavigation,
		"osd-viewer-tools": OSDViewerTools
	},
	props: {
		id: {
			type: String, default: "openseadragon-viewer"
		},
		// needed for fullscreen only
		containerId: {
			type: String
		},
		prefixUrl: {
			type: String
		},
		sequenceMode: {
			type: Boolean, default: true 
		},
		tileSources: {
			type: Array, default: []
		},
		tocMenuItems: {
			type: Array, default: []
		},
		canvasItems: {
			type: Array, default: []
		},
		isNavigationEnabled: {
			type: Array, default: true
		}
	},
	data() {
		return {
			toolbarId: this.id + "-toolbar",
			options: {
				id: this.id,
				prefixUrl: this.prefixUrl,
				sequenceMode: this.sequenceMode,
				tileSources: this.tileSources,
				showRotationControl: true,
				showSequenceControl: false,
				showNavigationControl: false,
				preserveViewport: true,
				maxZoomPixelRatio: 4,
				toolbar: this.toolbarId,
				// showReferenceStrip: false,
				initialPage: this.pageIndex ?? 0
			},
			OSDViewer: null,
			length: this.tileSources.length,
			rotation: 0,
			fullScreen: false
		}
	},
	computed: {
		
	},
	methods: {
		// initialise OSD viewer and expose it to parent
		initOSDViewer(options) {
			this.OSDViewer = new OpenSeadragon.Viewer(options);
			// Emit viewer back to parent
			this.$emit('emitOsdViewer', this.OSDViewer);
			store.commit("updateCurrentCanvas", this.canvasItems[this.pageIndex]);

			this.OSDViewer.addHandler('page', function(event) {  				
				this.pageIndex = event.page;
			});
			this.OSDViewer.addHandler('open', function(evt) {
				//console.log( "OPENED", evt );
				// includes current canvas id and eventSource
			});
		},
		// Let navigation menu direct to new page index
		getNavDirection( newIndex ) {
			var currentIndex = this.getCurrentPageIndex();
			if ( typeof newIndex !== 'undefined' ) {
				this.updatePageIndex( newIndex );
			}
		},
		getCurrentPageIndex() {
			return this.OSDViewer.currentPage();
		},
		updatePageIndex(index) {
			this.debugLog( "updatePageIndex with index", index );
			this.pageIndex = index;
			if ( this.OSDViewer !== null ) {
				this.OSDViewer.goToPage(index);
			}
			this.updateCanvasIndexInUrl(index);
			this.debugLog( "this.canvasItems[index]", this.canvasItems[index] );
			store.commit("updateCurrentCanvas", this.canvasItems[index]);
		},
		updateCanvasIndexInUrl(index) {
			const baseUrl = window.location.origin + window.location.pathname;
			const urlParams = new URLSearchParams(window.location.search);
			urlParams.set("cdx", index);
			history.replaceState( { canvasIndex: index }, "", baseUrl + "?" + urlParams );
		},
		setRotation(newRotation) {
			this.rotation = newRotation;
			this.OSDViewer.viewport.setRotation( newRotation );
		},
		setFullScreen( fullScreen ) {
			// We are not using this.OSDViewer.setFullScreen( fullScreen );
			const osdViewerEl = document.getElementById(this.id);
			const container = document.getElementById(this.containerId);
			if ( fullScreen ) {				
				container.requestFullscreen();
				osdViewerEl.style.height = "100vh";
			} else {
				osdViewerEl.style.height = "calc(100vh - 100px)";
				// Check we are still in fullscreen mode
				// because user may have pressed ESC
				if ( document.fullscreenElement ) {
					document.exitFullscreen();
				}
			}
		},
		zoomIn() {
			const currentZoom = this.OSDViewer.viewport.getZoom();
			this.OSDViewer.viewport.zoomTo( currentZoom * 1.5 );
		},
		zoomOut() {
			const currentZoom = this.OSDViewer.viewport.getZoom();
			this.OSDViewer.viewport.zoomTo( currentZoom / 1.5 );
		},
		setFilters(v) {
			var canv = this.OSDViewer.canvas.getElementsByTagName("canvas");
			canv[0].style.filter = `brightness(${v.brightness}%) contrast(${v.contrast}%) saturate(${v.saturation}%)`;
		}
	},
	// OSDViewerTools: emitAnnotationToggle
	emits: ["emitAnnotationToggle"],
	watch: {
		currentCanvas(n,o) {
			this.debugLog( "OSDViewer currentCanvas changed to ", n );
		},
		pageIndex(n,o) {
			this.updatePageIndex(n);
		},
		options(n,o) {
			if ( n !== o ) {
				// console.log( "init again...");
				this.initOSDViewer( this.options );
			}
		}
	},
	mounted() {
		if ( this.tileSources.length !== 0 ) {
			this.initOSDViewer( this.options );
		}
	},
	updated() {
		//console.log( "OSDViewer has been updated." );
	},
	setup( props ) {
		const currentCanvas = ref( {} );
		currentCanvas.value = computed( function() {
			return store.getters.getCurrentCanvas;
		});
		const pageIndex = computed( function() {
			return store.getters.getCurrentCanvas.index ?? 0;
		});

		// Dev only
		function debugLog( msg = "(No message)", loggable = "" ) {
			//console.log( "OSDViewer: " + msg, loggable );
		}

		return {
			currentCanvas,
			pageIndex,
			debugLog
		}
	}

} );
</script>

<style>
.osd-viewer-container {
	height:100%;
}
.osd-viewer {
	height:100%;
	max-height:100vh;
}
.osd-viewer-toolbar {
	display:flex;
	justify-content:space-between;
}

</style>
