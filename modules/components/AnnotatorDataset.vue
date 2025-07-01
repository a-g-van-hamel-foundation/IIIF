<template>
	<div ref="anno-dataset" class="anno-sidebar-dataset">

		<!-- Using output from wiki template -->
		<template v-if="presentationMethod === 'template'">
			<div v-html="templateOutput"></div>
		</template>

		<!-- Using form profile -->
		<template
			v-if="presentationMethod === 'profile'"
			v-for="field in profileSchema.properties"
			:key="uniqueid + `-` + field.name"
		>
			<div 
				v-if="defaults[field.name] !== null && defaults[field.name] !== ''"
				class="anno-data-item"
			>
				<template v-if="( field.inputType === 'textarea' || field.inputType === 'texteditor' )">
					<label class="anno-label">{{ field.label }}</label>
					<div v-html="defaults[field.name]"></div>
				</template>
				<template v-if="field.inputType === 'text'">
					<label class="anno-label">{{ field.label }}</label>
					<div v-html="defaults[field.name]"></div>
				</template>
				<template v-if="field.inputType === 'xmleditor'">
					<label class="anno-label">{{ field.label }}</label>
					<pre lang="xml" class="anno-lang-xml">{{ defaults[field.name] }}</pre>
				</template>

				<template v-if="field.inputType === 'select' && !field.multiple ">
					<annotator-data-select
						:label="field.label"
						:input-value="defaults[field.name]"
						:multiple="field.multiple || false"
						:options="field.options"
					></annotator-data-select>
				</template>

				<template v-if="field.inputType === 'lookup' ">
					<annotator-data-lookup
						:multiple="field.multiple || false"
						:input-value="defaults[field.name]"
						:label="field.label"
						:api-type="field.apiType || null"
						:api-url="field.apiUrl || null"
					></annotator-data-lookup>
				</template>

			</div>
		</template>

	</div>
</template>

<script>
const { defineComponent, ref, watch, computed } = require("vue");
const store = require("ext.iiif.annotator.store");
const { CdxButton, CdxIcon } = require("@wikimedia/codex");
const AnnotatorDataSelect = require("./AnnotatorDataSelect.vue");
const AnnotatorDataLookup = require("./AnnotatorDataLookup.vue");

module.exports = defineComponent( {
	name: "AnnotatorDataset",
	components: {
		CdxButton, CdxIcon, AnnotatorDataSelect, AnnotatorDataLookup
	},
	props: {
		currentAnnotation: { type: Object, default: null },
		presentationMethod: { type: String, default: "profile" },
		profileSchema: {
			type: Object,
			default: {
				description: "",
				properties: []
			}
		}
	},
	methods: {},
	watch: {
		currentCanvas: function(n,o) {
			this.canvasId = n.canvasId;
		},
		currentAnnotation: function(n,o) {
			if ( n !== null && typeof n !== 'undefined' ) {
				this.debugLog( "currentAnnotation", n );
				this.currentId = n.id;
				this.uniqueid = Math.floor(Math.random() * 1000000 );
				if( this.presentationMethod === "profile" ) {
					this.defaults = this.fetchDataAsFormDefaults(n);
				} else if( this.presentationMethod === "template" ) {
					this.templateOutput = this.fetchTemplateOutputFromAnnotation(n);
				}
			}
		}
	},
	setup(props, { emit } ) {
		var uniqueid = ref( Math.floor(Math.random() * 1000000 ) );
		const currentCanvas = ref( computed( () => store.getters.getCurrentCanvas ) );
		const canvasId = ref( "" );
		// annotation id
		const currentId = ref( "" );
		const annotation = ref(
			computed( () => props.currentAnnotation )
		);
		var defaults = ref( fetchDataAsFormDefaults( annotation ) ?? [] );

		function fetchDataAsFormDefaults( anno ) {
			if ( !typeof anno === "object"
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
			var fields = props.profileSchema.properties;
			var defaults = {};
			for ( const field of fields ) {
				defaults[field.name] = customData[field.name] ?? null;
			}
			return defaults ?? {};
		}

		const templateOutput = ref( null );
		function fetchTemplateOutputFromAnnotation(anno) {
			return anno.body[0].templateoutput ?? "";
		}

		// Dev only
		function debugLog( msg = "(No message)", loggable = "" ) {
			// console.log( "AnnotatorDataset: " + msg, loggable );
		}

		return {
			uniqueid,
			currentCanvas,
			canvasId,
			currentId,
			annotation,
			defaults,
			fetchDataAsFormDefaults,
			templateOutput,
			fetchTemplateOutputFromAnnotation,
			debugLog
		}
	}
} );
</script>

<style>
.anno-data-item {
	margin-bottom: .5rem;
}
.anno-label {
	font-size: .9rem;
	margin-bottom: 0.4rem;
	font-variant: all-small-caps;
}
pre.anno-lang-xml {
	font-size: .8rem;
	padding: .4rem;
	background-color: #e6e6e6;
}
</style>
