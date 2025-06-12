<template>
	<div 
		ref="acewrapper"
		class="ace-wrapper"
	>
		<textarea
			ref="textarea"
			class="ace-editor"
			v-model="content"
			:placeholder="placeholder"
		></textarea>
		<textarea hidden :name="name" :value="content"></textarea>
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require( "vue" );
const Ace = require( "ext.iiif.lib.ace" );
const AceUtils = require( "ext.iiif.lib.ace.utils" );

var basePath = mw.config.get( 'wgExtensionAssetsPath', '' );
if ( basePath.slice( 0, 2 ) === '//' ) {
	// Qu: ACE uses web workers, which have importScripts, which don't like relative links.
	// This is a problem only when the assets are on another server, so this rewrite should suffice
	// Protocol relative
	basePath = window.location.protocol + basePath;
}

module.exports = defineComponent( {
	name: "FieldXmlEditor",
	props: {
		name: { type: String, default: "" },
		label: { type: String, default: "" },
		ariaLabel: { type: String, default: "" },
		//value: { type: String, default: "" },
		inputValue: { type: String, default: "" },
		placeholder: { type: String, default: "" },
		class: { type: String, default: "" }
	},
	methods: {
		initAceEditor() {
			Ace.config.set( 'basePath', basePath + '/IIIF/modules/lib-ace' );
			const aceEditor = Ace.edit( this.$refs.textarea );
			var XmlMode = Ace.require("ace/mode/xml").Mode;
			aceEditor.setOptions({
				wrap: true,
				highlightActiveLine: true,
				autoScrollEditorIntoView: true,
				//copyWithEmptySelection: false,
				showPrintMargin: false,
				showLineNumbers: false,
				showGutter: false
				//theme: 'ace/theme/...'
			});
			aceEditor.session.setMode(new XmlMode() );
			aceEditor.session.setUseWrapMode( true );
			const self = this;
			aceEditor.session.on('change', function(delta) {
				self.content = aceEditor.getSession().getValue();
				//console.log( "content changed", self.content );
				//this.$emit('emit-update-value', this.content );
			});
			this.addKeyboardShortcuts( aceEditor );
			this.aceEditor = aceEditor;
		},
		addKeyboardShortcuts( aceEditor ) {
			aceEditor.container.addEventListener('keydown', function(e) {
				if (e.metaKey && e.key === 'i') {
					e.preventDefault();
					const selected = aceEditor.getSelectedText();
					if ( selected !== "" ) {
						var newVal = "<expan>" + selected + "</expan>";
						aceEditor.session.replace( aceEditor.selection.getRange(), newVal );
						var newVal = "";
					}
				}
			});
		},
		resizeEditor( aceEditor ) {
			// console.log( 'Resizing...' );
			const resizableEl = aceEditor.textInput.getElement().parentElement;
			const onresize = ( resizableEl, callback) => {
				const resizeObserver = new ResizeObserver( () => callback() );
				resizeObserver.observe( resizableEl );
			};
			onresize( resizableEl, function () {
				aceEditor.resize();
			});
		}
	},
	mounted() {
		this.initAceEditor();
	},
	watch: {
		content: function(n,o) {
			//console.log( "content being watched changed", n );
		}
	},
	setup(props) {
		const content = ref( props.inputValue );
		const val = ref( content);
		const aceEditor = ref( null );
		/*
		const textareaClasses = computed( () => ( {
			'is-autogrow': props.autogrow
		} ) );
		  */
		return {
			aceEditor,
			content,
			val
		}
	}
});
</script>

<style>
.ace_editor {
	position: relative;
	min-height: 100px;
}

</style>