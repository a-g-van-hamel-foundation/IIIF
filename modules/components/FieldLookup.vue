<template>
	<!-- @load-more="onLoadMore" -->
	<cdx-lookup
		v-model:selected="selection"
		:initial-input-value="selectionLabel"
		:menu-items="menuItems"
		:menu-config="menuConfig"
		aria-label="Lookup..."
		@input="onInput"
		:placeholder="placeholder"
	></cdx-lookup>
	<div class="lookup-chips">
		<!-- one only currently-->
		<template
			ref="lookup-input"
			v-if="selection !== null && selectionLabel !== null">
			<!--
			<div class="lookup-chip">
				{{ selectionLabel ?? "..." }} <small class="lookup-chip-identifier">({{ selection }})</small>
			</div>
			-->
		</template>
		<!-- single or multiple -->
		<div v-for="(item, index) in selectedItems" :key="item.value">
			<div v-if="item.value" class="lookup-chip">
				{{ item.label || "" }} (<code>{{ item.value }}</code>)
				<input
					type="hidden"
					:name="name"
					:value="item.value"
				></input>
				<button class="dismiss" @click.prevent="onDismissChip(item)">✕</button>
			</div>
		</div>
		<!--Dev only:
		<code>Selection: {{ selection }} / Default items: {{ defaultItems }} / Selected: {{ selectedItems }}</code>-->
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require( "vue" );
const { CdxLookup, CdxIcon } = require( '@wikimedia/codex' );

module.exports = defineComponent( {
	name: "FieldLookup",
	components: { CdxLookup, CdxIcon },
	props: {
		name: { type: String },
		placeholder: { type: String, default: "" },
		selected: { type: String },
		defaultItems: { type: [String, Array ], default: null },
		inputValue: { type: String },
		multiple: { type: Boolean, default: false },
		apiType: { type: String },
		apiUrl: { type: String }
	},
	watch: {
		defaultItems: function(n,o) {
			//console.log( "FieldLookup - defaultItems has changed",n);
			//this.selectedItems = n;
		},
		selected: function(n,o) {
			//console.log( "FieldLookup - selected has changed",n );
		},
		selection: function(n,o) {
			//console.log( "FieldLookup - selection changed", n );
			this.$emit( "emit-lookup-value", n );
			this.updateCurrentSelectionLabel();
		},
		selectionLabel: function(n,o) {
			//console.log( "FieldLookup: selectionLabel changed", n );
			if ( this.multiple ) {
				this.selectedItems.push( { value: this.selection, label: n } );
			} else {
				this.selectedItems = [{ value: this.selection, label: n }];
			}
		}
	},
	setup(props) {
		const selection = ref( props.selected || null );
		const selectionLabel = ref( null );
		const selectedItems = ref( [] );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( props.selected || "" );

		// Update selected items once labels have been fetched
		if (props.multiple && props.defaultItems !== null) {
			// Attempt rescue if 'multiple' was changed to true
			var newDefaultItems = ( typeof props.defaultItem == "string" )
				? [ props.defaultItems ]
				: props.defaultItems ?? [];
			for ( const defaultItem of newDefaultItems ) {
				fetchLabelAndUpdateSelectedItems( defaultItem );
			}
		} else if(!props.multiple && props.defaultItems !== null ) {
			// Attempt rescue in the event 'multiple' was
			// changed to false. If so, get first item only.
			// @todo Consider warning if > 1
			fetchLabelAndUpdateSelectedItems(
				( typeof props.defaultItem === "array" ) ? props.defaultItems[0] : props.defaultItems
			);
		}

		// Add selected items once we have retrieved 
		// matching labels from the API
		function fetchLabelAndUpdateSelectedItems( item ) {
			//console.log( "running fetchLabelAndUpdateSelectedItems for item", item );
			fetchResults( item )
			.then( (data) => {
				if ( props.apiType == "wikibase" ) {
					// likely the first item (data.search[0]) but look for it anyway
					const found = data.search.find( (res) => res.id == item );
					if ( props.multiple && found !== undefined ) {
						selectedItems.value.push({ value: item, label: found.label ?? item });
					} else if( found !== undefined ) {
						selectedItems.value = [{ value: item, label: found.label ?? item }];
					} else {
						console.log( "Lookup could not find an item in the Wikibase database.", item  );
						selectedItems.value = [{ value: item, label: item + " (?)" }];
					}
				} else if( props.apiType == "reconciliation" ) {
					const found = data.result.find( (res) => res.id == item );
					// add (multiple) or replace
					if ( props.multiple && found !== undefined ) {
						selectedItems.value.push({ value: item, label: found.name ?? item });
					} else if( found !== undefined ) {
						selectedItems.value = [{ value: item, label: found.name ?? item }];
					} else {
						console.log( "Lookup could not find an item in the reconciliation API. Perhaps the query changed or the page was deleted?", item  );
						selectedItems.value = [{ value: item, label: item + " (?)" }];
					}
				}
			});
		}

		// @todo Flesh this out! Retrieving label should not usually 
		// follow this route
		function updateCurrentSelectionLabel() {
			//console.log( "updateCurrentSelectionLabel");
			fetchResults( selection.value )
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
				const found = dataResult.find( (res) => res.id == selection.value );
				if ( found !== undefined ) {
					selectionLabel.value = found[labelName];
				} else {
					console.log( "No label retrieved for selection.value", selection.value );
					console.log( "because dataResult is this", dataResult );
				}
			});
		}

		// Remove selected item
		function onDismissChip(item) {
			selectedItems.value = selectedItems.value.filter( 
				(t) => t !== item
			);
			// Reset to allow value being watched to be selected again
			if( item.value === selection.value ) {
				selection.value = selectionLabel.value = null;
				currentSearchTerm.value = "";				
			}
		}

		// Fetch results from API
		function fetchResults( searchTerm, offset ) {
			// console.log( "searchTerm", searchTerm );
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
			} else if( props.apiType == "reconciliation" ) {
				// @todo unused; no support for offset
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
			}
			return fetch( api ).then( ( response ) => response.json() );
		}

		function onInput( value ) {
			// Internally track the current search term.
			currentSearchTerm.value = value;
			// Do nothing if we have no input.
			if ( !value ) {
				menuItems.value = [];
				return;
			}

			fetchResults( value )
			.then( ( data ) => {
				// console.log( "onInput: fetchResults, data", data);
				// Make sure this data is still relevant first.
				if ( currentSearchTerm.value !== value ) {
					// console.log( "currentSearchTerm is not equal to value", currentSearchTerm.value, value );
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
					//console.log( "No results" );
					menuItems.value = [];
					return;
				}
				// Build an array of menu items.
				const newMenuRes = dataResult.map( ( res ) => ( {
					value: res.id,
					label: res[labelName] ?? res.id,
					description: res.description ?? ""
				} ) );
				// console.log( "new menu results ",newMenuRes );
				// Update menuItems.
				menuItems.value = newMenuRes;
			} )
			.catch( () => {
				// On error, set results to empty.
				menuItems.value = [];
			} );
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

			fetchResults( currentSearchTerm.value, menuItems.value.length )
				.then( ( data ) => {
					// Wikidata has data.search
					if ( !data.search || data.search.length === 0 ) {
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

		return {
			selectedItems,
			selection,
			selectionLabel,
			menuItems,
			menuConfig,
			// Methods
			fetchResults,
			onInput,
			onLoadMore,
			updateCurrentSelectionLabel,
			onDismissChip
		};
	}
} );

</script>

<style>
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
}
.lookup-chip .dismiss {
  background:none;
  border:none;
}
</style>
