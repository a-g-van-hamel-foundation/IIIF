<template>

<template v-if="!isEnabled">
	<!-- Form disabled -->
</template>
<template v-else-if="isEnabled">

	<form
		ref="tocform"
		class="toc-form"
		:disabled="isFormDisabled"
	>
		<div class="toc-form-header">
			<div>
				<template v-if="itemData[`canvasids`] && itemData[`canvasids`].length ">
					<cdx-button @click.prevent="updateCanvas( itemData[`canvasids`] )" size="small">{{ $i18n( "iiif-toc-creator-canvas-updater-button-text" ).text() }}</cdx-button>
				</template>
			</div>
			<div v-if="hasAlternativeForm" class="select-forms-menu">
				<cdx-button @click.prevent="selectForm('Main')" size="small" :weight="tabWeightMain">{{ $i18n( "iiif-toc-creator-tab-main" ).text() }}</cdx-button>
				<div :key="`menu-` + config.name" v-for="config in alternativeFormConfigs">
					<cdx-button @click.prevent="selectForm(config.name)" size="small" :weight="tabWeightAlternative">{{ config.name }}</cdx-button>
				</div>
			</div>
		</div>

		<template v-for="config in alternativeFormConfigs"	
			:key="`form-` + config.name"
		>
			<fieldset ref="alt-tocfieldset" v-if="selectedForm == config.name" class="toc-fieldset">
				<template
					v-for="field in alternativeFormFields"	
					:key="`alt-` + field.name"
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
						:allow-tags="field.allowTags"
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
		</template>

		<fieldset ref="tocfieldset" v-if="isMainFormEnabled" class="toc-fieldset">
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
					:allow-tags="field.allowTags"
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
</template>

<!-- @submit.prevent="submitForm"
 :key="uniqueId + `-` + field.name"
  -->

<script>
const { defineComponent, ref, reactive, watch, computed, defineModel, toRaw } = require( "vue" );
//const store = require("ext.iiif.annotator.store");
const { CdxTextArea, CdxTextInput, CdxButton, CdxIcon } = require( '@wikimedia/codex' );
//const { cdxIconCheck, cdxIconClose, cdxIconEllipsis } = require( "./icons.json" );
const DynamicFormField = require( "./DynamicFormField.vue" );
const { storeToRefs } = require( "pinia" );
const useTOCStore = require( "../stores/toc.js" );

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
	emits: [ 'update:value-data', 'update-field' ],
	setup(props, { emit } ) {
		// Pinia setup
		const store = useTOCStore();
		const { makeCanvasNavigationRequestById } = store;

		// Data
		const itemData = reactive( props.valueData );
		watch( itemData, (n) => {
			emit('update:value-data', n );
		}, { deep: true } );

		// Form
		const uniqueId = ref( Math.floor(Math.random() * 1000000 ) );
		const formFields = ref( {} );
		formFields.value = props.formProfileSchema.properties;
		// Additional field(s) on top of the profileSchema
		debugLog( "props.canvases", props.canvases );
		if ( props.canvases.length !== 0 ) {
			formFields.value = [ {
				name: "canvasids",
				label: "IIIF canvas",
				inputType: "lookup",
				options: props.canvases,
				multiple: true,
				showValue: true,
				required: false,
				allowTags: false
			}, ...formFields.value ];
		}
		debugLog( "TOCForm, props.formProfileSchema", props.formProfileSchema );
		debugLog( "TOCForm, formFields", formFields );

		const selectedForm = ref( props.valueData.Select ?? "Main" );
		const tabWeightMain = ref( selectedForm.value == "Main" ? "normal" : "quiet" );
		const tabWeightAlternative = ref( selectedForm.value == "Main" ? "quiet" : "normal" );

		const hasAlternativeForm = ref( false );
		const alternativeFormConfigs = ref( [] );
		const alternativeFormFields = ref( [] );
		if ( props.formProfileSchema.hasOwnProperty("alternatives") ) {
			hasAlternativeForm.value = true;
			// Going to support only one first
			props.formProfileSchema.alternatives.forEach( ( alt ) => {
				alternativeFormConfigs.value = [ alt, ...alternativeFormConfigs.value ];
				alternativeFormFields.value = alt.properties;
				alternativeFormFields.value = [ {
						name: "Select",
						inputType: "hidden",
						value: selectedForm ?? "Main"
					},
					...alternativeFormFields.value ];
			} );
		}
		const isMainFormEnabled = ref( selectedForm.value == "Main" );
		function selectForm( formName ) {
			selectedForm.value = itemData["Select"] = formName;
			isMainFormEnabled.value = ( formName == "Main" );
			tabWeightMain.value = formName == "Main" ? "normal" : "quiet";
			tabWeightAlternative.value = formName == "Main" ? "quiet" : "normal";
		}

		const isFormDisabled = ref( computed( () => {
			return props.isEnabled ? "" : "disabled";
		}) );

		// If options is an array of strings, we need to convert
		function standardiseOptions( options ) {
			if ( options == null ) {
				return [];
			}
			const newOptions = [];
			options.forEach( ( opt, index ) => {
				if ( typeof opt == "string" ) {
					newOptions[index] = { value: opt, label: opt, }
				} else {
					// hopefully, should be good
					newOptions[index] = opt;
				}
			});
			return newOptions;
		}

		function updateValue( n ) {
			debugLog("TOCForm, updateValue", n );
		}
		// Are we still using using this?
		function onUpdateField( payload ) {
			debugLog( "TOCForm, onUpdateField: payload", payload );
			emit('update-field', payload.key, payload.value);
		}
		
		function updateCanvas( canvasIds ) {
			if ( !Array.isArray( canvasIds ) || canvasIds.length == 0 ) {
				return;
			}
			makeCanvasNavigationRequestById( canvasIds[0] );
		}

		function debugLog( msg, res ) {
			//console.log( "TOCForm: " + msg, res || "" );
		}

		return {
			itemData,
			uniqueId,
			selectedForm,
			tabWeightMain,
			tabWeightAlternative,
			formFields,
			hasAlternativeForm,
			isMainFormEnabled,
			selectForm,
			alternativeFormConfigs,
			alternativeFormFields,
			isFormDisabled,
			standardiseOptions,
			onUpdateField,
			updateValue,
			updateCanvas,
			debugLog
		}
	}

} );
</script>
<style lang="less">
.toc-form-header {
	display: flex;
	justify-content: space-between;
	.select-forms-menu {
		display: flex;
		justify-content: flex-end;
		gap: 0.5rem;
	}
}

.cdx-text-input__input:enabled,
.lookup-field,
.ql-container.ql-snow,
.anno-field .anno-grow-wrap > textarea {
	background-color: #cbe0e0;
}

.lookup-field {
	.lookup-chip {
		background-color: #d5eded;
	}
	code {
		background-color: transparent;
		border: 0;
	}
}

</style>
