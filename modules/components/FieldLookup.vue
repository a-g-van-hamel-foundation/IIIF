<template>
	<div class="lookup-field">
		<div ref="el" class="lookup-chips">
			<template v-for="item in selectedItems" :key="`${item.value}-${dragKey}`" >
				<div  class="lookup-chip">
					{{ item.label || "" }} 
					<template v-if="showValue">(<code>{{ item.value }}</code>)</template>
					<input type="hidden"
						:name="name"
						:value="item.value"
					></input>
					<button class="dismiss no-drag" @mousedown.stop @click.prevent="handleDismissChip(item)">✕</button>
				</div>
			</template>
		</div>
		<cdx-lookup
			:key="name"
			v-model:selected="currentSelection"
			:initial-input-value="currentSelectionLabel"
			:menu-items="menuItems"
			:menu-config="menuConfig"
			aria-label="Lookup..."
			@input="onInput"
			@focus="onFocus"
			:placeholder="placeholder"
		></cdx-lookup>
		<!--dev only: <pre>{{ selectedItems || {} }}</pre> -->
	</div>
</template>

<script>
/**
 * The lookup field has an input that accepts a single value ('currentSelection'), along with a 'currentSelectionLabel' - representing the most recent selection in the input.
 * Selected values (multiple or single) are stored, with labels, in 'selectedItems' and should be represented by chips.
 * Wikimedia Codex now also offers MultiselectLookup, which may be a good alternative
 */

const { defineComponent, ref, reactive, computed } = require( "vue" );
const { CdxLookup, CdxIcon } = require( '@wikimedia/codex' );
const { useDraggable } = VueDraggableLib.VueDraggable;

module.exports = defineComponent( {
	name: "FieldLookup",
	components: { CdxLookup, CdxIcon },
	props: {
		name: { type: String },
		placeholder: { type: String, default: "" },
		selected: { type: [Array,String], default: [] },
		// Not implemented
		//defaultItems: { type: [String, Array ], default: [] },
		multiple: { type: Boolean, default: false },
		// whether to show value after label, between brackets
		showValue: { type: Boolean, default: false },
		// Either API
		apiType: { type: String, default: null },
		apiUrl: { type: String, default: null },
		// ...or options
		options: { type: Array, default: [] },
		customOptions: { type: Object, default: {} }
	},
	watch: {
		selectedItems: {
			handler(n,o) {
				this.debugLog( "FieldLookup, selectedItems", n );
				// @todo these nulls should not happen but may be caused somewhere else
				let filtered = n.filter((t) => t.value != null );
				this.$emit('update:selected', filtered.map(item => item.value ) );
    		},
			// Important!
			deep: true
  		},
		//defaultItems: function(n,o) {
			//debugLog( "defaultItems has changed",n);
			//this.selectedItems = n;
		//},
		selected: function(n,o) {
			//debugLog( "FieldLookup, selected: previous values", o );
			//debugLog( "FieldLookup, selected: new value", n );
		},
		currentSelection: {
			handler(n,o) {
				this.$emit( "emit-lookup-value", n );
				if ( n == "" || n == null ) {
					if ( this.apiType == "reconciliation" ) {
						// re-populate menu with initial suggestions
						this.fetchAPIResultsAndSetMenu("");
					}
					//return;
				}				
				if ( this.dataSourceType == "options" ) {
					this.updateCurrentSelectionLabelForOptions();
				} else {
					this.updateCurrentSelectionLabelForAPI();
				}
			},
			deep: true
		},
		menuItems: function(n,o) {
			// debugLog( "menuItems changed to", n );
		}
	},
	emits: ['update:selected'],
	setup(props, {emit} ) {
		const dragKey = ref(0);

		// Data
		const dataSourceType = props.apiType != null ? "api" : "options";
		// selectedItems: array of objects in the format
		// [ { label: ..., value: ... },{ label: ..., value: ... } ]
		const selectedItems = ref( [] );
		// @dev try this instead in the event of out-of-sync issues
		// const validSelectedItems = computed(() => selectedItems.value.filter(item => item?.value));

		if ( dataSourceType == "api" ) {
			initSetSelectedItemsForAPI( props.multiple, props.selected );
		} else {
			initSetSelectedItemsForOptions( props.selected );
		}

		// Unused
		function getValuesFromSelectedItems( selected ) {
			if ( selected == null || selected == undefined ) {
				return [];
			}
			const valuesOnly = [];
			selectedItems.value.forEach( (item) => {
				valuesOnly.push(item.value);
			});
			return valuesOnly;
		}

		/* BEING REVISED
		const selection = ref( props.selected || null );
		if ( typeof selection == "string" ) {
			selection.value = [ selection ];
		}
		*/

		// Interaction
		const currentSelection = ref();
		const currentSelectionLabel = ref();
		const currentSearchTerm = ref( props.selected || "" );
		// menuItems = list of items actually shown,
		// mutations responsive to search term
		const menuItems = ref( [] );

		// Update selected items once labels have been fetched
		function initSetSelectedItemsForAPI( multiple, selected ) {
			if ( selected == null ) {
				return;
			}
			if ( multiple ) {
				// Attempt rescue if 'multiple' was changed to true
				var newDefaultItems = ( typeof selected == "string" )
					? [ selected ]
					: ( selected ?? [] );
				for ( const defaultItem of newDefaultItems ) {
					fetchLabelAndUpdateSelectedItems( defaultItem );
				}
			} else {
				// Attempt rescue in the event 'multiple' was
				// changed to false. If so, get first item only.
				// @todo Consider warning if > 1
				fetchLabelAndUpdateSelectedItems(
					( typeof selected == "array" ) ? selected[0] : selected
				);
			}
		}

		function initSetSelectedItemsForOptions( selected ) {
			if ( selected == null || props.options == [] ) {
				return;
			}
			const selectedProxy = rescueSelection( selected, "array" );
			for ( const item of selectedProxy ) {
				if ( item == null || item == "" ) {
					continue;
				}
				if ( selectedItems.value == null || typeof selectedItems.value == "undefined" ) {
					selectedItems.value = [];
				}
				//item
				const found = props.options.find( (res) => res.value == item );
				debugLog( "...Found option", found );
				addToSelectedItems( { 
					value: item, 
					label: ( found != undefined ? found.label : item )
				} );
			}
		}

		// If we switched from string to array, etc.,
		// attempt to rescue
		// targetFormat = multple
		function rescueSelection( v, targetDatatype ) {
			if ( typeof v == "string" ) {
				return targetDatatype == "array" ? [ v ] : v;
			} else {
				// proper delimiter unknown
				return targetDatatype == "string" ? v.join( ",")  : v;
			}
			return v;
		}

		// Add selected items once we have retrieved 
		// matching labels from the API
		function fetchLabelAndUpdateSelectedItems( item ) {
			if ( item == null || item == "" ) {
				return;
			}
			if ( selectedItems.value == null || typeof selectedItems.value == "undefined" ) {
				//debugLog( "selectedItems is null/undefined" );
				selectedItems.value = [];
			}
			//debugLog( "running fetchLabelAndUpdateSelectedItems for item", item );
			requestAPIResults( item )
			.then( (data) => {
				if ( data == undefined ) {
					// ?menuItems.value = [];
					return;
				}
				if ( props.apiType == "wikibase" ) {
					// likely the first item (data.search[0]) but look for it anyway
					const found = data.search.find( (res) => res.id == item );
					if ( props.multiple && found !== undefined ) {
						addToSelectedItems( { value: item, label: found.label ?? item } );
					} else if( found !== undefined ) {
						setItem({ value: item, label: found.label ?? item });
					} else {
						debugLog( "Lookup could not find an item in the Wikibase database.", item  );
						setItem({ value: item, label: item + " (?)" });
					}
				} else if( props.apiType == "reconciliation" ) {
					const found = data.result.find( (res) => res.id == item );
					// add (multiple) or replace
					if ( props.multiple && found !== undefined ) {
						addToSelectedItems( { value: item, label: found.name ?? item } );
					} else if( found !== undefined ) {
						setItem({ value: item, label: found.name ?? item });
					} else {
						debugLog( "Lookup could not find an item in the reconciliation API. Perhaps the query changed or the page was deleted?", item  );
						setItem({ value: item, label: item + " (?)" });
					}
				}
			});
		}

		// @todo Flesh this out! Retrieving label should not usually 
		// follow this route
		function updateCurrentSelectionLabelForAPI() {
			requestAPIResults( currentSelection.value )
			.then( ( data ) => {
				if ( props.apiType == "wikibase" ) {
					var dataResult = data.search;
					var labelName = "label";
				} else if ( props.apiType == "reconciliation" ) {
					var dataResult = data.result;
					var labelName = "name";
				} else {
					return;
				}
				if ( dataResult == undefined ) {
					return;
				}
				const found = dataResult.find( (res) => res.id == currentSelection.value );
				if ( found != undefined && found[labelName] != undefined ) {
					currentSelectionLabel.value = found[labelName];
					addCurrentSelectionToSelectedItems( currentSelectionLabel.value );
				} else {
					debugLog( "No label retrieved for selection.value", currentSelection.value );
					debugLog( "because dataResult is this", dataResult );
				}
			});
		}

		function updateCurrentSelectionLabelForOptions() {
			const found = props.options.find( (res) => res.value == currentSelection.value );
			if ( found != undefined && found["label"] != undefined ) {
				currentSelectionLabel.value = found["label"];
				addCurrentSelectionToSelectedItems( currentSelectionLabel.value );
			}
		}

		function addCurrentSelectionToSelectedItems(label) {
			//debugLog( "FieldLookup: currentSelectionLabel changed", n );
			if ( props.multiple ) {
				// Adds to selectedItems
				addToSelectedItems({ value: currentSelection.value, label: label });
			} else {
				selectedItems.value = [{ value: currentSelection.value, label: label }];
			}
			currentSelection.value = "";
		}

		function setItem(item) {
			selectedItems.value = [ item ];
		}

		function addToSelectedItems(newItem) {
			const alreadyExists = selectedItems.value.some(item => item.value === newItem.value);
			if ( alreadyExists ) {
				return;
			}
			selectedItems.value = [...selectedItems.value, newItem];
		}

		// Remove selected item
		function handleDismissChip(item) {
			selectedItems.value = selectedItems.value.filter(
				t => t.value !== item.value
			);
			debugLog( "removed item, new selectedItems.value", selectedItems.value );

			// Reset to allow value being watched to be selected again? No longer needed?
			/*
			if( item.value === currentSelection.value ) {
				// @todo undefined?
				currentSelection.value = "";
				currentSelectionLabel.value = "";
				//addCurrentSelectionToSelectedItems( currentSelectionLabel.value );
				currentSearchTerm.value = "";
			}
			*/
		}

		// Fetch results from API
		function requestAPIResults( searchTerm, offset ) {
			// debugLog( "searchTerm", searchTerm );
			if ( props.apiType == "wikibase" ) {
				const params = new URLSearchParams( {
					origin: '*',
					action: 'wbsearchentities',
					format: 'json',
					limit: '10',
					props: 'url',
					language: 'en',
					search: searchTerm
				} );
				// Not working in MW 1.39
				if ( offset ) {
					params.set( 'continue', String( offset ) );
				}
				//https://www.wikidata.org/w/api.php?action=wbsearchentities&origin=*&format=json&limit=10&props=url&language=en&search=certain
				//https://www.wikidata.org/w/api.php
				var api = props.apiUrl + `?${ params.toString() }`;
			} else if( props.apiType == "reconciliation" && props.apiUrl.substring(0,1) == "@" ) {
				// See #iiif-toc parser function
				// here apiUrl is not a url but a keyed reference 
				// to one in the customOptions object
				var api = props.customOptions[props.apiUrl] + encodeURI( searchTerm );
			} else if( props.apiType == "reconciliation" ) {
				// @todo offset ?
				const paramsLocal = new URLSearchParams({
					origin: '*',
					action: 'recon-suggest-entity',
					format: 'json',
					formatversion: '2',
					limit: '25',
					substr: searchTerm
				});
				//const api = "https://codecs.vanhamel.nl/api.php?action=recon-suggest-entity&format=json&source=smw&profile=69866&offset=0&limit=25&substr=" + ....
				var api = props.apiUrl + encodeURI( searchTerm );
			} else {
				//
			}
			return fetch( api ).then( ( response ) => response.json() );
		}

		function onFocus(e) {
			// If value is empty and type=options or 
			// reconciliation, make sure that menu
			// is populated with initial suggestions
			// (if any - API may not return anything).
			// List should be visible on subsequent keydown.
			if ( ( e.target.value == "" )
				&& ( props.apiType == "reconciliation" )
			) {
				fetchAPIResultsAndSetMenu("");
			} else if( e.target.value == "" && dataSourceType == "options" ) {
				onInputWithOptions("");
			}
		}

		// On input, show results in dropdown menu
		function onInput( value ) {
			// Internally track the current search term.
			currentSearchTerm.value = value;
			
			// (1) Options
			if ( dataSourceType == "options" ) {
				onInputWithOptions(value);
				return;
			}
			// Reset and return if we have no input
			if ( !value || value == "" ) {
				menuItems.value = [];
				return;
			}
			// or (2) API
			fetchAPIResultsAndSetMenu(value);
		}

		function fetchAPIResultsAndSetMenu(value) {
			// Internally track the current search term.
			currentSearchTerm.value = value;

			requestAPIResults( value )
			.then( ( data ) => {
				if ( data == undefined ) {
					menuItems.value = [];
					return;
				}
				// debugLog( "onInput: fetchAPIResultsAndSetMenu, data", data);
				// Make sure this data is still relevant first.
				if ( currentSearchTerm.value !== value ) {
					debugLog( "currentSearchTerm is not equal to value", currentSearchTerm.value, value );
					return;
				}
				if ( props.apiType == "wikibase" ) {
					var dataResult = data.search;
					var labelName = "label";
				} else if ( props.apiType == "reconciliation" ) {
					var dataResult = data.result;
					var labelName = "name";
				}
				// Reset the menu items if there are no results.
				if ( !dataResult || dataResult === undefined || dataResult.length === 0 ) {
					menuItems.value = [];
					return;
				}
				// Build an array of menu items.
				const newMenuRes = dataResult.map( ( res ) => ( {
					value: res.id,
					label: res[labelName] ?? res.id,
					description: res.description ?? ""
				} ) );
				// debugLog( "new menu results ",newMenuRes );
				// Update menuItems.
				menuItems.value = newMenuRes;
			} )
			.catch( () => {
				// On error, set results to empty.
				menuItems.value = [];
			} );
		}

		function onInputWithOptions(value) {
			if ( value && value != "" ) {
				menuItems.value = props.options.filter( ( item ) => item.label.includes( value ) );
			} else {
				menuItems.value = props.options;
			}
		}

		function deduplicateResults( results ) {
			const seen = new Set( menuItems.value.map( ( result ) => result.value ) );
			return results.filter( ( result ) => !seen.has( result.value ) );
		}

		/* Does not work in MW 1.39 */
		function onLoadMore() {
			if ( !currentSearchTerm.value ) {
				return;
			}

			requestAPIResults( currentSearchTerm.value, menuItems.value.length )
			.then( ( data ) => {
				// Wikidata has data.search
				if ( !data || !data.search || data.search.length === 0 ) {
					return;
				}

				const results = data.search.map( ( result ) => ( {
					label: result.label,
					value: result.id,
					description: result.description
				} ) );

				// Update menuItems.
				const deduplicatedResults = deduplicateResults( results );
				menuItems.value.push( ...deduplicatedResults );
			} );
		}

		const menuConfig = {
			visibleItemLimit: 6
		};

		// Draggable
		const el = ref();

		useDraggable( el, selectedItems, {
			immediate: true,
			animation: 150,
			//group: props.name + getRandomNumber() + "-items",
			onStart() {},
			onUpdate() {},
			onEnd() {
				// Force Vue to re-sync its vDOM with a fresh array reference
				selectedItems.value = [...selectedItems.value];
				dragKey.value++;
			},
			filter: 'input,form,fieldset,button,.no-drag',
			preventOnFilter: true
		});

		function getRandomNumber() {
			// 7 digits
			return Math.floor(1000000 + Math.random() * 9000000);
		}

		function debugLog( msg, res ) {
			//console.log( "FieldLookup: " + msg, res || "" );
		}

		return {
			el,
			dragKey,
			dataSourceType,
			selectedItems,
			//validSelectedItems,
			currentSelection,
			currentSelectionLabel,
			menuItems,
			menuConfig,
			// Methods
			//requestAPIResults,
			onFocus,
			onInput,
			onLoadMore,
			fetchAPIResultsAndSetMenu,
			updateCurrentSelectionLabelForAPI,
			updateCurrentSelectionLabelForOptions,
			handleDismissChip,
			addCurrentSelectionToSelectedItems,
			addToSelectedItems,
			deduplicateResults,
			getRandomNumber,
			debugLog
		};
	}
} );

</script>

<style>
.lookup-field {
	background-color: var(--background-color-base, #fff);
	border-radius: 2px;
	border: 1px solid var(--border-color-base,#a2a9b1);
	min-height: 32px;
	box-shadow: inset 0 0 0 1px var(--box-shadow-color-transparent,transparent);
	padding: 4px 8px;
}

.lookup-field .cdx-text-input__input:enabled {
	width:4rem;
}
.lookup-field .cdx-text-input__input:enabled:focus,
.lookup-field .cdx-text-input__input.cdx-text-input__input--has-value:enabled {
	width:100%;
}

.lookup-chips {
	display:flex;
	flex-wrap:wrap;
	gap: .1rem;
}
.lookup-chip {
	padding: .2em .4em;
	font-size:.8rem;
	display: inline-block;
	border-radius:.4rem;
	border:1px solid #a2a9b1;
	line-height: 1rem;
	cursor: grab;
}
.lookup-chip .dismiss {
  background:none;
  border:none;
}
</style>
