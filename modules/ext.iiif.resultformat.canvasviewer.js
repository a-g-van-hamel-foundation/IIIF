"use strict";

( function () {
	// Require Vue.js v3
	const Vue = require("vue");
	const Vuex = require("vuex");	

	function htmldecode(str) {
		var txt = document.createElement("textarea");
		txt.innerHTML = str;
		return txt.value;
	}

	function fetchDataAndMountApp(Vue, Vuex, App, item, configProps) {
		const manifestObj = JSON.parse( htmldecode(item.dataset.manifestJson) );
		const canvasItems = JSON.parse( htmldecode(item.dataset.canvasitems) );
		const tileSourcesFromManifest = JSON.parse( htmldecode(item.dataset.tilesources ) );
		const initialAnnotationPages = JSON.parse( htmldecode(item.dataset.annotationpages ) );

		const presentationMethod = item.dataset.presentationMethod;
		const formProfiles = JSON.parse( htmldecode(item.dataset.profiles ) );
		const formProfile = null;

		const summary = JSON.parse( htmldecode(item.dataset.summary) );

		const createdApp = Vue.createMwApp( App, {
			configProps,
			manifestObj,
			canvasItems,
			tileSourcesFromManifest,
			presentationMethod,
			formProfile,
			formProfiles,
			initialAnnotationPages,
			summary
		} );
		createdApp.use( Vuex );
		createdApp.mount( item );
	}

	/* OLD METHOD
	const iiifAnnotationDataWidgets = document.querySelectorAll(".iiif-canvas-viewer");
	iiifAnnotationDataWidgets.forEach( function(item) {
		var App = require( "ext.iiif.annotator.components" ).AnnotatorInterface;
		if (typeof App !== "undefined") {
			const configProps = item.dataset;
			fetchDataAndMountApp( Vue, Vuex, App, item, configProps );
		}
	});
	*/

	var App = require( "ext.iiif.annotator.components" ).AnnotatorInterface;

	// Find relevant elements to mount Vue app; also allow third-party
	// extensions to load module in dynamic content updates
	// (relies on wikipage.content hook).
	mw.hook( "wikipage.content" ).add( ($content) => {
		if ( typeof App === "undefined" ) {
			return;
		}
		$content[0].querySelectorAll( ".iiif-canvas-viewer" ).forEach( function (item) {
			const configProps = item.dataset;
			 if ( !item.dataset.vueMounted ) {
				item.dataset.vueMounted = "1";
				fetchDataAndMountApp( Vue, Vuex, App, item, configProps );
			 }
		});
	});

}() );
