<template>
	<div
		:id="id"
		:class="wrapperClass"
	></div>
</template>

<script>
const { defineComponent, computed, ref, onMounted, watch } = require( "vue" );
require( "ext.iiif.lib.tify" );
const { storeToRefs } = require( "pinia" );
const useTOCStore = require( "../stores/toc.js" );

module.exports = defineComponent( {
	name: "Tify",
	components: {},
	props: {
		id: { type: String, default: "tify-container" },
		wrapperClass: { type: String, default: "iiif-tify-viewer-for-toc" },
		manifest: { type: String, default: "" }
	},
	setup( props ) {
		// Pinia setup
		const store = useTOCStore();
		const { canvasNavigationRequest } = storeToRefs( store );

		const tify = ref( null );
		//id
		//manifest
		onMounted( () => {
			const options = {
				container: "#" + props.id,
				manifestUrl: props.manifest ?? ""
			};
			// @todo consider 
			// options.annotationsVisible = true;
			// options.pages
			// options.setView .. [ "export", "help", "info", "fulltext", "text", "thumbnails", "toc" ]
			tify.value = new Tify( options );

			/* Example
			// Wait for Tify to initialise its viewer
			tify.value?.ready.then( () => {
				const osdViewer = tify.value.viewer;
				osdViewer.addHandler( 'open', (e) => {
					console.log( "Tify OSD open", e );
				});
			});
			*/

		} );

		watch( () => canvasNavigationRequest.value, (request) => {
			if ( request.index == null ) {
				return;
			}
			goToCanvas( request.index );
		});

		function goToCanvas( index ) {
			if ( !tify.value ) {
				console.log( "Tify is not ready" );
				return;
			}
			tify.value.setPage( index );
		}

		return {
			tify,
			canvasNavigationRequest
		};
	}
} );
</script>

<style>
.iiif-tify-viewer-for-toc {
	width: 100%;
	height: 100%;
}
.iiif-tify-viewer-for-toc--fh {
	width: 100%;
	height: 500px;
}
</style>
