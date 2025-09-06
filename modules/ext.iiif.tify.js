$( document ).ready(function($) {

	tifyViewers = document.querySelectorAll( ".iiif-tify-viewer" );
	tifyViewers.forEach( function( el ) {
		initTifyViewer( el, el.dataset )
	});

	function initTifyViewer(el, settings) {
		id = el.getAttribute("id");
		console.log( "Testing TIFY..." );
		const TIFY = require( "ext.iiif.lib.tify" );
		const options = {
			container: "#" + id,
			manifestUrl: settings.manifest ?? ""
		};
		if (settings.annotationsVisible == "true") {
			options.annotationsVisible = true;
		}
		if (settings.canvasIndex !== "" ) {
			console.log( "canvasIndex", settings.canvasIndex );
			options.pages = [ parseInt(settings.canvasIndex) ];
		}

		const tifyViewer = new Tify( options );
		//tifyViewer.mount("#" + id);

		tifyViewer.ready.then( () => {
			//tify.setPage([1, 12, 13])	  
			//tifyViewer.setView('thumbnails')
			//tifyViewer.viewer.viewport.zoomTo(2)
			//console.log( tifyViewer.viewer.viewport );
			console.log( tifyViewer.viewer );
			//AnnotoriousOSDController( tifyViewer.viewer );
		});
	}

	/*
	@todo remove

	console.log( "Testing TIFY..." );
	const TIFY = require( "ext.iiif.lib.tify" );
	// const Annotorious = require( "ext.iiif.lib.annotorious" );
	// const AnnotoriousOSD = require( "ext.iiif.lib.annotorious.osd" );

	options = {
		//container: '#iiif-tify-viewer',
		//manifestUrl: 'https://www.isos.dias.ie/static/manifests/RIA_MS_23_P_16.json',
		manifestUrl: "https://codecs.vanhamel.nl/api.php?action=iiif-mw-pres&format=json&formatversion=2&version=2&source=commons&pageids=6228718,1230085,44768586"
	};
	//new Tify( $options ); // with container
	// or
	const tifyViewer = new Tify( options );
	tifyViewer.mount('#iiif-tify-viewer');

	// The viewer object exposes the full OpenSeadragon API. If you want to control the scan view programmatically, the methods of viewer.viewport are probably of interest.

	function AnnotoriousOSDController( viewer ) {
		var options = {
			//adapter: Annotorious.W3CImageFormat('sample-image'),
			drawingEnabled: true,
			 style: {
				fill: '#ff0000',
				fillOpacity: 0.25
			}
		};
		//console.log( AnnotoriousOSD );
		//const anno = AnnotoriousOSD.createOSDAnnotator( viewer, options);
	}

	tifyViewer.ready.then( () => {
		//tify.setPage([1, 12, 13])	  
		//tifyViewer.setView('thumbnails')
		//tifyViewer.viewer.viewport.zoomTo(2)
		//console.log( tifyViewer.viewer.viewport );
		console.log( tifyViewer.viewer );
		//AnnotoriousOSDController( tifyViewer.viewer );
	});
	*/


});
// forgot something here
