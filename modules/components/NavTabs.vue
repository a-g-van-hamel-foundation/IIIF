<template>
	<div class="annot-tab-headers">
		<template v-for="item in items" 
			:key="`annot-tab-header-` + item.id">
			<a
				:class="item.id === activeId ? 'active' : '' "
				@click="onHeaderClick(item.id)"
			>{{ item.label }}</a>
		</template>
	</div>
	<div v-for="item in items" 
		:key="`annot-tab-header-` + item.id"
		:class="item.id === activeId ? 'annot-tab-pane active' : 'annot-tab-pane hidden' "
		>
		<slot 
			:name="item.id"			
		></slot>
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require( "vue" );

module.exports = defineComponent( {
	name: "NavTabs",
	components: {},
	props: {
		items: { type: Array, default: [] },
		initialActiveId: { type: String, default: "" },
		time: { type: String|Number, required: false }
	},
	watch: {
		time: function(n) {
			//console.log( "reset time changed", n );
			//console.log( "this.$props.initialActiveId", this.$props.initialActiveId );
			this.activeId = this.$props.initialActiveId;
		},
		initialActiveId: function(n,o) {
			//console.log( "initialActiveId changed", n );
			//this.activeId = n;
		},
		activeId: function(n,o) {
			//console.log( "active id changed and is now ", n );
		}
	},
	setup(props) {
		//console.log( "props.initialActiveId", props.initialActiveId );
		const activeId = ref( props.initialActiveId );

		function onHeaderClick(id) {
			//console.log("You clicked a tab, sir?", id);
			activeId.value = id;
    	}

		return {
			activeId,
			// methods
			onHeaderClick
		}
	}
} );
</script>

<style>
.annot-tab-headers {
	display: flex;
	flex-wrap: wrap;
	width: 100%;
	list-style: none;
	list-style-type: none;
	padding-left: 0;
	margin-bottom: .6em;
}
.annot-tab-headers > a,
.annot-tab-headers > a:link {
	font-size:.9em;
	font-variant:all-small-caps;
    display: block;
    color: #495057;
    text-decoration: none;
    background-color: #fff;
	border: 1px solid transparent;
	border-top-left-radius: 0.25rem;
	border-top-right-radius: 0.25rem;
	margin-right: 2px;
	border-radius: 4px 4px 0 0;
	padding: 0.3rem .6rem;
	line-height:normal;
}
.annot-tab-headers > a:hover, 
.annot-tab-headers > a:focus, 
.annot-tab-headers > a:visited:hover {
	color: #000;
	text-decoration: none;
	border-color: #eee #eee #ddd;
}
.annot-tab-headers > a:visited {
	color: #495057;
	text-decoration: none;
}
.annot-tab-headers > a.active,
.annot-tab-headers > a.active:hover,
.annot-tab-headers > a.active:focus {
	color: #222;
	cursor: default;
	background-color: #fff;
	border-bottom: .15rem solid #5A7179;
	text-decoration: none;
}

.annot-tab-pane.hidden {
	display:none;
}

</style>
