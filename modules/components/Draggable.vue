<template>
	<h2>Test draggable</h2>
	<div class="row">
	<div class="col-md-6">
	<draggable
		v-model="list1"
		item-key="name"
		:disabled="!enabled"
		ghost-class="ghost"
		group="people"
		@start="isDragging = true"
		@end="isDragging = false"
		class="list-group"
		:move="checkMove"
		animation="200"
	>
  		<template #item="{element}">
    		<div class="list-group-item">
				<div class="handle"></div>
				{{element.name}} ({{element.id}})
			</div>
		</template>
	</draggable>
	</div>

	<div class="col-md-6">
	<draggable
        :list="list2"
        :disabled="!enabled"
        item-key="name"
		group="people"
        class="list-group"
        ghost-class="ghost"
        :move="checkMove"
        @start="dragging = true"
        @end="dragging = false"
		animation="200"
      >
        <template #item="{ element }">
          <div class="list-group-item" :class="{ 'not-draggable': !enabled }">
            {{ element.name }}
          </div>
        </template>
      </draggable>
	  </div>

	  </div>

</template>

<script>
// https://github.com/SortableJS/vue.draggable.next
const { defineComponent, defineExpose, computed, ref } = require("vue");
// const store = require("ext.iiif.annotator.store");
const VueDraggable = require("ext.iiif.lib.draggable");
// const { CdxButton, CdxIcon } = require( "@wikimedia/codex" );

module.exports = defineComponent( {
	name: "DraggableComponent",
	components: {
		"draggable": VueDraggable
	},
	props: {},
	setup(props, context) {
		const enabled = true;
		const isDragging = false;
		const list1 = ref( [] );
		list1.value = [
			{ name: "Alice", id: 0 },
			{ name: "Bernard", id: 1 },
			{ name: "Chrissy", id: 2 }
		];
		const list2 = ref( [] );
		list2.value = [
			{ name: "John", id: 0 },
			{ name: "Joao", id: 1 },
			{ name: "Jean", id: 2 }
	    ];

		function checkMove(e) {
			// console.log( e.relatedContext.element.name );
    		// console.log("Future index: " + e.draggedContext.futureIndex);
		}

		function clone(el) {
			return { name: el.name + " cloned" };
		}

		return {
			isDragging,
			enabled,
			list1,
			list2,
			checkMove
		}
	}
} );

</script>

<style>
.ghost {
	opacity: 0.5;
	background: #c8ebfb;
}
</style>