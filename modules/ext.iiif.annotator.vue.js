"use strict";

( function () {
	// Require Vue.js v3
	const Vue = require("vue");
	const Vuex = require("vuex");
	Vue.configureCompat( {
		MODE: 3
	} );
	Vue.use(Vuex);

	function fetchManifestDataAndMountApp( Vue, App, item, configProps ) {
		const actionApiBaseUrl = "//" + mw.config.get( "wgServerName" ) + (mw.config.get( "wgScriptPath" ) || "") + "/api.php";
		const actionApi = new mw.ForeignApi( actionApiBaseUrl, { anonymous: true } );
		const manifest = ( typeof configProps.manifest !== "undefined" ) ? configProps.manifest : null;
		if ( manifest === null ) {
			return;
		}

		// One or two API requests
		var iiifOsdApiParams = {
			action: "iiif-osd",
			manifest: manifest,
			formatversion: "2",
			annotations: configProps.target
		};
		if ( configProps.targetSlot !== undefined ) {
			// MCR: get content from slot
			iiifOsdApiParams.slot = configProps.targetSlot;
		}
		// @dev test console.log( "https:" + actionApiBaseUrl + "?" + new URLSearchParams(iiifOsdApiParams) );
		var getManifestData = actionApi.post(iiifOsdApiParams);
		// @todo: get the following from iiif-osd instead
		if ( typeof configProps.profileId !== "undefined" ) {
			var getFormProfileData = actionApi.post({
				action: "iiif-wiki",
				formatversion: "2",
				id: configProps.profileId
			});
		} else {
			var getFormProfileData = false;
		}

		$.when( getManifestData, getFormProfileData )
		.done( function( manifestData, formProfileData ) {
			// dev: data is an array of two objects
			if ( !0 in manifestData ) {
				console.log( "No data from manifest" );
				return;
			}
			const manifestObj = manifestData[0].result.manifest;
			const canvasItems = manifestData[0].result.canvasItems ?? [];
			const tileSourcesFromManifest = manifestData[0].result.imageInformationRequests;
			const formProfile = formProfileData == false ? null : formProfileData[0];
			const formProfiles = {};
			formProfiles[configProps.profileId] = formProfile;
			const initialAnnotationPages = manifestData[0].result.annotations;
			const summary = manifestData[0].result.summary ?? "";
			// Create Vue App with properties
			const createdApp = Vue.createMwApp( App, {
				configProps,
				manifestObj,
				canvasItems,
				tileSourcesFromManifest,
				formProfile,
				formProfiles,
				initialAnnotationPages,
				summary
			} );
			//createdApp.use( Vuex );
			createdApp.mount( item );
		})
		.fail( function( manifestData, formProfileData ) {
			console.log( "No (valid) data retrieved from the API" );
			console.log( "manifestData", manifestData );
			console.log( "formProfileData", formProfileData );
		});
	}

	// @todo .iiif-annotator-widget
	const iiifWidgets = document.querySelectorAll(".iiif-widget");
	iiifWidgets.forEach( function(item) {
		// define App
		var App = require( "ext.iiif.annotator.components" ).AnnotatorInterface;
		if (typeof App !== "undefined") {
			const configProps = item.dataset;
			fetchManifestDataAndMountApp( Vue, App, item, configProps );
		}
	});

}() );
