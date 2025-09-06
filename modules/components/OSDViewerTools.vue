<template>
<div class="osd-viewer-tools">

	<template v-if="showDrawingTool">
		<cdx-toggle-button
			ref="annotationToggle"
			class="toggle-annotate"
			v-model="doAnnotate"
			aria-label="Toggle annotation tool"
			@update:model-value="onAnnotationUpdate"
		>
			<cdx-icon :icon="cdxIconHighlight"></cdx-icon>
		</cdx-toggle-button>
	</template>

	<cdx-button
		class="osd-viewer-rotation"
		value="rotateRight"
		aria-label="Rotate clockwise"
		@click="onClickRotation">
		<cdx-icon :icon="cdxIconReload"></cdx-icon>
	</cdx-button>

	<cdx-toggle-button
		class="osd-viewer-other-toggle"
		value="show"
		aria-label="Show"
		v-model="toggleImageFilters"
		@update:model-value="onToggleImageFilters"
	>
		<cdx-icon :icon="cdxIconImage"></cdx-icon>
	</cdx-toggle-button>
	<div :class="dropdownClass">
		<div class="anno-field">
			<label>{{ $i18n( "iiif-annotator-osdviewertools-brightness" ).text() }}</label>
			<field-range-slider
				:key="brightnessSlider"
				:name="`brightness`"
				:min="`50`"
				:initialValue="filters.brightness"
				:suffix="`%`"
				@emit-slider-value="getBrightness"
			>
			</field-range-slider>
		</div>
		<div class="anno-field">
			<label>{{ $i18n( "iiif-annotator-osdviewertools-contrast" ).text() }}</label>
			<field-range-slider
				:key="contrastSlider"
				:name="`contrast`"
				:min="`50`"
				:initialValue="filters.contrast"
				:suffix="`%`"
				@emit-slider-value="getContrast"
			>
			</field-range-slider>
		</div>
		<div class="anno-field">
			<label>{{ $i18n( "iiif-annotator-osdviewertools-saturation" ).text() }}</label>
			<field-range-slider
				:key="saturationSlider"
				:name="`saturation`"
				:initialValue="filters.saturation"
				:suffix="`%`"
				@emit-slider-value="getSaturation"
			>
			</field-range-slider>
		</div>
		<hr />
		<cdx-button
			class="osd-viewer-filter-reset"
			value="reset"
			aria-label="Reset filters"
			@click="onClickResetFilters">
			{{ $i18n( "iiif-annotator-osdviewertools-resetfilters" ).text() }}
		</cdx-button>
	</div>

		<cdx-toggle-button
		ref="fullscreenToggle"
		class="toggle-fullscreen"
		v-model="toggleFullscreen"
		aria-label="Toggle fullscreen mode"
		@update:model-value="onFullScreenChange"
	>
		<template v-if="!toggleFullscreen">
			<cdx-icon :icon="cdxIconFullScreen"></cdx-icon>
		</template>
		<template v-if="toggleFullscreen">
			<cdx-icon :icon="cdxIconExitFullscreen"></cdx-icon>
		</template>
	</cdx-toggle-button>

</div>

</template>

<script>
const { defineComponent, ref, reactive, watch } = require( 'vue' );
const { CdxToggleButton, CdxButton, CdxButtonGroup, CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconReload, cdxIconZoomIn, cdxIconZoomOut, cdxIconImage, cdxIconFullScreen, cdxIconExitFullscreen,  cdxIconHighlight } = require( "./icons.json" );
const FieldRangeSlider = require( './FieldRangeSlider.vue' );

module.exports = defineComponent( {
	name: 'OSDViewerTools',
	components: {
		CdxToggleButton, CdxButtonGroup, CdxButton, CdxIcon, cdxIconImage,
		FieldRangeSlider
	},
	props: {
		rotation: {
			type: Number,
			default: 0
		},
		showDrawingTool: {
			type: Boolean,
			default: true
		}
	},
	computed: {
		dropdownClass() {
    		return this.toggleImageFilters ? "osd-dropdown-right active" : "osd-dropdown-right hidden";
    	}
	},
	watch: {
		rotation: function(n,o) {
			this.$emit('sendRotation', n);
		},
		doAnnotate: function(n,o) {
			//this.$emit('emitAnnotationToggle',n);
			// <input @input="$parent.$emit('custom-event', e.target.value) />
			this.$parent.$emit('emitAnnotationToggle',n)
		},
		toggleFullscreen: function(n,o) {
			//console.log( "toggleFullscreen",n);
			this.$emit( "sendFullScreen",n);
		}
	},
	emits: [ "sendFullScreen", "emitFilters" ],
	setup(props, { emit } ) {
		const doAnnotate = ref( false );
		const onAnnotationUpdate = function(value) {
			// console.log( 'update:modelValue event emitted with value: ' + value );
		}

		// Fullscreen mode
		const toggleFullscreen = ref( false );
		const onFullScreenChange = function(value) {
			// console.log( "onFullScreenChange", value );
			//emit('sendFullScreen', value );
		}
		document.addEventListener( "fullscreenchange", function() {
			if ( !document.fullscreenElement) {
				toggleFullscreen.value = false;
			}
		});
		
		// Rotation
		var newRotation = ref( props.rotation );
		function onClickRotation(value) {
			var newRotation = props.rotation = props.rotation + 90;
		}

		// Button group
		var buttons = [
			{ value: "rotateRight", label: null, icon: cdxIconReload, ariaLabel: "Rotate clockwise" }
			//, { value: "fullScreen", label: null, icon: cdxIconFullScreen, ariaLabel: "Open viewer in full screen mode" }
		];
		function onClickButtons( value ) {
			switch( value ) {
				case "rotateRight":
					var newRotation = props.rotation = props.rotation + 90;
					break;
				/*
				case "fullScreen":
					//console.log( "set fullScreen to true" );
					props.fullScreen = true;
					emit('sendFullScreen', true );
				break;
				*/
			}
		}

		// Image Filters
		// Component keys for dynamic rendering
		const brightnessSlider = ref( "brightness-" + Math.floor(Math.random() * 100000 ) );
		const contrastSlider = ref( "contrast-" + Math.floor(Math.random() * 100000 ) );
		const saturationSlider = ref( "saturation-" + Math.floor(Math.random() * 100000 ) );

		const toggleImageFilters = ref( false );
		const filters = reactive( {
			brightness: "100",
			contrast: "100",
			saturation: "100"
		} );

		function onToggleImageFilters( value ) {
			//console.log( "onToggleImageFilters", value );
		}
		function getBrightness(v) {
			filters.brightness = v;
		}
		function getContrast(v) {
			filters.contrast = v;
		}
		function getSaturation(v) {
			filters.saturation = v;
		}
		function onClickResetFilters(v) {
			filters.brightness = filters.contrast = filters.saturation = "100";
			// required to force rerender
			brightnessSlider.value = "brightness-" + Math.floor(Math.random() * 100000 );
			contrastSlider.value = "contrast-" + Math.floor(Math.random() * 100000 );
			saturationSlider.value = "saturation-" + Math.floor(Math.random() * 100000 );
		}
		watch( filters, function(n,o) {
			emit("emitFilters", n);
		});

		return {
			doAnnotate,
			onAnnotationUpdate,
			toggleFullscreen,
			onFullScreenChange,
			newRotation,
			buttons,
			onClickButtons,
			onClickRotation,
			// 4
			toggleImageFilters,
			onToggleImageFilters,
			filters,
			brightnessSlider,
			contrastSlider,
			saturationSlider,
			getBrightness,
			getContrast,
			getSaturation,
			onClickResetFilters,
			//
			cdxIconHighlight,
			cdxIconReload,
			cdxIconImage,
			cdxIconExitFullscreen,
			cdxIconFullScreen
		}
	}
} );
</script>

<style>
.osd-viewer-tools {
	display:flex;
	align-items: start;
	align-self: start;
	flex-wrap: wrap;
	position: relative;
	justify-content: end;
	min-width: 8em;
}

.osd-dropdown-right.hidden {
	display:none;
}
.osd-dropdown-right {
	display: block;
	position: absolute;
	float: right;
	top: 0;
	right: 0;
	transform: translate3d(0px, 2rem, 0px);
	min-width: 250px;
	overflow: auto;
	background-color: rgba(255,255,255,0.9);
	box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
	z-index: 1;
	padding:.9em;
}
</style>
