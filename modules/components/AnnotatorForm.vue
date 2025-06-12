<template>
	<template v-if="!isEnabled">
		<div class="annot-placeholder-text"><i>{{ $i18n( "iiif-annotator-create-or-select-annotation" ).text() }}</i></div>
	</template>
	<form
		@submit.prevent="submitForm"
		ref="annoform"
		:disabled="isFormDisabled"
		class="anno-sidebar-form"
	>

		<fieldset ref="annofieldset" v-if="isEnabled" class="anno-sidebar-fieldset">

			<div class="submit-form-button-container">
				<cdx-icon :icon="cdxIconCheck" class="annot-status annot-success" v-if="showIconCheck"></cdx-icon>
				<cdx-icon :icon="cdxIconClose" class="annot-status annot-fail" v-if="showIconClose"></cdx-icon>
				<cdx-icon :icon="cdxIconEllipsis" class="annot-status annot-nochange" v-if="showIconEllipsis"></cdx-icon>
				<cdx-button
					:disabled="false"
					action="progressive"
					weight="primary"
					type="submit"
					aria-label="Submit form"
				>{{ $i18n( "iiif-annotator-save" ).text() }}</cdx-button>			
			</div>

			<template
				v-for="field in profileSchema.properties"
				:key="uniqueid + `-` + field.name"
			>
				<dynamic-form-field
					:ref="field.name"
					:input-type="field.inputType"
					:name="field.name"
					:label="field.label"
					:options="field.options"
					:input-value="defaults[field.name]"
					:default-value="field.defaultValue"
					@emit-update-value="updateValue"
					:placeholder="field.placeholder"
					:multiple="field.multiple || false"
					:api-type="field.apiType || null"
					:api-url="field.apiUrl || null"
				></dynamic-form-field>
			</template>

		</fieldset>
	</form>
</template>

<script>
const { defineComponent, ref, watch, computed } = require( "vue" );
const store = require("ext.iiif.annotator.store");
const { CdxTextArea, CdxTextInput, CdxButton, CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconCheck, cdxIconClose, cdxIconEllipsis } = require( "./icons.json" );
const DynamicFormField = require( "./DynamicFormField.vue" );

module.exports = defineComponent( {
	name: "AnnotatorForm",
	components: {
		CdxTextArea, CdxTextInput, CdxButton, CdxIcon,
		DynamicFormField
	},
	props: {
		timestamp: { type: Number, default: 0 },
		isEnabled: { type: Boolean, default: false },
		currentAnnotation: { type: Object, default: null },
		profileSchema: {
			type: Object,
			default: {
				description: "",
				properties: []
			}
		},
		showIcon: { type: "String", default: "" }
	},
	methods: {
		// @todo maybe remove
		updateValue( value ) {
			this.debugLog("updateValue", value );
		}
	},
	watch: {
		currentCanvas: function(n,o) {
			this.debugLog("currentCanvas changed on watch",n);
			this.canvasId = n.canvasId;
		},
		currentAnnotation: function(n,o) {
			this.debugLog( "currentAnnotation changed on watch", n );
			if ( n !== null && typeof n !== 'undefined' ) {
				this.currentId = n.id;
				this.uniqueid = Math.floor(Math.random() * 1000000 );				
				this.defaults = this.fetchDataAsFormDefaults(n);
				this.debugLog( "currentAnnotation id", n.id );
			} else {
				this.debugLog( "currentAnnotation is null or undefined" );
			}
		},
		showIcon: function(n,o) {
			this.showIconCheck = this.showIconClose = this.showIconEllipsis = false;	
			if( n === "success" ) {
				this.showIconCheck = true;
			} else if( n === "fail" ) {
				this.showIconClose = true;
			} else if( n === "nochange" ) {
				this.showIconEllipsis = true;
			}
		}
	},
	emits: [ "emitUpdatedAnnotation" ],
	setup(props, { emit } ) {
		var uniqueid = ref( Math.floor(Math.random() * 1000000 ) );
		const annoform = ref( null );
		const annofieldset = ref( null );
		const formData = ref( Object );

		const currentCanvas = ref( computed( () => store.getters.getCurrentCanvas ) );
		const canvasId = ref( "" );

		// annotation id
		const currentId = ref( "" );
		const annotation = ref(
			computed( () => props.currentAnnotation )
		);
		var defaults = ref( fetchDataAsFormDefaults( annotation ) ?? [] );
		//const newAnnotation = ref( Object );

		function fetchDataAsFormDefaults( anno ) {
			if ( !typeof anno === 'object'
				|| !Object.hasOwn(anno, "body")
				|| anno.body.length == 0
			) {
				var fields = props.profileSchema.properties;
				var foo = {};
				for ( const field of fields ) {
					foo[field.name] = null;
				}
				return foo;
			} else {
				function getDataset(body) {
					if ( typeof body === "object" && body.Dataset !== undefined ) {
						// @todo Legacy - will be removed		
						return body.Dataset;
					} else if( typeof body === "object" && body.type === "Dataset" ) {
						return body;
					}
				}
				var customDataBodies = anno.body.map(getDataset);
				var customData = customDataBodies.length ? customDataBodies[0] : null;
			}
			// For each field name, get the corresponding values from anno
			var fields = props.profileSchema.properties;
			var defaults = {};
			for ( const field of fields ) {
				defaults[field.name] = customData[field.name] ?? null;
			}
			return defaults ?? {};
		}

		function submitForm() {
			var formData = new FormData( annoform.value );
			// Get latest annotation and update
			var newAnnotation = annotation.value;
			// dev note: no textualBody
			newAnnotation.body = [{
				type: "Dataset",
				id: currentId.value
				// leave 'motivation' to form schema
			}];
			// No need for formData.entries():
			// debugLog( "object entries ", formData.entries() );
			const multiple = [];
			props.profileSchema.properties.forEach( function(prop) {
				if ( typeof prop.multiple !== 'undefined' && prop.multiple === true ) {
					multiple[prop.name] = true;
				} else {
					multiple[prop.name] = false;
				}
			});
			debugLog( "submitForm: multiple?", multiple );

			for ( const [key, value] of formData ) {
				if ( multiple[key] == false ) {
					newAnnotation.body[0][key] = value;
				} else {
					newAnnotation.body[0][key] = formData.getAll(key);
				}
			}

			emit( "emitUpdatedAnnotation", newAnnotation );
		}

		const isFormDisabled = ref( computed( () => {
			return props.isEnabled ? "" : "disabled";
		}) );
		annofieldset.disabled = true;
		var showIconCheck = ref( false );
		var showIconClose = ref( false );
		var showIconEllipsis = ref( false );

		// Dev only
		function debugLog( msg = "(No message)", loggable = "" ) {
			//console.log( "AnnotatorForm: " + msg, loggable );
		}

		return {
			uniqueid,
			annoform,
			annofieldset,
			isFormDisabled,
			formData,
			defaults,
			currentId,
			canvasId,
			annotation,
			//newAnnotation,

			submitForm,
			fetchDataAsFormDefaults,
			// Icons
			cdxIconCheck, cdxIconClose, cdxIconEllipsis,
			showIconCheck, showIconClose, showIconEllipsis,

			debugLog
		};
	}
} );
</script>

<style>
.annotator-sidebar {
	max-height: 100%;
	overflow-y: auto;
}
.annotator-sidebar .submit-form-button-container {
	display:flex;
	flex-direction: row;
	margin-bottom:1rem;
	justify-content: flex-end;
	gap: .5rem;
}
.annot-placeholder-text {
	font-size:.8rem;
}
.cdx-icon.annot-status {
	display:inline;
	-webkit-animation: fadeInOut 2.5s linear forwards;
    animation: fadeInOut 2.5s linear forwards;
}
.cdx-icon.annot-success {
	color:#69B33F;	
}
.cdx-icon.annot-fail {
	color:#dd3243;
}
.cdx-icon.annot-nochange {
	color:grey;
}
@keyframes fadeInOut {
	0%,100% { opacity: 0; }
	20%,80% { opacity: 1; }
}

.anno-sidebar-fieldset,
.anno-field {
	animation: comp-fade-in .7s;
	-moz-animation: comp-fade-in .7s;
}
@keyframes comp-fade-in {
	0%   { opacity: 0; }
	100% { opacity: 1; }
}
</style>
