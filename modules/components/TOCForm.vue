<template>

	<form
		ref="tocform"
		class="toc-form"
		:disabled="isFormDisabled"
	>
		<fieldset ref="tocfieldset" v-if="isEnabled" class="toc-fieldset">

			<template
				v-for="field in formFields"	
				:key="field.name"
			>
				<dynamic-form-field
					:ref="field.name"
					:name="field.name"
					:input-type="field.inputType || `text`"
					:label="field.label"
					:multiple="field.multiple || false"
					:show-value="field.showValue || false"
					:api-type="field.apiType || null"
					:api-url="field.apiUrl || null"
					:options="standardiseOptions(field.options) || []"
					v-model:input-value="itemData[field.name]"
					:default-value="field.defaultValue"
					@emit-update-value="updateValue"
					@update-field="onUpdateField"
					:wrapper-class="field.wrapperClass || `form-field`"
					:custom-options="customOptions"
				></dynamic-form-field>
			</template>
		</fieldset>
	</form>
</template>

<!-- @submit.prevent="submitForm"
 :key="uniqueId + `-` + field.name"
  -->

<script>
const { defineComponent, ref, watch, computed, defineModel } = require( "vue" );
//const store = require("ext.iiif.annotator.store");
const { CdxTextArea, CdxTextInput, CdxButton, CdxIcon } = require( '@wikimedia/codex' );
//const { cdxIconCheck, cdxIconClose, cdxIconEllipsis } = require( "./icons.json" );
const DynamicFormField = require( "./DynamicFormField.vue" );

module.exports = defineComponent( {
	name: "TOCForm",
	components: {
		CdxTextArea, CdxTextInput,
		CdxButton, CdxIcon,
		DynamicFormField
	},
	props: {
		timestamp: { type: Number, default: 0 },
		isEnabled: { type: Boolean, default: false },
		formProfileSchema: { type: Object, default: {} },
		valueData: { type: Object, default: {} },
		canvases: { type: Array, default: [] },
		customOptions: { type: Object, default: {} }
	},
	emits: [ 'emit-update:valueData', 'update-field' ],
	setup(props, { emit } ) {
		
		// Data
		const itemData = computed({
			get: () => props.valueData,
			set: (value) => emit('emit-update:valueData', value)
		});

		// Form
		const uniqueId = ref( Math.floor(Math.random() * 1000000 ) );
		const formFields = ref( {} );
		formFields.value = props.formProfileSchema.properties;
		// Additional field(s) on top of the
		debugLog( "props.canvases", props.canvases);
		if ( props.canvases.length !== 0 ) {
			formFields.value = [{
				name: "canvas",
				label: "IIIF canvas",
				inputType: "lookup",
				options: props.canvases,
				multiple: true,
				showValue: true,
				required: false
			}, ...formFields.value];
		}
		debugLog( "TOCForm, props.formProfileSchema", props.formProfileSchema );
		debugLog( "TOCForm, formFields", formFields );

		const isFormDisabled = ref( computed( () => {
			return props.isEnabled ? "" : "disabled";
		}) );

		// If options is an array of strings, we need to convert
		function standardiseOptions( options ) {
			if ( options == null ) {
				return [];
			}
			const newOptions = [];
			options.forEach( (opt, index) => {
				if ( typeof opt == "string" ) {
					newOptions[index] = { value: opt, label: opt, }
				} else {
					// hopefully, should be good
					newOptions[index] = opt;
				}
			});
			return newOptions;
		}

		function updateValue(n) {
			debugLog("TOCForm, updateValue", n );
		}
		// Are we still using using this?
		function onUpdateField(payload) {
			debugLog( "TOCForm, onUpdateField: payload", payload );
			emit('update-field', payload.key, payload.value)
		}
		
		function debugLog(msg, res) {
			//console.log( "TOCForm: " + msg, res || "" );
		}

		return {
			itemData,
			uniqueId,
			formFields,
			isFormDisabled,
			standardiseOptions,
			onUpdateField,
			updateValue,
			debugLog
		}
	}

} );
</script>
