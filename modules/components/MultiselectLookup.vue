<template>
	<cdx-multiselect-lookup
		id="cdx-demo-vegetables-basic"
		v-model:input-chips="chips"
		v-model:selected="selection"
		:menu-items="menuItems"
		:menu-config="menuConfig"
		aria-label="MultiselectLookup basic demo"
		@input="onInput"
		@update:selected="onUpdateSelected"
	>
		<template #no-results>
			No results found.
		</template>
	</cdx-multiselect-lookup>
</template>

<script>
const { defineComponent, ref } = require( 'vue' );
const { CdxMultiselectLookup } = require( '@wikimedia/codex' );
const vegetableItems = [
	{
		"label": "potato",
		"description": "root vegetable",
		"value": "Q10998"
	},
	{
		"label": "carrot",
		"description": "root vegetable, usually orange in color",
		"value": "Q81",
		"tag": "limited support"
	},
	{
		"label": "zucchini",
		"description": "Edible summer squash, typically green in colour",
		"value": "Q7533"
	},
	{
		"label": "eggplant",
		"description": "plant species Solanum melongena",
		"value": "Q7540"
	},
	{
		"label": "broccoli",
		"description": "edible green plant in the cabbage family",
		"value": "Q47722"
	},
	{
		"label": "cauliflower",
		"description": "vegetable, for the plant see Q7537 (Brassica oleracea var. botrytis)",
		"value": "Q23900272"
	},
	{
		"label": "brussels sprouts",
		"description": "vegetable",
		"value": "Q150463"
	},
	{
		"label": "cassava root",
		"description": "root vegetable",
		"value": "Q43304555"
	},
	{
		"label": "plantain",
		"description": "banana-like vegetable, less sweet",
		"value": "Q165449"
	},
	{
		"label": "cabbage",
		"description": "Vegetable, the generic term for several varieties.",
		"value": "Q14328596"
	},
	{
		"label": "napa cabbage",
		"description": "a type of Chinese cabbage",
		"value": "Q13360268"
	}
]

module.exports = defineComponent( {
	name: 'MultiselectLookupBasic',
	components: {
		CdxMultiselectLookup
	},
	props: {
		options: { type: Array, default: [] }
	},
	setup() {
		const chips = ref( [] );
		const selection = ref( [] );
		const menuItems = ref( [] );

		const menuOptions = ref( [] );
		menuOptions.value = vegetableItems;

		const menuConfig = {
			boldLabel: false,
			visibleItemLimit: 6
		};

		/**
		 * On input, filter the menu items.
		 *
		 * @param {string} value
		 */
		function onInput( value ) {
			if ( value ) {
				console.log( "vegetableItems", vegetableItems );
				console.log( "menuOptions.value", menuOptions.value );
				menuItems.value = menuOptions.value.filter( ( item ) => item.label.includes( value ) );
			} else {
				menuItems.value = [];
			}
		}

		function onUpdateSelected() {
			// eslint-disable-next-line no-console
			console.log( 'Current selection:', selection.value.join( ', ' ) );
		}

		return {
			chips,
			selection,
			menuItems,
			menuConfig,
			onInput,
			onUpdateSelected
		};
	}
} );
</script>