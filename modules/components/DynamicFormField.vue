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
				v-model:input-value="selection"
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
				:value="selectedItem"
			></input>
			<cdx-select
				:selected="selectedItem"
				@update:selected="updateSelectedItem"
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
				:multiple="multiple"
				:show-value="showValue"
				:api-type="apiType"
				:api-url="apiUrl"
				:options="menuItems"
				:custom-options="customOptions"
				@emit-lookup-value="updateLookupValue"
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
		// Lookup: whether to show value after label, between brackets
		showValue: { type: Boolean, default: false },
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
			this.debugLog("DFF, selection watched is now", n );
		},
		selections: function(n,o) {
			this.debugLog( "DFF, selections watched is now", n );
		},
		inputValue: function(n,o) {
			this.debugLog("DFF, inputValue watched is now", n );
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
		// selectedItem may eventually replace selection
		const selectedItem = ref( props.inputValue ?? null );
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
		function updateLookupValue(n) {
			debugLog("DFF, updateLookupValue: ",n );
			debugLog("DFF, updateLookupValue, current selection: ", selection.value );
			if ( Array.isArray(selection.value) && n != null ) {
				const alreadyExists = selection.value.some(item => item.value === n.value);
				if ( !alreadyExists ) {
					selection.value = [ ...selection.value, n ];
				}
			} else if( n != null ) {
				selection.value = [ n ];
			}
			// emit( "emit-update-value", n );
		}

		function updateSelectedItem(n) {
			debugLog('DFF, updateSelectedItem', n);
			selectedItem.value = n;
			emit('update:inputValue', n);
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
		// Similar to updateSelectedItem
		function onMultiselectUpdateSelected(n) {
			debugLog( 'DFF (multiselect), current selections:', n.join( ', ' ) );
			//emit( "emit-update-value", n );
		}

		// Dev only
		function debugLog( msg, res ) {
			//console.log( "DFF: " + msg, res || "" );
		}

		return {
			emit,
			selection,
			selectedItem,
			selections,
			updateValue,
			updateLookupValue,
			updateSelectedItem,
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

<style lang="less">
.anno-field {
	.cdx-select {
		min-width: 150px;
		width: 100%;
	}
	.cdx-select-vue__handle {
		// Keep things in the middle
		line-height: inherit;
	}
	.ace_editor {
		border: 1px solid #a2a9b1;
		box-shadow: inset 0 0 0 5px transparent;
		transition-property: background-color,color,border-color,box-shadow;
		transition-duration: .25s;
		&.ace_focus {
			border: 1px solid #36c;
			box-shadow: inset 0 0 0 5px #36c;
			outline: 1px solid transparent;
		}
	}
	.ace_scroller {
		padding: 8px 4px;
	}
}

.form-field,
.anno-field {
	margin-bottom: .5rem;
	label {
		font-size: .9rem;
		margin-bottom: 0.4rem;
		font-variant: all-small-caps;
	}
}

.form-field-horizontal {
	display:flex;
	gap: .5rem;
	margin-bottom: 0.5rem;
	label {
		width: 5rem;
		font-size: .9rem;
		font-variant: all-small-caps;
	}
	& > div {
		width: 100%;
	}
}

.form-field-compact {
	display: inline-flex;
	margin-bottom: .5rem;
	label {
		margin-right: .5rem;
		font-size: .9rem;
		font-variant: all-small-caps;
	}
	.cdx-text-input {
		min-width:4rem;
	}
	input {
		width: 5rem;
	}
}

</style>
