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

	/* OLD METHOD
	const iiifAnnotationGalleries = document.querySelectorAll(".iiif-annotation-gallery");
	iiifAnnotationGalleries.forEach( function(item) {
		var App = require( "ext.iiif.gallery.components" ).Gallery;
		if (typeof App !== "undefined") {
			fetchDataAndMountApp( Vue, App, item );
		}
	});
	*/

	var App = require( "ext.iiif.gallery.components" ).Gallery;

	// Find relevant elements to mount Vue app; also allow third-party
	// extensions to load module in dynamic content updates
	// (relies on wikipage.content hook).
	mw.hook( "wikipage.content" ).add( ($content) => {
		if ( typeof App === "undefined" ) {
			return;
		}
		
		$content[0].querySelectorAll( ".iiif-annotation-gallery" ).forEach( function ( item ) {
			// vueMounted = Guard against double-mounting on the same element if already handled in a previous firing of the content hook
			if ( !item.dataset.vueMounted ) {
				item.dataset.vueMounted = "1";
				fetchDataAndMountApp( Vue, App, item );
			}
		} );
	} );

}() );
