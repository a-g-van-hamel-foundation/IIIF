"use strict";

( function () {

	const Vue = require("vue");
	//const Vuex = require("vuex");	

	function htmldecode(str) {
		var txt = document.createElement("textarea");
		txt.innerHTML = str;
		return txt.value;
	}

	function fetchDataAndMountApp(Vue, App, item) {
		const configProps = item.dataset;
		const annotationItems = JSON.parse( htmldecode( configProps.items ) );
		const settings = JSON.parse( htmldecode( configProps.settings ) );
		const createdApp = Vue.createMwApp( App, {
			annotationItems,
			settings,
			configProps
		} );
		//createdApp.use(Vuex);
		createdApp.mount( item );
	}

	const iiifAnnotationGalleries = document.querySelectorAll(".iiif-annotation-gallery");
	iiifAnnotationGalleries.forEach( function(item) {
		var App = require( "ext.iiif.gallery.components" ).Gallery;
		if (typeof App !== "undefined") {
			fetchDataAndMountApp( Vue, App, item );
		}
	});

}() );
