<template>
<div
	ref="textareawrapper"
	:class="wrapperClass"
>
	<textarea
		ref="textarea"
		:name="name"
		:placeholder="placeholder"
		v-model="content"
		@input="handleTextareaInput"
		@focus="autogrowTextarea"
		class="anno-textarea"
	></textarea>
</div>
</template>

<script>
const { defineComponent, ref, reactive, watch, onMounted, onBeforeUnmount } = require( "vue" );
const useVEField = require( "./composable.useVEField.js" );
const useVEFieldsStore = require( "../stores/veFields.js" );

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
	emits: [ "emit-update-value" ],
	setup( props, { emit, expose } ) {
		const content = ref( props.inputValue );
		const textarea = ref( null );
		const textareawrapper = ref( null );
		const targets = reactive( [] );
		const wrapperClass = ref( "anno-grow-wrap" );

		watch( content, (n, o) => {
			console.log( "content changed", n );
			emit( "emit-update-value", n );
		} );

		function handleTextareaInput( e ) {
			if ( props.editor == "ve" ) { return; }
			autogrowTextarea();
			textareawrapper.value.dataset.replicatedValue = e.target.value;
			content.value = e.target.value;
			emit( "emit-update-value", e.target.value );
		}

		// Plain textarea autowrap
		function autogrowTextarea() {
			if ( props.editor == "ve" ) { return; }
			const el = textarea.value;
			el.style.height = "auto";
			el.style.height = `${el.scrollHeight}px`;
		};

		// Textarea with VisualEditor
		let wikitext = ref( null );
		if ( props.editor == "ve" ) {
			// Set class required by VEForAll
			wrapperClass.value = "ve-area-wrapper";

			// Registry store
			const veFieldsStore = useVEFieldsStore();
			const { wikitext, getWikitext } = useVEField( textarea, content );

			watch( wikitext, ( value ) => handleVEInput( value ) );
			function handleVEInput( v ) {
				console.log( "new value for content", value );
				//content.value = v; // is no longer necessary
				// emit( "emit-update-value:content", v );
			}

			const toUnregister = veFieldsStore.register( getWikitext );
			onBeforeUnmount( toUnregister );
		}

		return {
			textarea,
			textareawrapper,
			handleTextareaInput,
			autogrowTextarea,
			content,
			wikitext,
			wrapperClass
		}
	}
});
</script>

<style lang="less">
.anno-grow-wrap {
	& > textarea {
		resize: none;
		overflow: hidden;
		padding: .5rem;
		background-color: #fff;
		color: #202122;
		border-color: #a2a9b1;
		box-shadow: inset 0 0 0 1px transparent;
		transition-property: background-color,color,border-color,box-shadow;
		transition-duration: .25s;
		font-size: .8rem;
		&:hover {
			border-color: #72777d;
		}
		&:focus {
			padding: .5rem;
			border-color:#36c;
			box-shadow: inset 0 0 0 1px #36c;
			outline:1px solid transparent;
		}
	}
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
