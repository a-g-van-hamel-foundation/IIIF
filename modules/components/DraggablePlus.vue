<template>
    <h2>Test draggable plus</h2>
	<div class="row">
	
	<div class="col-md-6">
	<section
		id="list-container"
		ref="el1"
		class="list-group" style="min-height:100px"
	>
		<div
        	v-for="item in list1"
        	:key="item.id"
			class="list-group-item cursor-move"
    	>
			{{ item.name }}
	    </div>
	</section>
	</div>

	<div class="col-md-6">
	<section
		ref="el2"
		class="list-group" style="min-height:100px"
	>
		<div
        	v-for="item in list2"
        	:key="item.id"
			class="list-group-item cursor-move"
    	>
			{{ item.name }}
	    </div>
	</section>
	</div>

	</div>
	
</template>

<script>
const { defineComponent, defineExpose, computed, ref, onMounted } = require("vue");

//console.log( require("ext.iiif.lib.vuedraggableplus") );
//const VueDraggable = require("ext.iiif.lib.vuedraggableplus");
// browser bundle not a module
//const { VueDraggable, useDraggable, vDraggable } = VueDraggableLib.VueDraggable;
console.log( "VueDraggableLib", VueDraggableLib );
//const { VueDraggable, useDraggable, vDraggable } = VueDraggableLib.VueDraggable;
const { useDraggable } = VueDraggableLib.VueDraggable;
//console.log( "{VueDraggable}", VueDraggable1 );
//const VueDraggable = require("ext.iiif.lib.vuedraggableplus");
//console.log( "VueDraggable", VueDraggable );

module.exports = defineComponent( {
	name: "DraggablePlusComponent",
	components: {
		//"vue-draggable-plus": VueDraggable
	},
	props: {},
	setup(props, context) {
		// <HTMLElement | null>
		const el1 = ref();
		const el2 = ref();

		const list1 = ref( [] );
		list1.value = [
			{ name: "Alice", id: 0 },
			{ name: "Bernard", id: 1 },
			{ name: "Chrissy", id: 2 }
		];
		const list2 = ref( [] );
		list2.value = [
			{ name: "David", id: 3 },
			{ name: "Emmy", id: 4 },
			{ name: "Fiona", id: 5 }
		];
		const lists = [ list1, list2 ];
		const els = [ el1, el2 ];
		const starts = [];
		els.forEach( (el, index) => {
			console.log( "el", index, el );
			console.log( "lists[index]", index, lists[index] );
			//var start = ref();
			useDraggable( el, lists[index], {
				immediate: true,
				animation: 150,
				group: "people",
				onStart() { console.log('start'); },
				onUpdate() { console.log('update'); }
			});
			//starts[index] = test;
		});
		const start1 = starts[0];
		const start2 = starts[1];

		/*
		var start = ref();
		var { start } = useDraggable( el, list1, {
			immediate: false,
			animation: 150,
			onStart() { console.log('start'); },
			onUpdate() { console.log('update'); }
		});
		*/

		/*
		onMounted( () => {
			console.log( "mounted" );
			//console.log( "el1", el1.value );
			start1();
			start2();
		});
		*/

        return {
			el1,
			el2,
			list1,
			list2			
		}
    }
} );
</script>

<style>
.cursor-move {
  cursor: move;
}
.cursor-grab {
  cursor: grab;
}
</style>