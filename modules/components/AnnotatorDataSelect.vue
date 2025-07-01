<template>
	<label class="anno-label" v-html="label"></lab>
	<div v-for="(item, index) in selectedItems" :key="item.value">
		<div v-if="item.value" class="lookup-item">
			<a :href="item.url">{{ item.label || "..." }}</a>
		</div>
	</div>
</template>

<script>
const { defineComponent, ref, watch, computed } = require( "vue" );
//const store = require("ext.iiif.annotator.store");

module.exports = defineComponent({
	name: "AnnotatorDataSelect",
	props: {
		inputValue: { type: String|Array, default: null },
		multiple: { type: Boolean, default: false },
		options: { type: Array, default: [] },
		label: { type: String }
	},
	setup( props ) {
		const selectedValues = typeof props.inputValue === "string" ? [ props.inputValue ] : props.inputValue;
		const selectedItems = ref( [] );

		// @todo - non-page values
		for ( const val of selectedValues ) {
			const found = props.options.find( (res) => res.value === val );
			if ( found !== undefined ) {
				console.log( found );
				selectedItems.value.push({
					value: found.value,
					label: found.label,
					url: mw.util.getUrl( found.value )
				});
			}
		}

		return {
			selectedItems
		}
	}
});
</script>
