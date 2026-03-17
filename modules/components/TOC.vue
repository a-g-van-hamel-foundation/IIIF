<template><div class="iiif-toc">

	<div class="iiif-toc-tools--top">
		<div v-if="iconStatus == 'success' ">
			<cdx-icon :icon="cdxIconCheck" class="iiif-edit-success"></cdx-icon>
		</div>
		<div v-if="iconStatus == 'fail' "><span class="iiif-edit-fail">Failed</span></div>
		<cdx-button @click="editTargetPage(targetPageId, targetSlot, list1)">Save</cdx-button>
	</div>

	<div class="iiif-toc-lists">

		<div class="iiif-toc-list-1">
			<section
				id="list-container"
				ref="el1"
				class="toc-item-group" style="min-height:100px"
			>
				<div
					v-for="(item, index) in list1"
					:key="item.id"
					:class="`toc-item toc-level-` + ( item.indentLevel ?? '0' )"
				>
					<div class="handle-left cursor-move">
						<cdx-icon :icon="cdxIconDraggable" title="Drag this item to a new position"></cdx-icon>
					</div>
					<details class="toc-item-details">
						<summary class="toc-item-header">
							<div class="toc-item-header-main">
								<cdx-icon :icon="cdxIconExpand" class="handle-expand" title="Expand to view form"></cdx-icon>
								<cdx-icon :icon="cdxIconCollapse" class="handle-collapse" title="Collapse to hide form"></cdx-icon>
								<input 
									type="text"
									name="name"
									v-model="item.name"
									placeholder="name"
									class="header-name-input form-control"
								/>
								<!-- @todo - change canvas in viewer panel -->
								<template v-if="item.canvas == 'lalalalal'">
									<button :data-canvas="item.canvas" data-canvas-window="mirador-window-1" class="btn-change-canvas btn btn-outline-dark btn-sm" data-scroll="true">View</button>
								</template>
							</div>
							
							<div class="handle-right">
								<input name="indentlevel" v-model="item.indentLevel" type="number" min="0" max="2" class="form-control" style="width:4rem;"></input>
								<span @click.prevent="handleAddAfter(index)" class="action-icon" title="Add item directly below">
									<cdx-icon :icon="cdxIconTableAddRowAfter" aria-label="Add new item below"></cdx-icon>
								</span>
								<details class="toc-item-close">
									<summary>
										<cdx-icon :icon="cdxIconClose" class="cdx-icon-warning" title="Remove item"></cdx-icon> </summary>
									<button @click="handleRemove(index)"
										class="cdx-button cdx-button--action-destructive cdx-button--size-small" aria-label="Remove this item"
										>Remove?
									</button>
								</details>
							</div>

						</summary>

						<input
							type="hidden"
							name="id"
							v-model="item.id"
							placeholder="id"
							class="form-control"
						/>

						<toc-form
							:key="`form-` + item.id"
							:is-enabled=true
							:form-profile-schema="formProfileSchema"
							v-model:value-data="item"
							:canvases="canvasIdentifiers"
						></toc-form>
						<pre>{{ item }}</pre>

					</details>
					
				</div>
			</section>

			<section class="iiif-toc-tools--bottom">
				<cdx-button @click="handleAddToBottom" title="Add new item" aria-label="Add new item">Add item</cdx-button>
			</section>
		</div>

		<!-- second list/column currently unused -->
		<div class="iiif-toc-list-2" style="display:none;">
			<section
				ref="el2"
				class="toc-item-group" style="min-height:100px"
			>
				<div
					v-for="item in list2"
					:key="item.id"
					class="toc-item cursor-move"
				>
					{{ item.name }}
				</div>	
			</section>
		</div>

	</div>

	<!-- dev only: <pre>{{ list1 }}</pre> -->
</div></template>

<script>
const { defineComponent, defineExpose, computed, ref, reactive, onMounted } = require("vue");
const { useDraggable } = VueDraggableLib.VueDraggable;
const TOCForm = require( "./TOCForm.vue" );
const { CdxButton, CdxIcon } = require( "@wikimedia/codex" );
const { cdxIconAdd, cdxIconTableAddRowAfter, cdxIconClose, cdxIconTrash, cdxIconDraggable, cdxIconExpand, cdxIconCollapse, cdxIconCheck } = require( './icons.json' );

module.exports = defineComponent( {
	name: "TOC",
	components: {
		CdxButton,
		CdxIcon,
		"toc-form": TOCForm
	},
	props: {
		formProfileData: { type: Object, default: {} },
		valueData: { type: Object, default: {} },
		targetPage: { type: String, default: "" },
		targetPageId: { type: String, default: "0" },
		targetSlot: { type: String, default: "main" },
		iiifManifest: { type: String, default: null },
		canvasIdentifiers: { type: Array }
	},
	setup(props, context) {
		// form
		//debugLog( "TOC.vue - formProfileData", props.formProfileData );
		const formProfileSchema = ref( props.formProfileData ?? {} );

		// data
		// <HTMLElement | null>
		const el1 = ref();
		const el2 = ref();

		// Main data list
		const list1 = ref( [] );
		list1.value = props.valueData !== undefined
			? ( props.valueData.items ?? [] )
			: [];
		// Previously tested but no current need for this
		// list2, list3, etc., could be used to turn this into a kanban board
		const list2 = ref( [] );
		/*
		list2.value = [
			{ name: "Prologue", id: '3' },
			{ name: "Body", id: '4' },
			{ name: "Epilogue", id: '5' }
		];
		*/
		const lists = [ list1, list2 ];
		const els = [ el1, el2 ];
		els.forEach( (el, index) => {
			useDraggable( el, lists[index], {
				immediate: true,
				animation: 150,
				group: "items",
				onStart() { debugLog('start draggable'); },
				onUpdate() { debugLog('update draggable'); },
				filter: 'input,form,fieldset',
				preventOnFilter: false
			});
		});

		function getRandomNumber() {
			// 7 digits
			return Math.floor(1000000 + Math.random() * 9000000)
		}

		function handleAddToBottom() {
			const random = getRandomNumber();
			list1.value.push({
				name: "",
				id: `${random}`
			});
		}

		function handleAddAfter(index) {
			const random = getRandomNumber();
			const newItem = {
				name: "",
				id: `${random}`
			}
			list1.value.splice( index + 1, 0, newItem );
		}

		function handleRemove(index) {
			list1.value.splice( index, 1 );
		}

		// Edit target page using WSSlots
		function editTargetPage(targetPageId, targetSlot, content) {
			// Requires WSSlots!
			var editParams = {
				action: "editslot",
				format: "json",
				formatversion: "2",
				// title: targetPage,
				pageid: targetPageId,
				slot: targetSlot,
				text: JSON.stringify( { "items": content } ),
				contentformat: "application/json",
				contentmodel: "json",
				summary: `Saved changes in slot (${targetSlot}) with the IIIF TOC Creator`
			};
			const mwApi = new mw.Api();
			const self = this;
			mwApi.postWithToken('csrf', editParams )
			.done( function(data) {
				self.showStatusIcon("success");
				debugLog( "Saved successfully with the IIIF TOC Creator" );
			})
			.fail( function(e) {
				self.showStatusIcon("fail");
				debugLog( "Failed to save data with the IIIF TOC Creator", e );
			});
		}

		const iconStatus = ref( "" );
		function showStatusIcon(status) {
			iconStatus.value = status;
			var timeOut = setTimeout( function() {
				iconStatus.value = "";
			}, 2500 );
		}

		function debugLog(msg, res) {
			//console.log( "TOCForm: " + msg, res || "" );
		}

        return {
			formProfileSchema,
			el1,
			el2,
			list1,
			list2,
			editTargetPage,
			iconStatus,
			showStatusIcon,
			handleAddToBottom,
			handleAddAfter,
			handleRemove,
			cdxIconClose,
			cdxIconTrash,
			cdxIconAdd,
			cdxIconTableAddRowAfter,
			cdxIconDraggable,
			cdxIconExpand,
			cdxIconCollapse,
			cdxIconCheck,
			debugLog
		}
    }
} );
</script>

<style lang="less">
.iiif-toc-lists {
	margin-bottom: 2rem;
	background-color: #edfaf5;
}

.toc-item-group {
	display: flex;
	flex-direction: column;
	padding-left: 0;
	margin-bottom: 0;
	border-radius: 0.25rem;
	.toc-item {
		position: relative;
		padding: 0;
  		background-color: #fff;
  		border: 1px solid rgba(0,0,0,0.125);
		display:flex;
		width:100%;
		gap:1rem;
		animation: announceItem 2s ease-in-out;
		box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
		&.toc-level-1 {
			border-left: 0.5rem solid #d2cfcf;
		}
		&.toc-level-2 {
			border-left: 1rem solid #d2cfcf;
		}
	}
	.toc-item > * {
		padding: .75rem .75rem;
	}
	.toc-item:first-child {
		border-top-left-radius: inherit;
  		border-top-right-radius: inherit;
	}
	.toc-item .handle-left {
		display:flex;
		flex-direction:column;
		gap: 1rem;
		justify-content: space-between;
		background-color: #edfaf5;
	}
	.toc-item .action-icon {
		cursor: pointer;
	}

	.handle-right {
		display: flex;
		gap: 1rem;
		flex-direction: row;
	}
	.toc-form {
  		padding-top: 1rem;
	}
}

.cursor-move {
	cursor: move;
}
.cursor-grab {
	cursor: grab;
}

.iiif-toc-tools--top {
	background-color: #edfaf5;
	display:flex;
	justify-content: flex-end;
	align-items: start;
	padding:.6rem;
	position: sticky;
	top: 0;
	z-index:1;
	box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,0.1);
	.cdx-icon.iiif-edit-success {
		color:#69B33F;
		margin-right:.6rem;
	}
	.iiif-edit-fail {
		color:red;
		margin-right:.6rem;
	}
}

.iiif-toc-tools--bottom {
	background-color: #edfaf5;
	padding: 1rem;
}

@keyframes announceItem {
	0% { background: #edfaf5; }
	//0%    {background: #eaf5f2; }
	100%  {background: inherit; }
}

.toc-item .toc-item-header {
	display:flex;
	gap: 1rem;
	justify-content: space-between;
	.toc-item-header .handle-expand,
	.toc-item-header:hover .handle-collapse {
		color: #5f7470;
	}
	.toc-item-header .handle-collapse,
	.toc-item-header:hover .handle-expand {
		color: inherit;
	}
	.toc-item-header-main {
		display: flex;
		gap: 1rem;
		width: 100%;
	}
}

.toc-item .toc-item-details {
	width:100%;
}

.toc-item-close {
	display:flex;
}
.toc-item-close summary::marker {
	content: "";
}

.cdx-icon-warning:hover {
	color: red;
}

.flex-gap-1 {
  gap: 1rem;
}

/* Toggle */
details .handle-expand {
	display:inline-flex;
}
details .handle-collapse {
	display:none;
}
details:open .handle-expand {
	display:none;
}
details:open .handle-collapse {
	display:inline-flex;
}

/* Animate transition */
details[open] summary ~ * {
	animation: fadeIn .5s ease-in-out;
}
@keyframes fadeIn {
	0%    {opacity: 0; }
	100%  {opacity: 1; }
}

</style>
