"use strict";

( function() {
	const Vue = require("vue");
	const Vuex = require("vuex");

	function initDraggable( Vue, Vuex, App, item ) {
		//const Draggable = require("ext.iiif.lib.draggable");
		const createdApp = Vue.createMwApp( App, {});
		createdApp.use(Vuex);
		createdApp.mount( item );
	}

	const draggableWidgets = document.querySelectorAll(".iiif-draggableplus-widget");
	draggableWidgets.forEach( function(item) {
		var App = require( "ext.iiif.draggableplus.components" ).DraggablePlus;
		initDraggable( Vue, Vuex, App, item );
	});

}() );
