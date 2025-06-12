<template>
<div
	:id="containerId"
	class="osdragon-widget"
>
	<div class="osd-viewer-container">
		<template v-if="showOSDViewer">
			<osd-viewer
				ref="osdviewer"
				@emit-osd-viewer="getOSDViewerFromChild"
				@vue:mounted="initOSDAnnotator"
				:id="id"
				:container-id="containerId"
				:prefix-url="prefixUrl"
				:sequence-mode=sequenceMode
				:tile-sources="tileSources"
				:toc-menu-items="tocMenuItems"
				:canvas-items="canvasItems"
				:is-navigation-enabled="isNavigationEnabled"
				@emit-annotation-toggle="toggleOSDDrawingTool"
			></osd-viewer>
		</template>
		
	</div>

	<div class="annotator-sidebar">
		<div class="anno-sidebar-form-container">
			<!--
			{ id: 'more', label: 'Dev' }
			-->
			<nav-tabs 
				:items="[
					{ id: 'form', label: $i18n( 'iiif-annotator-tab-form' ).text() },
					{ id: 'annotations', label: $i18n( 'iiif-annotator-tab-annotations' ).text() }
				]"
				:initial-active-id="activeTabId"
				:time="tabTime"
			>
				<template #form >					
					<annotator-form
						:timestamp="formKey"
						ref="annotatorform"
						class="annotation-form-container"
						:is-enabled="isFormEnabled"
						:current-annotation="currentAnnotation"
						:current-canvas="currentCanvas"
						@emit-updated-annotation="updateAnnotation"
						:profile-schema="formProfileSchema"
						:show-icon="iconStatus"
					></annotator-form>
				</template>

				<template #annotations >
					<div class="annotation-list">
						<template v-for="(anno, index) in annotationList">
							<a v-if="anno !== undefined"
								:key="`annot-list-` + index"
								class="annot-list-btn"
								@click="setCanvasFromPageIndex(anno.canvasIndex)"
							>{{ anno.label }}</a>
						</template>
					</div>
				</template>

				<!-- dev:
				<template #more >
					<div class="anno-field">
						<label>Current canvas</label>
						<div v-if="currentCanvas !== null">{{ currentCanvas.canvasId }}</div>
					</div>
					<div class="anno-field">
						<label>JSON</label>
						<div><code>{{ printArea }}</code></div>
					</div>
				</template>
				-->

			</nav-tabs>
		</div>

	</div>
</div>
</template>

<script>
const { defineComponent, defineExpose, computed, ref } = require("vue");
const store = require("ext.iiif.annotator.store");
const OSDViewerModule = require( "./OSDViewer.vue" );
const AnnotoriousOSD = require( "ext.iiif.lib.annotorious.osd" );
const { W3CImageFormat } = require( "ext.iiif.lib.annotorious.osd" );
const SequenceModePlugin = require( "ext.iiif.lib.annotorious.plugin.sequencemode" ).default;
const AnnotatorForm = require( "./AnnotatorForm.vue" );
const NavTabs = require( "./NavTabs.vue" );
const { CdxButton, CdxIcon } = require( '@wikimedia/codex' );

module.exports = defineComponent( {
	name: "AnnotatorInterface",
	components: {
		"cdx-button": CdxButton,
		"cdx-icon": CdxIcon,
		"osd-viewer": OSDViewerModule,
		"annotator-form": AnnotatorForm,
		"nav-tabs": NavTabs
	},
	props: {
		configProps: {
			type: Array,
			default: []
		},
		manifestObj: {
			type: Array,
			default: []
		},
		canvasItems: {
			type: Array,
			default: []
		},
		tileSourcesFromManifest: {
			type: Array,
			default: []
		},
		formProfile: {
			type: Object,
			default: null
		},
		initialAnnotationPages: {
			type: Array,
			default: []
		}
	},
	methods: {
		getOSDViewerFromChild( OSDViewer ) {
			this.OSDViewer = OSDViewer;
		},
		initOSDAnnotator() {
			// W3CImageFormat adapter
			if ( typeof(this.currentCanvas.url) !== "undefined" ) {
				this.debugLog( "Attempting to use W3CImageFormat with", this.currentCanvas.url );
				this.OSDAnnotatorOptions.adapter = AnnotoriousOSD.W3CImageFormat( this.currentCanvas.url );
			} else {
				console.log( "Something went wrong. The current canvas URL (this.currentCanvas.url) is undefined");
			}
			this.OSDAnnotator = AnnotoriousOSD.createOSDAnnotator(
				this.OSDViewer,
				this.OSDAnnotatorOptions
			);
			AnnotoriousOSD.SequenceModePlugin = SequenceModePlugin(
				this.OSDAnnotator,
				this.OSDViewer,
				this.initialAnnotationPages ?? [],
				this.canvasItems
			);
			this.setOSDAnnotatorBehaviours( this.OSDAnnotator, this.OSDViewer );
			// Set current MW user, not Guest
			this.OSDAnnotator.setUser( mw.user.id() );
			// @todo - Should we do this here?
			this.updateAnnotationList( this.OSDAnnotator.exportAllAnnotations() );
		},
		// Emitter handlers
		toggleOSDDrawingTool( doAnnotate = false ) {
			this.OSDAnnotatorOptions.drawingEnabled = doAnnotate;
			this.OSDAnnotator.setDrawingEnabled(doAnnotate);
			this.OSDViewer.zoomPerClick = doAnnotate ? 1 : 1.5;
			if ( !doAnnotate ) {
				this.OSDAnnotator.cancelDrawing();
			}
		},
		// @todo unused so probably remove (may not even work)
		getCurrentSourceId() {
			return this.OSDViewer.tileSources[this.OSDViewer.currentPage];
		},
		/**
		 * Update annotation with new data from AnnotatorForm
		 * except for image coordinates in 'target',
		 * and 'creator' and 'created'
		 */
		updateAnnotation( updatedAnnotation ) {
			// Because AnnotatorForm could not check for intermediate
			// changes (if any) to image coordinates, let's get them now.
			// (1) get current annotation without the new data
			const latestAnno = this.OSDAnnotator.getAnnotationById(updatedAnnotation.id);

			// (2) Update with new data
			const newAnno = latestAnno;
			newAnno.body = updatedAnnotation.body ?? updatedAnnotation.bodies ?? [];
			/*
			var newObj = {
				id: updatedAnnotation.id,
				body: updatedAnnotation.body ?? updatedAnnotation.bodies,
				creator: latestAnno.creator ?? "",
				created: latestAnno.created ?? "",
				target: latestAnno.target
			};
			*/
			this.OSDAnnotator.updateAnnotation(newAnno);
			this.debugLog( "updateAnnotation: new annotation before update", JSON.stringify(newAnno) );
			// Don't use this.writeToWiki() here because of api edit lag
		},
		updateAnnotationList(annotations) {
			const list = [];
			for ( anno of annotations ) {
				let idx = anno.references.index;
				list[idx] = {
					canvasIndex: idx,
					canvasId: anno.references.canvasId,
					label: this.canvasItems[idx]["label"]
				}
			}
			this.annotationList = list;
		},
		writeToWiki() {
			var annotations = this.OSDAnnotator.exportAllAnnotations();
			var prevAnnotationList = this.annotationList;
			this.updateAnnotationList( annotations );

			if ( this.annotationList === prevAnnotationList ) {
				self.showStatusIcon("nochange");
				this.debugLog( "writeToWiki(): no changes in annotation list" );
				return;
			} else {
				this.debugLog( "writeToWiki(): attempting to save ", JSON.stringify(annotations) );
			}

			// Prepare the API action
			const wikiPage = this.configProps.target ?? null;
			const targetSlot = this.configProps.targetSlot ?? null;
			if ( wikiPage === null ) {
				console.log( "Annotations could not be written to a wiki page. Reason: no wiki page specified." );
				return;
			}
			if ( targetSlot !== null && targetSlot !== "" ) {
				this.debugLog( "Using editslot for slot", targetSlot );
				// WSSlots' editslot.
				// Content model cannot be set/changed through action
				var editParams = {
					action: "editslot",
					format: "json",
					formatversion: "2",
					title: wikiPage,
					slot: targetSlot,
					text: JSON.stringify(annotations),
					summary: `Saved annotations in slot (${targetSlot}) with IIIF Annotator`
				};
			} else {
				var editParams = {
					action: "edit",
					format: "json",
					formatversion: "2",
					title: wikiPage,
					text: JSON.stringify(annotations),
					summary: "Saved annotations with IIIF Annotator",
					contentformat: "application/json",
					contentmodel: this.configProps['targetContentModel'] ?? "json"
				};
			}

			// Perform the API action
			const mwApi = new mw.Api();
			const self = this;
			mwApi.postWithToken('csrf', editParams )
			.done( function(data) {
				self.showStatusIcon("success");
				self.debugLog( "API response", data );
				self.debugLog( "Successfully saved annotationPages", JSON.stringify(annotations) );
			})
			.fail( function(e) {
				console.log( "Failed to save data with the IIIF Annotator", e );
				self.showStatusIcon("fail");
			});
		}
	},
	watch: {
		currentCanvas: function(n,o) {
			this.showForm(false);
		},
		currentAnnotation: function(n,o) {
			this.debugLog( "currentAnnotation being watched:", n );
		}
	},
	mounted() {
	},
	setup(props, context) {
		// context.emit, context.expose
		var prefixUrl = ref( "/extensions/IIIF/modules/lib-openseadragon/images/" );
		var manifestUrl = ref( props.configProps['manifest'] ?? null );
		var tileSources = ref( props.tileSourcesFromManifest );

		// OSD Viewer
		var OSDViewer = ref( null );
		var showOSDViewer = ref( true );
		var containerId = ref( "osdragon-widget-" + props.configProps['id'] ?? "osdragon-widget" );
		var id = ref( "osd-" + props.configProps['id'] ?? "osd-viewer" );
		const osdViewerEl = computed( () => {
			return document.getElementById( "osd-" + props.configProps['id'] ?? "osd-viewer" );
		});
		// OSD Viewer navigation / canvases
		function getPageIndexFromUrl() {
			const urlParams = new URLSearchParams(window.location.search);
			var defaultIndex = props.configProps.canvasIndex ?? "0";
			var index = parseInt( urlParams.get("cdx") ?? defaultIndex );
			return index;
		}
		var initialPageIndex = getPageIndexFromUrl();
		store.commit("updateCurrentCanvas", props.canvasItems[initialPageIndex] );
		var currentCanvas = ref( null );
		var currentCanvas = computed( () => {
			return store.getters.getCurrentCanvas ?? null;
		});
		function setCanvasFromPageIndex(idx) {
			store.commit("updateCurrentCanvas", props.canvasItems[idx]);
		}

		// OSD Viewer navigation/TOC
		var isNavigationEnabled = ref( true );
		function createTOCFromCanvasItems( canvasItems ) {
			toc = [];
			for (let i = 0; i < canvasItems.length; i++) {
 				toc[i] = {
					value: canvasItems[i]["index"],
					label: canvasItems[i]["label"]
				}
			}
			return toc;
		}
		var tocMenuItems = ref( [] );
		var tocMenuItems = createTOCFromCanvasItems( props.canvasItems );

		// OSD Annotorious
		var OSDAnnotator = ref(null);
		const OSDAnnotatorOptions = ref({
			drawingEnabled: false,
			style: {
				fill: '#ff0000',
				fillOpacity: 0.15
			},
			multiSelect: true
		});
		var sequenceMode = ref(true);
		var currentAnnotation = ref(null);
		const annotationList = ref( [] );
		// Print area for dev info - to be disabled in production:
		// var printArea = ref( '' );

		function setOSDAnnotatorBehaviours( OSDAnnotator, OSDViewer ) {
			onDeleteRemoveAnnotation( OSDAnnotator, this );
			onCreateAnnotation( OSDAnnotator, this );
			onUpdateAnnotation( OSDAnnotator, this );
			onDeleteAnnotation( OSDAnnotator, this );
			onClickAnnotation( OSDAnnotator, OSDViewer, this );
			onSelectionChanged( OSDAnnotator, OSDViewer, this );
		}
		function onDeleteRemoveAnnotation( osdAnnotator, self ) {
			osdViewerEl.value.addEventListener("keydown", function(evt) {
				if (evt.key === "Delete" || evt.key === "Backspace") {
					const selected = osdAnnotator.getSelected();
					if (typeof selected !== "undefined") {
						selected.forEach( function(anno) {
							self.debugLog( "onDeleteRemoveAnnotation", anno );
							self.debugLog( "all current annotations", self.OSDAnnotator.exportAllAnnotations());
							osdAnnotator.removeAnnotation(anno);
						});
						// don't use self.writeToWiki() here
						self.showForm(false);
					}
				}
			});
		}
		function onCreateAnnotation( osdAnnotator, self ) {
			osdAnnotator.on("createAnnotation", function(annotation) {
				// self.printArea = JSON.stringify( annotation );
				self.currentAnnotation = annotation ?? null;
				self.debugLog('self.currentCanvas',self.currentCanvas);
				let newAnnotation = JSON.parse(JSON.stringify(annotation));

		  		self.debugLog("Created annotation (createAnnotation fired)", newAnnotation);
				self.setTab("form");
				self.showForm(true);
				//self.isFormEnabled = true;

				self.writeToWiki();
			});
		}
		function onUpdateAnnotation( osdAnnotator, self ) {
			osdAnnotator.on('updateAnnotation', function(updated, previous ) {
				// console.log('Annotation was updated and is now', updated);
				self.writeToWiki();
			});
		}
		function onSelectionChanged( osdAnnotator, osdViewer, self ) {
			osdAnnotator.on("selectionChanged", function(annotations) {
				//console.log('Selection changed (selectionChanged)', annotations );
				debugLog('Selection changed (selectionChanged)', annotations );
				if( !osdAnnotator.isDrawingEnabled() ) {
					// Set/reset to default zoom factor in case
					// onClickAnnotation previously set it to 1 
					osdViewer.zoomPerClick = 1.5;
				}

				// May be used for debugging purposes:
				if( typeof(annotations) === "array" && annotations.length === 0 ) {
					debugLog( "Annotations (array) selected", "none" );
					// Presumably just deleted
				} else if( annotations[0] !== undefined && annotations[0]["id"] !== undefined ) {
					debugLog( "Annotations (array) selected", annotations );
					if( annotations.length > 1 ) {
						// Do not associate a form with multiple shapes
						//self.isFormEnabled = false;
						self.showForm(false);
					}
				} else if( annotations["target"] !== undefined ) {
					debugLog( "Annotation (obj) with target selected", annotations );
				} else {
					debugLog( "Annotation (obj) empty.");
				}
			});
		}

		function onDeleteAnnotation( osdAnnotator, self ) {
			osdAnnotator.on("deleteAnnotation", function(annotation) {
				if( typeof annotation === "undefined" ) {
					self.debugLog("onDeleteAnnotation: annotation is undefined");
				} else {
					//self.debugLog("onDeleteAnnotation", annotation);
				}
				// Form - see now onDeleteRemoteAnnotation
				self.writeToWiki();
				//self.formKey = Date.now();
				//self.isFormEnabled = false;
			});
		}

		// cf. onSelectionChanged, which checks if multiple shapes are selected
		function onClickAnnotation( osdAnnotator, osdViewer, self ) {
			osdAnnotator.on("clickAnnotation", function(annotation, originalEvent) {
				self.debugLog('Annotation clicked:', annotation );
				//console.log('Annotation clicked:', annotation );

				// Disable form just in case ????
				// self.isFormEnabled = false;
				// self.formKey = Date.now();

				// Disable zoom on click
				osdViewer.zoomPerClick = 1;
				self.currentAnnotation = annotation ?? null;
				/*
				if ( self.currentAnnotation !== undefined
					&& annotation.id === self.currentAnnotation.id ) {
					// No need to start anew. Keep current data but
					// there are some issues with this approach - @todo
					self.currentAnnotation = annotation;
				} else {
					self.currentAnnotation = annotation;
				}
				*/
				self.setTab("form");
				self.showForm(true);
				// dev: self.printArea = JSON.stringify( annotation );
			});
		}

		// Navigation tabs
		const activeTabId = ref( "" );
		activeTabId.value = "form";
		const tabTime = ref(0);
		function setTab(name) {
			activeTabId.value = name;
			tabTime.value = Date.now();
		}

		// Form
		const formProfileDefault = {
			properties: [
				{
					name: "description",
					label: "Description",
					inputType: "textarea",
					required: false,
					placeholder: "Give a description",
					motivation: "describing"
				}
			]
		};
		var formProfileSchema = ref(
			props.formProfile ?? formProfileDefault
		);
		var formKey = ref(0);
		const isFormEnabled = ref( false );
		function showForm(isEnabled) {
			isFormEnabled.value = isEnabled;
			var formKey = Date.now();
		}
		// Edit status
		const iconStatus = ref( "" );
		function showStatusIcon(status) {
			iconStatus.value = status;
			var timeOut = setTimeout( function() {
				iconStatus.value = "";
			}, 2500 );
		}

		/**
		 * Dev only
		 * @param msg
		 * @param loggable 
		 */
		function debugLog( msg = "(No message)", loggable = "" ) {
			// console.log( "AnnotatorInterface: " + msg, loggable );
		}
		defineExpose({
			//
		});

		return {
			prefixUrl,
			manifestUrl,
			tileSources,

			// OSD Viewer
			OSDViewer,
			showOSDViewer,
			id,
			containerId,
			osdViewerEl,
			currentCanvas,
			// initialPageIndex,
			setCanvasFromPageIndex,

			// Viewer navigation
			isNavigationEnabled,
			tocMenuItems,

			// Annotorious
			OSDAnnotator,
			OSDAnnotatorOptions,
			sequenceMode,
			setOSDAnnotatorBehaviours,
			onDeleteRemoveAnnotation,
			onCreateAnnotation,
			onDeleteAnnotation,
			onUpdateAnnotation,
			onSelectionChanged,
			onClickAnnotation,
			// printArea,
			currentAnnotation,
			annotationList,

			// Sidebar navigation tabs
			activeTabId,
			tabTime,
			setTab,

			// Form
			isFormEnabled,
			showForm,
			formProfileSchema,
			iconStatus,
			showStatusIcon,

			// Other
			debugLog
			// Receiving emits
			// updateAnnotation
		};
	}
});
</script>

<style>
.osdragon-widget {
	display:flex;
	width:100%;
	background-color:white;
}
.osd-viewer-container {
	width:100%;
}
.annotator-sidebar {
	width:35%;
	min-width:150px;
}
.annotation-form-container {
	width:100%;
}

.anno-sidebar-form-container {
	padding:0 .6rem .6rem .6rem;
}
.annotation-list a {
	margin-right: 0.2em;
}

a.annot-list-btn {
	display: inline-block;
	padding: 0.25rem 0.5rem;
	margin-bottom: 0.25rem;
	font-weight: 400;
	font-size: 0.875rem;
	line-height: 1.5;
	text-align: center;
	vertical-align: middle;
	user-select: none;
	color: #fff;
	background: #5f7470 linear-gradient(180deg,#778985,#5f7470) repeat-x;
	border:1px solid #5f7470;
	border-radius: 0.2rem;
	box-shadow: inset 0 1px 0 rgba(255,255,255,0.15),0 1px 1px rgba(0,0,0,0.075);
	transition: color 250ms ease-out 0s,background-color 250ms ease-out 0s,border-color 250ms ease-out 0s;
	-webkit-transition: color 250ms ease-out 0s,background-color 250ms ease-out 0s,border-color 250ms ease-out 0s;
	-o-transition: color 250ms ease-out 0s,background-color 250ms ease-out 0s,border-color 250ms ease-out 0s;
}
a.annot-list-btn:hover,
a.annot-list-btn:focus {
	color: #fff;
	background:	#4e5f5c linear-gradient(180deg,#687774,#4e5f5c) repeat-x;
	border-color: #485855;
}
a.annot-list-btn:active {
	background-color: #485855;
	background-image: none;
	border-color: #42514e;
}
a.annot-list-btn:focus {
	box-shadow: inset 0 1px 0 rgba(255,255,255,0.15),0 1px 1px rgba(0,0,0,0.075),0 0 0 0.2rem rgba(119,137,133,0.5);
	/*box-shadow: inset 0 3px 5px rgba(0,0,0,0.125),0 0 0 0.2rem rgba(119,137,133,0.5);*/
}

</style>
