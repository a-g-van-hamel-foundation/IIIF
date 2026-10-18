/**
 * useVEField: composable for VisualEditor with VEForAll
 *
 * Registers and removes `initCallbacks` for VEForAll, i.e. 
 * callbacks to execute when VE is fully loaded.
 *
 * @see VEForAll.js (applyVisualEditor)
 * @see ext.veforall.editor.js (initCallbacks, destroy)
 * @see ext.veforall.target.js (updateContent)
 */
const { shallowRef, ref, onMounted, onBeforeUnmount } = require( "vue" );
// const veModuleName = "ext.veforall.main";
// const veWrapperClass = "ve-area-wrapper";
// const vePendingClass = "ve-for-all-waiting-for-update";

/**
 * @param {Ref<HTMLTextAreaElement|null>} textareaRef textarea's template ref
 * @param {Ref<string>} content wikitext content, which is updated
 * whenever the textarea receives new wikitext, whether through plain
 * typing or VE conversion.
 */
module.exports = function useVEField( textareaRef, content ) {
	const target = shallowRef( null );
	const editor = shallowRef( null );
	const veActive = ref( false );

	let initCallback = null;
	let wrapper = null;
	let lastHtml = null;
	// Set and returned by 'sync' to ensure that only one
	// async call is in flight at a time
	let pending = null;
	let destroyed = false;

	onMounted( async () => {
		const el = textareaRef.value;
		// VE reads the textarea once i.e. when it initialises:
		el.value = content.value;
		// Adding ve-area-wrapper is currently still the component's
		// responsibility
		wrapper = el.closest( ".ve-area-wrapper" ) || el.parentElement;

		// When VEForAll triggers 'change', catch it and 'pull' so that
		// any conversion updates content.
		$( el ).on( "change", pull );

		// Add a focusout listener to the wrapper
		// (previously tried blur but that didn't work)
		wrapper.addEventListener( "focusout", onFocusOut );

		// Load VEForAll
		let veLoaded = await loadVEForAll();
		if ( !veLoaded ) {
			return;
		}

		// Exit if editor has been unmounted/destroyed
		if ( destroyed ) {
			return;
		}

		// Register a callback BEFORE calling applyVisualEditor()
		// VEForAll calls it with `this` i.e. its Editor
		initCallback = function () {
			if ( this.$node[ 0 ] !== el ) {
				return;
			}
			editor.value = this;
			target.value = this.target;
			// Defer `removeInitCallback` until VEForAll is done
			// going over the array
			Promise.resolve().then( removeInitCallback );
		};
		mw.veForAll.Editor.prototype.initCallbacks.push( initCallback );

		// Finally, apply VisualEditor through VEForAll
		$( el ).applyVisualEditor();
		veActive.value = true;
	} );

	onBeforeUnmount( () => {
		destroyed = true;
		removeInitCallback();
		if ( textareaRef.value ) {
			$( textareaRef.value ).off( "change", pull );
		}
		if ( wrapper ) {
			wrapper.removeEventListener( "focusout", onFocusOut );
		}
		if ( editor.value ) {
			// Destroy editor (mw.veForAll.Editor.prototype.destroy)
			editor.value.destroy();
		}
	} );

	/** 
	 * 'Syncs' i.e. converts the VE surface to wikitext if it changed
	 * since the last conversion.
	 */
	function sync() {
		if ( pending ) {
			return pending;
		}
		const t = target.value;
		const el = textareaRef.value;
		if ( destroyed || !t || !el ) {
			return Promise.resolve();
		}

		let html;
		try {
			html = t.getSurface().getHtml();
		} catch ( e ) {
			// Surface not ready (yet)
			return Promise.resolve();
		}
		if ( html === lastHtml ) {
			// Nothing changed. No API request
			return Promise.resolve();
		}

		// `updateContent()` converts only when this class is set as
		// a flag and the textarea is hidden (VE mode).
		// Setting it explicitly instead of relying on focus.
		el.classList.add( "ve-for-all-waiting-for-update" );
		pending = Promise.resolve( t.updateContent() )
			.then( () => {
				lastHtml = html;
				// update `content` so Vue won't re-render with
				// a stale value
				pull();
			} )
			.finally( () => {
				pending = null;
			} );
		return pending;
	}

	/**
	 * Pull from textarea to set `content`. Should work for both
	 * VE and plain textarea mode. VE mode runs on the jQuery 'change'
	 * that VEForAll triggers after writing converted wikitext.
	 * Important to not to break model binding with textarea.
	 */
	function pull() {
		if ( textareaRef.value ) {
			content.value = textareaRef.value.value;
		}
	}

	/**
	 * Soft dependency on 'ext.veforall.main'
	 * @returns 
	 */
	async function loadVEForAll() {
		// Soft dependency on 'ext.veforall.main': without VEForAll
		// this stays a plain textarea
		if ( mw.loader.getState( "ext.veforall.main" ) === null ) {
			console.warn( "VEForAll is not installed" );
			return false;
		}
		try {
			await mw.loader.using( "ext.veforall.main" );
			return true;
		} catch ( e ) {
			// Module is registered but failed to load
			// e.g. because VisualEditor is missing
			console.error( "VEForAll failed to load" );
			return false;
		}
		return true;
	}

	const debouncedSync = OO.ui.debounce( () => {
		// failures surface via getWikitext() at save time
		sync().catch( () => {} );
	}, 500 );

	// https://developer.mozilla.org/en-US/docs/Web/API/Element/focusout_event
	// https://developer.mozilla.org/en-US/docs/Web/API/FocusEvent/relatedTarget
	function onFocusOut( e ) {
		// Ignore focus changing within the field, e.g.
		// moving from surface to toolbar, etc.
		if ( !wrapper.contains( e.relatedTarget ) ) {
			debouncedSync();
		}
	}

	/**
	 * Flushes pending VE changes and returns the current wikitext.
	 */
	async function getWikitext() {
		// 'sync' resolves immediately if there's no VE or nothing changed
		await sync();
		pull();
		return content.value;
	}

	function removeInitCallback() {
		if ( !initCallback ) {
			return;
		}
		const list = mw.veForAll.Editor.prototype.initCallbacks;
		const i = list.indexOf( initCallback );
		if ( i !== -1 ) {
			list.splice( i, 1 );
		}
		initCallback = null;
	}

	return {
		target,
		veActive,
		sync,
		getWikitext
	};
};
