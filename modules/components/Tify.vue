<template>
	<div
		:id="id"
		:class="wrapperClass"
	></div>
</template>

<script>
const { defineComponent, computed, ref, onMounted } = require("vue");
require("ext.iiif.lib.tify");

module.exports = defineComponent( {
	name: "Tify",
	components: {},
	props: {
		id: { type: String, default: "tify-container" },
		wrapperClass: { type: String, default: "iiif-tify-viewer-for-toc" },
		manifest: { type: String, default: "" }
	},
	setup( props ) {
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
			const tify = new Tify( options );
		});

		return {
			//
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
