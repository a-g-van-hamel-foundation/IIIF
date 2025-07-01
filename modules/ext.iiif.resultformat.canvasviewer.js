"use strict";

( function () {
	// Require Vue.js v3
	const Vue = require("vue");
	const Vuex = require("vuex");
	Vue.configureCompat( {
		MODE: 3
	} );
	Vue.use(Vuex);

	function htmldecode(str) {
		var txt = document.createElement("textarea");
		txt.innerHTML = str;
		return txt.value;
	}

	function fetchDataAndMountApp(Vue, App, item, configProps) {
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
		//createdApp.use( Vuex );
		createdApp.mount( item );
	}

	const iiifAnnotationDataWidgets = document.querySelectorAll(".iiif-canvas-viewer");
	iiifAnnotationDataWidgets.forEach( function(item) {
		var App = require( "ext.iiif.annotator.components" ).AnnotatorInterface;
		if (typeof App !== "undefined") {
			const configProps = item.dataset;
			fetchDataAndMountApp( Vue, App, item, configProps );
		}
	});

}() );
