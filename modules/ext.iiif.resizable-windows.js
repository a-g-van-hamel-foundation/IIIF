"use strict";

( function() {
	const Vue = require( "vue" );
	const Pinia = require( "pinia" );

	function init( App, item, configData, left, right ) {
		const createdApp = Vue.createMwApp( App, {
			configData,
			leftContent: Vue.markRaw( left ),
			rightContent: Vue.markRaw( right )
		} );
		const pinia = Pinia.createPinia();
		createdApp.use( pinia );
		createdApp.mount( item );
	}

	mw.loader.using( [ 'vue' ] ).then( () => {
		mw.hook( 'wikipage.content' ).add( () => {
			const widgets = document.querySelectorAll( ".iiif-resizable-windows" );
			widgets.forEach( function( item ) {
				const left = item.getElementsByClassName( 'window-1' )[0];
				const right = item.getElementsByClassName( 'window-2' )[0];
				if ( !left || !right ) {
					console.log( "Could not find left or right content");
					return;
				}
				const App = require( "ext.iiif.resizablewindows.components" ).ResizableWindowsStandalone;
				init( App, item, item.dataset, left, right );
			});

		} );
	} );

})();
