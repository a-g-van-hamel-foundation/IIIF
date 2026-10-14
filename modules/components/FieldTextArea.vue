<template>
<div
	ref="textareawrapper"
	:class="wrapperClass"
>
	<textarea
		ref="textarea"
		:name="name"
		:placeholder="placeholder"
		class="anno-textarea"
		v-model="content"
		@input="handleInput"
	></textarea>
</div>
</template>

<script>
const { defineComponent, ref, onMounted } = require( "vue" );

module.exports = defineComponent( {
	name: "FieldTextArea",
	props: {
		name: { type: String, default: "" },
		label: { type: String, default: "" },
		ariaLabel: { type: String, default: "" },
		//value: { type: String, default: "" },
		inputValue: { type: String, default: "" },
		placeholder: { type: String, default: "" },
		autogrow: { type: Boolean, default: false },
		class: { type: String, default: "" },
		// Default is to have no text editor
		editor: { type: String, default: "" }
	},
	methods: {
		handleInput(e) {
			this.$refs.textareawrapper.dataset.replicatedValue = e.target.value;
			// this.$refs.textarea.value = same as = e.target.value 
			var content = e.target.value;
			this.$emit('emit-update-value', e.target.value );
		}
	},
	setup( props ) {
		const content = ref( props.inputValue );
		const textarea = ref( null );
		const wrapperClass = ref( "anno-grow-wrap" );

		onMounted( () => {
			if ( props.editor === "ve" ) {
				applyVisualEditorToTextarea( textarea.value );
			}
		} );

		function applyVisualEditorToTextarea( textarea ) {
			if ( mw.loader.getState( 'ext.veforall.main' ) === null ) {
				mw.log.warn( 'VEForAll is not installed' );
				return;
			}
			mw.loader.using( 'ext.veforall.main' ).then( function () {
				// Class required by VEForAll
				wrapperClass.value = "ve-area-wrapper";
				$( textarea ).applyVisualEditor();
			}, function () {
				// Module is registered but failed to load
				// e.g. because VisualEditor is missing
				mw.log.error( 'VEForAll could not be loaded' );
			} );
		}

		return {
			textarea,
			content,
			wrapperClass,
			applyVisualEditorToTextarea
		}
	}
});
</script>

<style lang="less">
.anno-textarea {
	&:hover {
		border-color: #72777d;
	}
	&:focus {
		border-color:#36c;
		box-shadow: inset 0 0 0 1px #36c;
		outline:1px solid transparent;
	}
}

.anno-grow-wrap {
	display: grid;
	&::after {
		white-space: pre-wrap;
		content: attr(data-replicated-value) " ";
		visibility: hidden;
	}
	& > textarea {
		background-color: #fff;
		color: #202122;
		border-color: #a2a9b1;
		box-shadow: inset 0 0 0 1px transparent;
		transition-property: background-color,color,border-color,box-shadow;
		transition-duration: .25s;
	}
}

.anno-grow-wrap > textarea {
	resize: none;
	overflow: hidden;
}
.anno-grow-wrap > textarea,
.anno-grow-wrap::after {
	display: grid;
	padding: 0.5rem;
	grid-area: 1 / 1 / 2 / 2;
}

.ve-area-wrapper {
	display: flex !important;
	flex-direction: column;
	.ve-ui-toolbar {
		font-size: .8rem;
		order: 1;
		& > .oo-ui-toolbar-bar {
			// Shouldn't hover over dropdown menu (.oo-ui-popupToolGroup-tools)
			z-index: inherit;
		}
	}
	.ve-init-target {
		order: 2;
		.ve-ce-branchNode p {
			margin-bottom: .4em;
		}
	}
	textarea {
		order: 3;
	}

	.ve-ce-documentNode.ve-ce-attachedRootNode {
		min-height: 1em;
	}
	.ve-ce-documentNode.ve-ce-attachedRootNode {
		background: #cbe0e0;
		padding: .5rem;
		font-size: .8rem;
	}
	.ve-ce-branchNode {
		outline: 1px solid transparent;
	}
	.ve-ce-branchNode:focus {
		border-color: #36c;
		box-shadow: inset 0 0 0 2px #36c;
		outline: 1px solid transparent;
	}
	
}

</style>
