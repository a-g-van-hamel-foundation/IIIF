"use strict";

( function() {
	const Vue = require("vue");
	const Vuex = require("vuex");
	const isAnon = mw.user.isAnon();

	function initTOC( App, item, configData ) {
		const actionApiBaseUrl = mw.config.get("wgServer") + (mw.config.get("wgScriptPath") || "") + "/api.php";
		const actionApi = new mw.ForeignApi( actionApiBaseUrl, { anonymous: isAnon } );
		// formData
		// console.log("configData",configData);

		var profileId = configData.formId ?? null;
		if ( profileId == null || profileId == "" ) {
			// No profile? Use a default form
			finaliseTOCApp( App, getDefaultFormData(), [], {}, configData, item );
			return;
		}

		var getFormProfileData = actionApi.post({
			action: "iiif-wiki",
			formatversion: "2",
			id: profileId
		});

		var getValueData = actionApi.post({
			action: "iiif-wiki",
			formatversion: "2",
			id: configData.targetPageId ?? null,
			slot: configData.targetSlot ?? "main"
		});

		const getManifestData = fetch( configData.iiifManifest )
		.then( function(response) {
			return response.json();
		})
		.catch((error) => {
			console.log(error);
			return;
		});

		$.when( getFormProfileData, getValueData, getManifestData )
			// @dev Don't forget destructuring syntax
		.done( function( [formProfileData], [valueData], manifestData ) {
			if ( !0 in formProfileData ) {
				console.log( "No form data found" );
				return;
			}
			finaliseTOCApp( App, formProfileData, valueData ?? [], manifestData, configData, item );
		});
	}

	function finaliseTOCApp( App, formProfileData, valueData, manifestData, configData, item ) {
		const targetPage = configData.targetPage;
		const targetPageId = configData.targetPageId;
		const targetSlot = configData.targetSlot;
		const iiifManifest = configData.iiifManifest ?? null;
		const canvasIdentifiers = getCanvasIdentifiers(manifestData);
		const createdApp = Vue.createMwApp( App, {
			formProfileData,
			valueData,
			targetPage,
			targetPageId,
			targetSlot,
			iiifManifest,
			canvasIdentifiers
		});
		createdApp.use(Vuex);
		createdApp.mount(item);
	}

	function getDefaultFormData() {
		var formData = {
			"properties": [
				{
					inputType: "textarea",
					name: "Description",
					label: "Descripion"
				}
			]
		};
		return formData;
	}

	/**
	 * @param object manifest
	 * @returns array
	 */
	function getCanvasIdentifiers( manifest ) {
		if ( manifest == undefined || typeof manifest != "object" ) {
			return [];
		}
		// Presentation API version
		let version = null;
		if ( manifest.hasOwnProperty("@context") ) {
			if ( manifest["@context"] == "http://iiif.io/api/presentation/3/context.json" ) {
				version = "3";
			} else if ( manifest["@context"] == "http://iiif.io/api/presentation/2/context.json" ) {
				version = "2";
			} else if( typeof manifest["@context"] == "object" && manifest["@context"].includes( "http://iiif.io/api/presentation/3/context.json" ) ) {
				version = "3";
			}
		}

		const newList = [];
		if ( version == null ) {
			console.log( "No Presentation API version found in manifest" );
			return [];
		} else if ( version == "3" ) {
			const canvases = manifest.items ?? null;
			if ( canvases == null ) {
				return [];
			}
			canvases.forEach( function(canvas) {
				let lgKey = Object.keys(canvas["label"]);
				const label = canvas["label"][lgKey];
				newList.push( { "value": canvas["id"], "label": label } );
			});
		} else if( version == "2") {
			// Traverse to get canvases
			// Current on the faulty assumption there's only one sequence
			const sequences = manifest.sequences ?? null;
			const canvases = sequences != null
				? ( sequences[0]["canvases"] ?? null )
				: null;
			if ( canvases == null ) {
				return [];
			}
			canvases.forEach( function(canvas) {
				newList.push( { "value": canvas["@id"], "label": canvas["label"] } );
			});
		}
		//console.log(newList);
		return newList;
	}

	// Action..

	const tocWidgets = document.querySelectorAll(".iiif-toc-widget");
	tocWidgets.forEach( function(item) {
		const configData = item.dataset;
		var App = require( "ext.iiif.toc.components" ).TOC;
		initTOC( App, item, configData );
	});

}() );
