<template>
	<label class="anno-label">{{ label }}</label>
	<div v-for="(item, index) in selectedItems" :key="item.value">
		<div v-if="item.value" class="lookup-item">
			<a :href="item.url">{{ item.label || "" }}</a> 
			<div class="item-description" v-if="item.description !== ''">{{ item.description }}</div>
		</div>
	</div>
</template>

<script>
const { defineComponent, ref, watch, computed } = require( "vue" );
//const store = require("ext.iiif.annotator.store");

module.exports = defineComponent({
	name: "AnnotatorDataLookup",
	//description: "Presents data submitted with a lookup field",
	props: {
		inputValue: { type: String|Array, default: null },
		multiple: { type: Boolean, default: false },
		label: { type: String },
		apiType: { type: String },
		apiUrl: { type: String }
	},
	setup( props ) {
		// props.inputValue
		const selectedValues = typeof props.inputValue === "string" ? [ props.inputValue ] : props.inputValue;
		// selectedItems = values + label fetched from the API 
		const selectedItems = ref( [] );

		for ( const val of selectedValues ) {
			fetchResults( val )
			.then( (data) => {
				switch( props.apiType ) {
					case "wikibase":
						var dataResult = data.search;
						console.log( "getting results???", dataResult );
						var found = dataResult.find( (res) => res.id == val );
						selectedItems.value.push({ 
							value: val,
							label: `${found.label} (${val})` ?? val,
							url: found.concepturi ?? "#",
							description: found.description ?? ""
						});
						break;
					case "reconciliation":
						var dataResult = data.result;
						var found = dataResult.find( (res) => res.id == val );
						selectedItems.value.push({ 
							value: val,
							label: found.name ?? val,
							url: found.other.fullurl ?? "#",
							description: found.description ?? ""
						});
						break;
				}
			});
		}

		// Fetch results from API - @todo move this and the one in FieldLookup to store?
		function fetchResults( searchTerm ) {
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
				//https://www.wikidata.org/w/api.php?action=wbsearchentities&origin=*&format=json&limit=10&props=url&language=en&search=certain
				//https://www.wikidata.org/w/api.php
				var api = props.apiUrl + `?${ params.toString() }`;
			} else if( props.apiType == "reconciliation" ) {
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

		return {
			selectedItems
		}

	}
});
</script>

<style>
.lookup-item {
	display:block;
	margin-bottom:.1rem;
}
.item-description {
	font-size:0.8rem;
	color:#7b7b7b;
}
</style>
