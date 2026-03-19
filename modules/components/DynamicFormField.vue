<template>
	<template v-if="inputType == 'text' && !multiple ">
		<section :class="wrapperClass || `anno-field`">
			<label :for="name">{{ label }}</label>
			<cdx-text-input
				:name="name"
				v-model="selection"
				@input="emit('update:inputValue', $event.target.value)"
				:placeholder="placeholder"
			></cdx-text-input>
		</section>
	</template>

	<template v-else-if="inputType == 'textarea'">
		<section class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-text-area
				:name="name"
				v-model:input-value="inputValue"
				@emit-update-value="updateValue"
				:placeholder="placeholder"
			></field-text-area>
		</section>
	</template>

	<template v-else-if="inputType == 'texteditor'">
		<section class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-text-editor
				:name="name"
				v-model:input-value="selection"
			></field-text-editor>
		</section>
	</template>

	<template v-else-if="inputType == 'xmleditor'">
		<section class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-xml-editor
				:name="name"
				v-model="inputValue"
				:input-value="inputValue"
			></field-xml-editor>
		</section>
	</template>

	<template v-if="inputType == 'select'">
		<section class="anno-field">
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
				:menu-items="menuItems || []"
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
		</section>
	</template>

	<template v-else-if="inputType == 'lookup'">
		<section class="anno-field">
			<label :for="name">{{ label }}</label>
			<field-lookup
				:name="name"
				v-model:selected="selection"
				:default-items="inputValue"
				:multiple="multiple"
				:api-type="apiType"
				:api-url="apiUrl"
				:options="menuItems"
				:custom-options="customOptions"
				@update:selected="updateSelected"
				@emit-lookup-value="getLookupValue"
			></field-lookup>
		</section>
	</template>

	<!-- Not yet ready -->
	<template v-else-if="inputType == 'multiselectlookup'">
		<section class="anno-field">
			<label :for="name">{{ label }}</label>
			<cdx-multiselect-lookup
				:name="name"
				v-model:input-chips="multiselectChips"
				v-model:selected="selections"
				aria-label="Multiselect lookup"
				:menu-items="menuItemsSelected || []"
				:menu-config="multiselectConfig"
				:placeholder="placeholder"
				@input="onMultiselectInput"
				@update:selected="onMultiselectUpdateSelected"
			></cdx-multiselect-lookup>
		</section>
	</template>

	<template v-else-if="inputType == 'rangeslider'">
		<section class="anno-field">
			<label :for="name">{{ label }} (test)</label>
			<field-range-slider
				:name="name"
				:value="inputValue"
			></field-range-slider>
		</section>
	</template>

	<template v-if="inputType == 'hidden' ">
		<input
			type="hidden"
			:name="name"
			:value="defaultValue"
		></input>
	</template>

	<template v-if="inputType == 'free' ">
		<span v-html="defaultValue"></span> 
	</template>

</template>

<script>
const { defineComponent, ref, watch, computed } = require( "vue" );
const { CdxTextInput, CdxButton, CdxLabel, CdxSelect, CdxMultiselectLookup } = require( '@wikimedia/codex' );
const FieldLookup = require( "./FieldLookup.vue" );
const FieldRangeSlider = require( './FieldRangeSlider.vue' );
const FieldTextArea = require( './FieldTextArea.vue' );
const FieldTextEditor = require( './FieldTextEditor.vue' );
const FieldXmlEditor = require( "./FieldXmlEditor.vue" );

module.exports = defineComponent( {
	name: "DynamicFormField",
	components: {
		CdxTextInput, CdxButton, CdxLabel, CdxSelect, CdxMultiselectLookup,
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
		defaultValue: { type: String, default: "" },
		wrapperClass: { type: String, default: "anno-field" },
		customOptions: { type: Object, default: {} }
	},
	watch: {
		selection: function(n,o) {
			//this.debugLog("DFF, selection changed", n );
		},
		selections: function(n,o) {
			this.debugLog( "DFF, selections watched", n );
		},
		inputValue: function(n,o) {
			//this.debugLog("DFF, inputValue changed", n );
		}
	},
	// @todo emits: [ 'emit-update-value' ],
	emits: ['update:inputValue'],
	setup(props, { emit }) {
		// const selection = ref( props.inputValue || null );
		// proxy for props.inputValue
		const selection = computed({
			get() { return props.inputValue },
      		set(val) { emit('update:inputValue', val) }
		});
		// Multiselect:
		const selections = ref( [] );
		/*
		selections.value = computed({
			get() { return props.inputValue },
      		set(val) { emit('update:inputValue', val) }
		});
		*/

		// 'select' and 'multiselect'
		const menuItems = ref( computed( () => props.options ) );
		/*
		function standardiseOptions(options) {
			const items = [];
			Object.keys(options).forEach(function(key,index) {
				items[key] = options[key];
			});
			return items;
		}
		*/

		// 'lookup'
		function getLookupValue(n) {
			debugLog("DFF, getLookupValue: ",n );
			debugLog("DFF, getLookupValue, current selection: ", selection.value );
			if ( n == null ) {
				// ????
				//return;
			}
			//selection.value = n;
			if ( typeof selection.value == "array" ) {
				selection.value.push( n );
			} else {
				selection.value = [ n ];
			}
			emit( "emit-update-value", n );
		}

		function updateSelected(n) {
			debugLog('DFF, updateSelected', n);
			emit('emit-update-value', n );
		}

		function updateValue(n) {
			debugLog("DFF, updateValue: ",n);
			emit('update:inputValue', n);
		}

		// Multiselect
		const multiselectChips = ref( [] );
		const multiselectConfig = {
			boldLabel: false,
			visibleItemLimit: 6
		};
		const menuItemsSelected = ref( [] );
		function onMultiselectInput( value ) {
			if ( value ) {
				menuItemsSelected.value = menuItems.value.filter( ( item ) => item.label.includes( value ) );
			} else {
				menuItemsSelected.value = [];
			}
		}
		// Similar to updateSelected
		function onMultiselectUpdateSelected(n) {
			debugLog( 'DFF (multiselect), current selections:', n.join( ', ' ) );
			emit( "emit-update-value", n );
		}

		// Dev only
		function debugLog( msg, res ) {
			//console.log( "DFF: " + msg, res || "" );
		}

		return {
			emit,
			selection,
			selections,
			getLookupValue,
			updateValue,
			updateSelected,
			menuItems,

			menuItemsSelected,
			multiselectChips,
			multiselectConfig,
			onMultiselectUpdateSelected,
			onMultiselectInput,

			debugLog
		}
	}
});
</script>

<style>
.form-field,
.anno-field {
	margin-bottom: .5rem;
}
.form-field label,
.anno-field label {
	font-size: .9rem;
	margin-bottom: 0.4rem;
	font-variant: all-small-caps;
}

.form-field-horizontal {
	display:flex;
	gap: .5rem;
	margin-bottom: 0.5rem;
}
.form-field-horizontal label {
	width: 5rem;
	font-size: .9rem;
	font-variant: all-small-caps;
}
.form-field-horizontal > div {
	width: 100%;
}

.form-field-compact {
	display: inline-flex;
	margin-bottom: .5rem;
}
.form-field-compact label {
	margin-right: .5rem;
	font-size: .9rem;
	font-variant: all-small-caps;
}
.form-field-compact .cdx-text-input {
	min-width:4rem;
}
.form-field-compact input {
	width: 5rem;
}

.cdx-select {
	min-width: 150px;
	width: 100%;
}
</style>
