<template>
<div class="osd-nav-menu">
	<cdx-button-group
		class="osd-viewer-indicators"
		:buttons="buttons"
		@click="onClickButtons"
		:disabled="!isEnabled"
	>
	</cdx-button-group>
	<cdx-select
		name="selectCanvasItem"
		class="osd-viewer-list"
		v-model:selected="menuSelection"
		:menu-items="menuItems"
		default-label="Choose an option"
		:disabled="!isEnabled"
	></cdx-select>
</div>
</template>

<script>
const { defineComponent, ref } = require( 'vue' );
const { CdxButton, CdxButtonGroup, CdxSelect, CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconPrevious, cdxIconNext, cdxIconMoveFirst, cdxIconMoveLast } = require( "./icons.json" );

module.exports = defineComponent( {
	name: 'OSDViewerNavigation',
	components: {
		CdxButtonGroup, CdxButton, CdxSelect, CdxIcon
	},
	props: {
		initialIndex: {
			type: Number, default: 0
		},
		length: {
			type: Number, default: 0
		},
		menuItems: {
			type: Array, default: []
		},
		isEnabled: {
			type: Boolean, default: true
		}
	},
	data() {
		return {
			currentIndex: this.initialIndex || 0,			
			// menuSelection can change in two ways!
			menuSelection: this.initialIndex || 0,
			length: this.length,
			menuItems: this.menuItems,
			buttons: [
				{ value: 'first', label: null, ariaLabel: "First page", icon: cdxIconMoveFirst, disabled: this.shouldDisableFirst(this.currentIndex) },
				{ value: 'previous', label: null, ariaLabel: "Previous page", icon: cdxIconPrevious, disabled: this.shouldDisableFirst(this.currentIndex) },
				{ value: 'next', label: null, ariaLabel: "Next page",icon: cdxIconNext, disabled: this.shouldDisableLast(this.currentIndex) },
				{ value: 'last', label: null, ariaLabel: "Last page", icon: cdxIconMoveLast, disabled: this.shouldDisableLast(this.currentIndex) }
			]
		}
	},
	methods: {
		onClickButtons( value ) {
			var newIndex = this.getNewIndexFromDirection( this.currentIndex, value );
			this.menuSelection = newIndex;
			// menuSelection is being watched and will handle the rest
			// No further action needed
		},
		updateButtons( index ) {
			this.buttons = [
				{ value: 'first', label: null, ariaLabel: "First page", icon: cdxIconMoveFirst, disabled: this.shouldDisableFirst(index) },
				{ value: 'previous', label: null, ariaLabel: "Previous page", icon: cdxIconPrevious, disabled: this.shouldDisableFirst(index) },
				{ value: 'next', label: null, ariaLabel: "Next page", icon: cdxIconNext, disabled: this.shouldDisableLast(index) },
				{ value: 'last', label: null, ariaLabel: "Last page", icon: cdxIconMoveLast, disabled: this.shouldDisableLast(index) }
			];
		},
		shouldDisableFirst(index) {
			return ( index == 0 );
		},
		shouldDisableLast(index) {
			return ( index == this.length - 1 );
		},
		getNewIndexFromDirection(currentIndex, direction) {
			// default
			var newIndex = currentIndex;
			switch(direction) {
				case "first":
					var newIndex = 0;
					break;
				case "previous":
					var newIndex = ( currentIndex == 0 ) ? 0 : currentIndex - 1;
					break;
				case "next":
					var newIndex = ( currentIndex == this.length - 1 ) ? currentIndex : currentIndex + 1;
					break;
				case "last":
					var newIndex = this.length - 1;
					break;
			}
			return newIndex;
		}
	},
	watch: {
		// @todo Cascading watch effect would be better handled
		// through state management
		initialIndex: function(n) {
			this.menuSelection = n;
		},
		menuSelection: function(n) {
			//this.updateButtons(newIndex);
			//this.$emit('sendNavDirection', newIndex);
			this.currentIndex = n;
		},
		currentIndex: function(n,o) {
			// Can we do this instead?
			this.$emit('sendNavDirection', n);
			this.updateButtons(n);
		}
	},
	setup(props) {
		// @todo move data to setup
		// const currentIndex = ref( props.initialIndex ?? 0 );
		// const menuSelection = ref( 0 );
		// menuSelection.value = props.currentIndex;
		// console.log( "props.initialIndex", props.initialIndex );

		return {
			//menuSelection
		};
	}
} );
</script>

<style>
.cdx-menu {
	max-height: 250px;
	overflow-y: scroll;
}
.osd-nav-menu {
	display:flex;
}
.osd-viewer-list {
	max-width:20em;
}
.osd-viewer-indicators {
	min-width: 10em;
}
</style>
