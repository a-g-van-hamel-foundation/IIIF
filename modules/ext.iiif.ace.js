"use strict";

( function () {

	const aceEditors = document.querySelectorAll( ".ace-editor" );

	function initAceEditor( origTextarea, index, basePath ) {
		// To preserve original textarea, create and append new one
		// Create parent wrapper first
		const wrapper = document.createElement( 'div' );
		wrapper.setAttribute( 'class', 'ace-parent' );
		origTextarea.insertAdjacentElement( 'beforebegin', wrapper );
		wrapper.appendChild( origTextarea );

		const aceTextarea = document.createElement( 'textarea' );
		aceTextarea.value = origTextarea.value;
		origTextarea.insertAdjacentElement( 'afterend', aceTextarea );

		ace.config.set( 'basePath', basePath + '/IIIF/modules/lib-ace' );
		var aceEditor = ace.edit( aceTextarea );
		var XmlMode = ace.require("ace/mode/xml").Mode;

		aceEditor.setOptions({
			wrap: true,
			highlightActiveLine: true,
			autoScrollEditorIntoView: true,
			showPrintMargin: false
		});
		aceEditor.session.setMode(new XmlMode() );
		aceEditor.session.setUseWrapMode( true );

		aceEditorId = "ace-editor-textarea-" + (index + 1);
		aceEditor.textInput.getElement().id = aceEditorId;
	   
		aceEditor.session.on('change', function(delta) {
			origTextarea.value = aceEditor.getSession().getValue();
		});
		origTextarea.value = aceEditor.getSession().getValue();
		// Keyboard shortcut to add ex tags
		// A similar feature is in ext.ctc.editor.js
		aceEditor.container.addEventListener('keydown', function(e) {
			if (e.metaKey && e.key === 'i') {
				e.preventDefault();
				const selected = aceEditor.getSelectedText();
				if ( selected !== "" ) {
					console.log( selected );
					var newVal = "<ex>" + selected + "</ex>";
					aceEditor.session.replace( aceEditor.selection.getRange(), newVal );
					var newVal = "";
				}
			}
		});

		aceEditor.session.setUseWrapMode(true);

		// Finally, make aceEditor resizable
		resizeEditor( aceEditor );
	}

	function resizeEditor( aceEditor ) {
		// console.log( 'Resizing...' );
		const resizableEl = aceEditor.textInput.getElement().parentElement;
		const onresize = ( resizableEl, callback) => {
			const resizeObserver = new ResizeObserver( () => callback() );
			resizeObserver.observe( resizableEl );
		};
		onresize( resizableEl, function () {
			aceEditor.resize();
		});
	}

	// Copied from CodeEditor extension
	function getBasePath() {
		var basePath = mw.config.get( 'wgExtensionAssetsPath', '' );
		if ( basePath.slice( 0, 2 ) === '//' ) {
			// ACE uses web workers, which have importScripts, which don't like relative links.
			// This is a problem only when the assets are on another server, so this rewrite should suffice
			// Protocol relative
			basePath = window.location.protocol + basePath;
		}
		return basePath;
	}

	const basePath = getBasePath();
	if ( typeof ace !== 'undefined' ) {
		aceEditors.forEach(function( el, index ) {
			initAceEditor( el, index, basePath );
		});
	} else {
		setTimeout(function() {
			console.log( "Trying to load Ace again..." );
			aceEditors.forEach(function( el, index ) {
				initAceEditor( el, index, basePath );
			});
		}, 1000);
	}

}() );
