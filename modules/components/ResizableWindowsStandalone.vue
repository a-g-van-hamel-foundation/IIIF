<template>
	<resizable-windows
		:is-enabled="isResizableEnabled"
		:wrapper-class="resizableClass"
		:resizer-width="10"
		:init-width-left="`50%`"
		:init-width-right="`50%`"
		:full-viewport-mode="fullViewportMode"
	>
		<template v-slot:window1>
			<div ref="leftHost" class="pane-host"></div>
		</template>
		<template v-slot:window2>
			<div class="window-menu-top">
				<cdx-button
					@click="toggleViewportMode()"
					weight="quiet"
				>
					<template v-if="!fullViewportMode">
						<cdx-icon :icon="cdxIconFullScreen" title="Enter full viewport"></cdx-icon>
					</template>
					<template v-else>
						<cdx-icon :icon="cdxIconExitFullscreen" title="Leave full viewport"></cdx-icon>
					</template>
				</cdx-button>
			</div>
			<div ref="rightHost" class="pane-host"></div>
		</template>
	</resizable-windows>
</template>

<script>
const { defineComponent, ref, onMounted } = require( "vue" );
const ResizableWindows = require( "./ResizableWindows.vue" );
const { CdxButton, CdxIcon } = require( "@wikimedia/codex" );
const { cdxIconFullScreen, cdxIconExitFullscreen } = require( "./icons.json" );

module.exports = defineComponent( {
	name: "ResizableWindowsStandalone",
	components: {
		"resizable-windows": ResizableWindows,
		CdxButton,
		CdxIcon
	},
	props: {
		configData: { type: "Object", default: {} },
		leftContent: { type: "HTMLDivElement" },
		rightContent: { type: "HTMLDivElement" }
	},
	setup ( props ) {
		const leftHost = ref( null );
		const rightHost = ref( null );
		onMounted( () => {
			leftHost.value.appendChild( props.leftContent );
			rightHost.value.appendChild( props.rightContent );
		} );

		const isResizableEnabled = ref( true );
		const resizableClass = ref( "resizable-layout" );

		const fullViewportMode = ref( false );
		function toggleViewportMode() {
			fullViewportMode.value = !fullViewportMode.value;
		}

		return {
			leftHost,
			rightHost,
			isResizableEnabled,
			resizableClass,
			fullViewportMode,
			toggleViewportMode,
			// icons
			cdxIconFullScreen,
			cdxIconExitFullscreen
		}
	}
});
</script>

<style lang="less">
.window-menu-top {
	background-color: rgba(255,255,255,0.9);
	display: flex;
	gap: 1rem;
	justify-content: flex-end;
	align-items: start;
	padding: 0.6rem;
	position: sticky;
	top: 0;
	z-index: 1;
	box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,0.1);
}

.iiif-resizable-windows {
	height: 75dvh;
	.resizable-layout {
		height: 100%;
	}
}

/* If pf for Mirador viewer is used in one of the slots */
.workspace--full-viewport #miradorframe {
	height: 100dvh;
}
</style>
