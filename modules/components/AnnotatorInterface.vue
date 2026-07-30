<template>
<div
	:id="containerId"
	class="osdragon-widget"
>
	<resizable-windows
		:wrapper-class="resizableClass"
		:wrapper-style="resizableStyle"
		:resizer-width="10"
		:is-enabled="isResizableEnabled"
		:init-width-left="`70%`"
		:init-width-right="`30%`"
	>

	<template v-slot:window1>
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
					:is-viewer-mode="isViewerMode"
					@emit-annotation-toggle="toggleOSDDrawingTool"
				></osd-viewer>
			</template>
			
		</div>
	</template>

	<template v-slot:window2>
		<div class="annotator-sidebar">
			<div class="anno-sidebar-form-container">
				<nav-tabs
					:items="navTabItems"
					:initial-active-id="activeTabId"
					:time="tabTime"
				>
					<template #form v-if="!isViewerMode">
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
							:custom-options="customOptions"
						></annotator-form>
					</template>
					<template #dataset v-if="isViewerMode">
						<annotator-dataset
							ref="annotatordataset"
							:current-annotation="currentAnnotation"
							:current-canvas="currentCanvas"
							:presentation-method="presentationMethod"
							:profile-schema="formProfileSchema"
						>testing viewer mode</annotator-dataset>
					</template>

					<template #annotations >
						<div class="annotation-list">
							<template v-for="(anno, index) in annotatedCanvasRefList">
								<a v-if="anno !== undefined"
									:key="`annot-list-` + index"
									class="annot-list-btn"
									@click="setCanvasFromPageIndex(anno.canvasIndex)"
								>{{ anno.label }}</a>
							</template>
						</div>
					</template>

					<template #info >
						<div v-html="summary"></div>
					</template>

				</nav-tabs>
			</div>

		</div>
	</template>

	</resizable-windows>
</div>
</template>

<script>
const { defineComponent, computed, ref, watch, onMounted, markRaw } = require("vue");
const store = require("ext.iiif.annotator.store");
const OSDViewerModule = require( "./OSDViewer.vue" );
const AnnotoriousOSD = require( "ext.iiif.lib.annotorious.osd" );
const { W3CImageFormat } = require( "ext.iiif.lib.annotorious.osd" );
const SequenceModePlugin = require( "ext.iiif.lib.annotorious.plugin.sequencemode" ).default;
const AnnotatorForm = require( "./AnnotatorForm.vue" );
const AnnotatorDataset = require( "./AnnotatorDataset.vue" );
const NavTabs = require( "./NavTabs.vue" );
const ResizableWindows = require( "./ResizableWindows.vue" );
const { CdxButton, CdxIcon } = require( "@wikimedia/codex" );

module.exports = defineComponent( {
	name: "AnnotatorInterface",
	components: {
		"cdx-button": CdxButton,
		"cdx-icon": CdxIcon,
		"osd-viewer": OSDViewerModule,
		"annotator-form": AnnotatorForm,
		"annotator-dataset": AnnotatorDataset,
		"nav-tabs": NavTabs,
		ResizableWindows
	},
	props: {
		configProps: {
			type: Object,
			default: {}
		},
		manifestObj: {
			type: Object,
			default: {}
		},
		canvasItems: {
			type: Array,
			default: []
		},
		tileSourcesFromManifest: {
			type: Array,
			default: []
		},
		presentationMethod: { type: String, default: "profile", description: "..." },
		formProfile: { type: Object, default: null },
		formProfiles: { type: Object, default: {} },
		initialAnnotationPages: {
			type: Array,
			default: []
		},
		summary: { type: String, default: "" }
	},
	setup(props, context) {
		// context.emit, context.expose

		/**
		 * Dev only
		 * @param msg
		 * @param loggable
		 */
		function debugLog( msg = "(No message)", loggable = "" ) {
			// console.log( "AnnotatorInterface: " + msg, loggable );
		}

		const prefixUrl = ref( mw.config.get("wgExtensionAssetsPath") + "/IIIF/modules/lib-openseadragon/images/" );
		const manifestUrl = ref( props.configProps['manifest'] ?? null );
		const tileSources = ref( props.tileSourcesFromManifest );

		// OSD Viewer
		const OSDViewer = ref( null );
		const showOSDViewer = ref( true );
		const containerId = ref( "osdragon-widget-" + props.configProps['id'] ?? "osdragon-widget" );
		const id = ref( "osd-" + props.configProps['id'] ?? "osd-viewer" );
		const osdViewerEl = computed( () => {
			return document.getElementById( "osd-" + props.configProps['id'] ?? "osd-viewer" );
		});

		// OSD Viewer navigation / canvases
		function getPageIndexFromUrl() {
			const urlParams = new URLSearchParams(window.location.search);
			const defaultIndex = props.configProps.canvasIndex ?? "0";
			const index = parseInt( urlParams.get("cdx") ?? defaultIndex );
			return index;
		}
		const initialPageIndex = getPageIndexFromUrl();
		store.commit("updateCurrentCanvas", props.canvasItems[initialPageIndex] );
		const currentCanvas = computed( () => {
			return store.getters.getCurrentCanvas ?? null;
		});
		function setCanvasFromPageIndex(idx) {
			if (typeof idx === "string" ) {
				idx = parseInt(idx);
			}
			store.commit("updateCurrentCanvas", props.canvasItems[idx]);
		}

		// OSD Viewer navigation/TOC
		const isViewerMode = ref( props.configProps["mode"] === "viewer" );
		debugLog( "Are we in viewer mode?", isViewerMode.value );

		const isNavigationEnabled = ref( true );
		function createTOCFromCanvasItems( canvasItems ) {
			let toc = [];
			for (let i = 0; i < canvasItems.length; i++) {
 				toc[i] = {
					value: canvasItems[i]["index"],
					label: canvasItems[i]["label"]
				}
			}
			return toc;
		}
		const tocMenuItems = ref( createTOCFromCanvasItems( props.canvasItems ) );

		// OSD Annotorious
		const OSDAnnotator = ref(null);
		const OSDAnnotatorOptions = ref({
			drawingEnabled: false,
			style: {
				fill: '#ff0000',
				fillOpacity: 0.15
			},
			multiSelect: true
		});
		if ( isViewerMode.value ) {
			OSDAnnotatorOptions.value.userSelectAction = "SELECT";
		}
		const sequenceMode = ref(true);
		const currentAnnotation = ref(null);
		// Reference data
		const annotatedCanvasRefList = ref( [] );
		// Print area for dev info - to be disabled in production:
		// var printArea = ref( '' );

		/** Emitter handler: receives the OSDViewer instance 
		 * from the child component.
		 */
		function getOSDViewerFromChild( osdViewerInstance ) {
			// Why markRaw? This OpenSeadragon lib instance is 
			// 'opaque', i.e. must be left to manage its own 
			// internal state. No deep-tracking by Vue.
			OSDViewer.value = markRaw( osdViewerInstance );
		}

		function initOSDAnnotator() {
			// W3CImageFormat adapter
			if ( typeof currentCanvas.value.url !== "undefined" ) {
				debugLog( "Attempting to use W3CImageFormat with", currentCanvas.value.url );
				OSDAnnotatorOptions.value.adapter = AnnotoriousOSD.W3CImageFormat( currentCanvas.value.url );
			} else {
				console.error( "Something went wrong. The current canvas URL (currentCanvas.value.url) is undefined");
			}
			// Why markRaw? Same as OSDViewer above:
			// opaque Annotorious instance
			// not plain state we want Vue to track.
			OSDAnnotator.value = markRaw( AnnotoriousOSD.createOSDAnnotator(
				OSDViewer.value,
				OSDAnnotatorOptions.value
			) );
			AnnotoriousOSD.SequenceModePlugin = SequenceModePlugin(
				OSDAnnotator.value,
				OSDViewer.value,
				props.initialAnnotationPages ?? [],
				props.canvasItems
			);
			if( !isViewerMode.value ) {
				setOSDAnnotatorBehaviours();
			} else {
				// viewer mode
				onClickAnnotation();
				OSDAnnotator.value.on("selectionChanged", function(annotations) {
					OSDViewer.value.zoomPerClick = 1.5;
				});
			}
			// Set current MW user, not Guest
			OSDAnnotator.value.setUser({
				id: mw.user.id(),
				name: mw.user.id()
		 	});
			// @todo - Should we do this here?
			updateAnnotatedCanvasRefList( OSDAnnotator.value.exportAllAnnotations() );
		}

		// Emitter handler
		function toggleOSDDrawingTool( doAnnotate = false ) {
			OSDAnnotatorOptions.value.drawingEnabled = doAnnotate;
			OSDAnnotator.value.setDrawingEnabled(doAnnotate);
			OSDViewer.value.zoomPerClick = doAnnotate ? 1 : 1.5;
			if ( !doAnnotate ) {
				OSDAnnotator.value.cancelDrawing();
			}
		}

		// @todo unused so probably remove (may not even work)
		function getCurrentSourceId() {
			return OSDViewer.value.tileSources[OSDViewer.value.currentPage];
		}

		/**
		 * Update annotation with new data from AnnotatorForm
		 * except for image coordinates in 'target',
		 * and 'creator' and 'created'
		 */
		function updateAnnotation( updatedAnnotation ) {
			// Because AnnotatorForm could not check for intermediate
			// changes (if any) to image coordinates, let's get them now.
			// (1) get current annotation without the new data
			const latestAnno = OSDAnnotator.value.getAnnotationById(updatedAnnotation.id);

			// (2) Update with new data
			// Clone rather than alias latestAnno: mutating the same object
			// Annotorious has stored internally would make its before/after
			// diff see no change, and it could then skip firing its own
			// 'updateAnnotation' event (which is what triggers prepareAndWriteToWiki()).
			const newAnno = { ...latestAnno };
			newAnno.body = updatedAnnotation.body ?? updatedAnnotation.bodies ?? [];

			OSDAnnotator.value.updateAnnotation(newAnno);
			debugLog( "updateAnnotation: new annotation before update", JSON.stringify(newAnno) );
			// Don't use prepareAndWriteToWiki() here because of API edit lag
		}

		function updateAnnotatedCanvasRefList(annotationPages) {
			const list = [];
			for (const anno of annotationPages) {
				let idx = anno.references.index;
				list[idx] = {
					canvasIndex: idx,
					canvasId: anno.references.canvasId,
					label: props.canvasItems[idx]["label"] ?? ""
				};
			}
			annotatedCanvasRefList.value = list;
		}

		// Page ID can be '0' (i.e. page does not exist)
		// and changes if we create the page
		const targetPageIdProxy = ref( props.configProps.targetId ?? "0" );
		// Slot defaults to "main"
		const targetSlot = ref( (props.configProps.targetSlot !== null && props.configProps.targetSlot !== "") ? props.configProps.targetSlot : "main" );

		function prepareAndWriteToWiki() {
			// Get AnnotationPages
			const annotationPages = OSDAnnotator.value.exportAllAnnotations();

			// Reflist check
			const prevAnnotatedCanvasRefList = annotatedCanvasRefList.value;
			updateAnnotatedCanvasRefList( annotationPages );
			if (annotatedCanvasRefList.value === prevAnnotatedCanvasRefList) {
				// Should not happen: updateAnnotatedCanvasRefList()
				// always assigns a new array.
				showStatusIcon("nochange");
				debugLog( "prepareAndWriteToWiki(): no changes in annotation list" );
				return;
			} else {
				debugLog( "prepareAndWriteToWiki(): attempting to save ", JSON.stringify(annotationPages) );
			}

			// Prepare the API action
			const targetPage = props.configProps.target ?? null;
			if (targetPage === null || targetPage === "") {
				console.error( "AnnotationPages could not be written to a wiki page. Reason: no wiki page specified." );
				return;
			} else if(targetPageIdProxy.value == "0") {
				// Create intended page first.
				let createParams = {
					action: "edit",
					title: targetPage,
					text: "",
					tags: "iiif-toc-edit",
					summary: "Create empty data page for IIIF annotation tool"
				};
				if (targetSlot.value === "main") {
					createParams.text = "{}";
					createParams.contentformat = "application/json";
					createParams.contentmodel = "json";
				}
				new mw.Api().postWithToken("csrf", createParams)
				.done( function(data) {
					if (data.hasOwnProperty("warnings")) {
						showStatusIcon("fail");
						console.error(data.warnings.main["*"]);
					} else {
						targetPageIdProxy.value = data.edit.pageid;
						writeToWiki(annotationPages);
					}
				})
				.fail( function(e) {
					showStatusIcon("fail");
					console.error(e);
				});
			} else {
				writeToWiki(annotationPages);
			}
		}

		/**
		 * Should not be used independently of prepareAndWriteToWiki()
		 */
		function writeToWiki(annotationPages) {
			const wikiPage = props.configProps.target ?? null;
			let editParams;
			if (targetSlot.value !== "main") {
				debugLog( "Using editslot for slot", targetSlot.value );
				// WSSlots' editslot.
				// Content model cannot be set/changed through action
				editParams = {
					action: "editslot",
					format: "json",
					formatversion: "2",
					title: wikiPage,
					slot: targetSlot.value,
					text: JSON.stringify(annotationPages),
					summary: `Saved annotationPages in slot (${targetSlot.value}) with IIIF Annotator`
				};
			} else {
				editParams = {
					action: "edit",
					format: "json",
					formatversion: "2",
					title: wikiPage,
					text: JSON.stringify(annotationPages),
					contentformat: "application/json",
					contentmodel: props.configProps['targetContentModel'] ?? "json",
					tags: "iiif-annotator-edit",
					summary: "Saved AnnotationPages with IIIF Annotator"
				};
			}

			// Perform the API action
			new mw.Api().postWithToken('csrf', editParams )
			.done( function(data) {
				showStatusIcon("success");
				debugLog( "API response", data );
				debugLog( "Successfully saved annotationPages", JSON.stringify(annotationPages) );
			})
			.fail( function(e) {
				console.error( "Failed to save data with the IIIF Annotator", e );
				showStatusIcon("fail");
			});
		}

		function setOSDAnnotatorBehaviours() {
			onDeleteRemoveAnnotation();
			onCreateAnnotation();
			onUpdateAnnotation();
			onDeleteAnnotation();
			onClickAnnotation();
			onSelectionChanged();
		}

		function onDeleteRemoveAnnotation() {
			osdViewerEl.value.addEventListener("keydown", function(evt) {
				if (evt.key === "Delete" || evt.key === "Backspace") {
					const selected = OSDAnnotator.value.getSelected();
					if (typeof selected !== "undefined") {
						selected.forEach( function(anno) {
							debugLog( "onDeleteRemoveAnnotation", anno );
							debugLog( "all current annotations", OSDAnnotator.value.exportAllAnnotations());
							OSDAnnotator.value.removeAnnotation(anno);
						});
						// don't use prepareAndwriteToWiki() here
						showForm(false);
					}
				}
			});
		}
		function onCreateAnnotation() {
			OSDAnnotator.value.on("createAnnotation", function(annotation) {
				// printArea = JSON.stringify( annotation );
				currentAnnotation.value = annotation ?? null;
				debugLog('currentCanvas', currentCanvas.value);
				let newAnnotation = JSON.parse(JSON.stringify(annotation));

		  		debugLog("Created annotation (createAnnotation fired)", newAnnotation);
				setTab("form");
				showForm(true);
				//isFormEnabled.value = true;

				prepareAndWriteToWiki();
			});
		}
		function onUpdateAnnotation() {
			OSDAnnotator.value.on('updateAnnotation', function(updated, previous ) {
				// debugLog('Annotation was updated and is now', updated);
				prepareAndWriteToWiki();
			});
		}
		function onSelectionChanged() {
			OSDAnnotator.value.on("selectionChanged", function(annotations) {
				//debugLog('Selection changed (selectionChanged)', annotations );
				debugLog('Selection changed (selectionChanged)', annotations );
				if( !OSDAnnotator.value.isDrawingEnabled() ) {
					// Set/reset to default zoom factor in case
					// onClickAnnotation previously set it to 1
					OSDViewer.value.zoomPerClick = 1.5;
				}

				// May be used for debugging purposes:
				if( Array.isArray(annotations) && annotations.length === 0 ) {
					debugLog( "Annotations (array) selected", "none" );
					// Presumably just deleted
				} else if( annotations[0] !== undefined && annotations[0]["id"] !== undefined ) {
					debugLog( "Annotations (array) selected", annotations );
					if( annotations.length > 1 ) {
						// Do not associate a form with multiple shapes
						//isFormEnabled.value = false;
						showForm(false);
					}
				} else if( annotations["target"] !== undefined ) {
					debugLog( "Annotation (obj) with target selected", annotations );
				} else {
					debugLog( "Annotation (obj) empty.");
				}
			});
		}

		function onDeleteAnnotation() {
			OSDAnnotator.value.on("deleteAnnotation", function(annotation) {
				if( typeof annotation === "undefined" ) {
					debugLog("onDeleteAnnotation: annotation is undefined");
				} else {
					//debugLog("onDeleteAnnotation", annotation);
				}
				// Form - see now onDeleteRemoveAnnotation
				prepareAndWriteToWiki();
				//formKey.value = Date.now();
				//isFormEnabled.value = false;
			});
		}

		// cf. onSelectionChanged, which checks if multiple shapes are selected
		function onClickAnnotation() {
			OSDAnnotator.value.on("clickAnnotation", function(annotation, originalEvent) {
				debugLog('Annotation clicked:', annotation );

				// Disable form just in case ????
				// isFormEnabled.value = false;
				// formKey.value = Date.now();

				// Disable zoom on click
				OSDViewer.value.zoomPerClick = 1;
				currentAnnotation.value = annotation ?? null;
				/*
				if ( currentAnnotation.value !== undefined
					&& annotation.id === currentAnnotation.value.id ) {
					// No need to start anew. Keep current data but
					// there are some issues with this approach - @todo
					currentAnnotation.value = annotation;
				} else {
					currentAnnotation.value = annotation;
				}
				*/
				setTab( !isViewerMode.value ? "form" : "dataset");
				showForm(true);
				// dev: printArea.value = JSON.stringify( annotation );
			});
		}

		// Navigation tabs
		const activeTabId = ref( "" );
		const navTabItems = ref( [] );
		if ( !isViewerMode.value ) {
			navTabItems.value = [
				{ id: 'form', label: mw.message( 'iiif-annotator-tab-form' ).text() },
				{ id: 'annotations', label: mw.message( 'iiif-annotator-tab-annotations' ).text() },
				{ id: 'info', label: "info" }
			];
			activeTabId.value = "form";
		} else {
			navTabItems.value = [
				{ id: 'dataset', label: "Data" },
				{ id: 'annotations', label: mw.message( 'iiif-annotator-tab-annotations' ).text() },
				{ id: 'info', label: "info" }
			];
			activeTabId.value = "dataset";
		}
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
		const customOptions = ref( {} );
		if (props.configProps.customOptions) {
			customOptions.value = JSON.parse(props.configProps.customOptions);
		}
		const formProfileSchema = ref(
			props.formProfile ?? formProfileDefault
		);
		const formKey = ref(0);
		const isFormEnabled = ref( false );
		function showForm(isEnabled) {
			isFormEnabled.value = isEnabled;
			formKey.value = Date.now();
		}
		// Edit status
		const iconStatus = ref( "" );
		function showStatusIcon(status) {
			iconStatus.value = status;
			setTimeout( function() {
				iconStatus.value = "";
			}, 2500 );
		}

		// Frame
		const resizableClass = ref( "annotation-frame-resizable" );
		const resizableStyle = ref( "" );
		const isResizableEnabled = ref( true );

		// Watchers
		watch( currentCanvas, function(n, o) {
			showForm(false);
		});

		watch( currentAnnotation, function(n, o) {
			debugLog( "currentAnnotation being watched:", n );
			// In the event that not all Annotations were created 
			// using the same form, allows for the use of multiple 
			// profiles.
			// @todo maybe profileid info is best taken from 
			// AnnotationPage references section
			if ( typeof n.body[0] !== 'undefined' && typeof n.body[0].profileid !== 'undefined'
			&& Object.keys(props.formProfiles).length > 0
			//&& typeof props.formProfiles.length !== "object"
			) {
				formProfileSchema.value = props.formProfiles[n.body[0].profileid];
			}
		});

		return {
			prefixUrl,
			manifestUrl,
			tileSources,
			targetPageIdProxy,
			targetSlot,

			// OSD Viewer
			OSDViewer,
			showOSDViewer,
			id,
			containerId,
			osdViewerEl,
			currentCanvas,
			// initialPageIndex,
			setCanvasFromPageIndex,
			getOSDViewerFromChild,

			// Viewer navigation
			isViewerMode,
			isNavigationEnabled,
			tocMenuItems,

			// Annotorious
			OSDAnnotator,
			OSDAnnotatorOptions,
			initOSDAnnotator,
			sequenceMode,
			setOSDAnnotatorBehaviours,
			toggleOSDDrawingTool,
			getCurrentSourceId,
			updateAnnotation,
			updateAnnotatedCanvasRefList,
			prepareAndWriteToWiki,
			onDeleteRemoveAnnotation,
			onCreateAnnotation,
			onDeleteAnnotation,
			onUpdateAnnotation,
			onSelectionChanged,
			onClickAnnotation,
			// printArea,
			currentAnnotation,
			annotatedCanvasRefList,

			// Sidebar navigation tabs
			activeTabId,
			tabTime,
			setTab,
			navTabItems,

			// Form
			isFormEnabled,
			showForm,
			customOptions,
			formProfileSchema,
			formKey,
			iconStatus,
			showStatusIcon,

			resizableClass,
			resizableStyle,
			isResizableEnabled,

			// Other
			debugLog
			// Receiving emits
			// updateAnnotation
		};
	}
});
</script>

<style lang="less">
.osdragon-widget {
	display:flex;
	width:100%;
	background-color:white;
}
.osd-viewer-container {
	width:100%;
}
.annotator-sidebar {
	/*width:35%;
	min-width:150px;*/
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
	text-decoration: none;
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

// Summary / metadata styling moved to ext.iiif.styles.less;

.annotation-frame-resizable {
	width:100%;
	max-width:100%;
}
</style>
