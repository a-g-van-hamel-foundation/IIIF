<template>
	<template v-if="inputType == 'text' && !multiple ">
		<div class="anno-field">
			<label :for="name">{{ label }}</label>
			<cdx-text-input
				:name="name"
				v-model="inputValue"
				:placeholder="placeholder"
			></cdx-text-input>
		</div>
	</template>

	<template v-else-if="inputType == 'textarea'">
		<div class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-text-area
				:name="name"
				v-model="inputValue"
				:input-value="inputValue"
				:placeholder="placeholder"
				@emit-update-value="updateValue"
			></field-text-area>
		</div>
	</template>

	<template v-else-if="inputType == 'texteditor'">
		<div class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-text-editor
				:name="name"
				v-model="inputValue"
				:input-value="inputValue"
			></field-text-editor>
		</div>
	</template>

	<template v-else-if="inputType == 'xmleditor'">
		<div class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-xml-editor
				:name="name"
				v-model="inputValue"
				:input-value="inputValue"
			></field-xml-editor>
		</div>
	</template>

	<template v-if="inputType == 'select'">
		<div class="anno-field">
			<label :for="name">{{ label }}</label>
			<!-- @update:selected="$emit( 'emit-update-value', $event )"
			 -->
			<input 
				type="hidden"
				:name="name"
				:value="selection"
			></input>
			<cdx-select
				v-model:selected="selection"
				@update:selected="updateSelected"
				:menu-items="menuItems"
				default-label="Select"
				:placeholder="placeholder"
				:multiple="multiple"
			>
				<template #label="{ selectedMenuItem, defaultLabel }">
					<span v-if="selectedMenuItem">
						<span>{{ selectedMenuItem.label }}</span>
					</span>
					<span v-else>
						{{ defaultLabel }}
					</span>
				</template>
			</cdx-select>
		</div>
	</template>

	<template v-else-if="inputType == 'lookup'">
		<div class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-lookup
				:name="name"
				v-model:selected="selection"
				:default-items="inputValue"
				@update:selected="updateSelected"
				@emit-lookup-value="getLookupValue"
				:multiple="multiple"
				:api-type="apiType"
				:api-url="apiUrl"
			></field-lookup>
		</div>
	</template>

	<template v-else-if="inputType == 'rangeslider'">
		<div class="anno-field">
			<label :for="name">{{ label }} (test)</label>
			<field-range-slider
				:name="name"
				:value="inputValue"
			></field-range-slider>
		</div>
	</template>

	<template v-if="inputType == 'hidden' ">
		<input
			type="hidden"
			:name="name"
			:value="defaultValue"
		></input>
	</template>

</template>

<script>
const { defineComponent, ref, watch, computed } = require( "vue" );
const { CdxTextInput, CdxButton, CdxLabel, CdxSelect } = require( '@wikimedia/codex' );
const FieldLookup = require( "./FieldLookup.vue" );
const FieldRangeSlider = require( './FieldRangeSlider.vue' );
const FieldTextArea = require( './FieldTextArea.vue' );
const FieldTextEditor = require( './FieldTextEditor.vue' );
const FieldXmlEditor = require( "./FieldXmlEditor.vue" );

module.exports = defineComponent( {
	name: "DynamicFormField",
	components: {
		CdxTextInput, CdxButton, CdxLabel, CdxSelect,
		FieldLookup, FieldRangeSlider, FieldTextArea, FieldTextEditor, FieldXmlEditor
	},
	props: {
		name: { type: String, default: "" },
		label: { type: String, default: "" },
		inputType: { type: String, default: "text" },
		inputValue: { type: String, default: null },
		multiple: { type: Boolean, default: false },
		apiType: { type: String, default: "wikibase" },
		apiUrl: { type: String, default: "https://www.wikidata.org/w/api.php" },
		placeholder: { type: String, default: "" },
		options: { type: Array, default: [] },
		defaultValue: { type: String, default: "" }
	},
	methods: {
		updateValue(n) {
			//console.log("updateValue",n);
		},
		updateSelected(selected) {
			//console.log('DynamicFormField - updateSelected', selected);
			this.$emit('emit-update-value"', selected );			
		}
	},
	watch: {
		selection: function(n,o) {
			//console.log("DynamicFormField - selection changed", n );
		},
		inputValue: function(n,o) {
			//console.log("DynamicFormField - inputValue changed", n );
		}
	},
	// @todo emits: [ 'emit-update-value' ],
	setup(props, { emit }) {
		const selection = ref( props.inputValue || null );
		if ( props.inputType == "select" || props.inputType == "lookup" ) {
			//console.log( "DynamicFormField: props.inputValue", props.inputValue);
			//selection.value = props.inputValue;
		}
		var menuItems = ref( computed( () => props.options ) );

		function getLookupValue(n) {
			selection.value = n;
			emit( "emit-update-value", n );
		}

		return {
			selection,
			getLookupValue,
			menuItems
		}
	}
});
</script>

<style>
.anno-field {
	margin-bottom: .5rem;
}
.anno-field label {
	font-size: .9rem;
	margin-bottom: 0.4rem;
	font-variant: all-small-caps;
}
.cdx-select {
	min-width: 150px;
	width: 100%;
}
</style>
