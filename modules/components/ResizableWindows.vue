<template>

	<div v-if="isEnabled" :class="layoutClass + ' ' + viewportClass" :style="wrapperStyle">
		<div class="resizable-container">
			<div v-if="orientationLayout === 'EW'"
				class="resizer-handle"
				:style="handleStyle"
				@pointerdown="onHandlePointerdownEW"
				role="separator"
				aria-orientation="vertical"
			></div>
			<div v-else-if="orientationLayout === 'NS'"
				class="resizer-handle-horizontal"
				:style="handleStyle"
				@pointerdown="onHandlePointerdownNS"
				role="separator"
				aria-orientation="horizontal"
			></div>

			<div class="resizable-grid" :style="gridStyle">
				<section class="resizable-window">
					<slot name="window1"></slot>
				</section>
				<span class="resizer-handle-placeholder"></span>
				<section class="resizable-window">
					<slot name="window2"></slot>
				</section>
			</div>

		</div>

	</div>
	<div v-else-if="!isEnabled">
		<slot name="window1"></slot>
		<slot name="window2"></slot>
	</div>

</template>

<script>
const { defineComponent, computed, ref, watch } = require("vue");

module.exports = defineComponent( {
	name: "ResizableWindows",
	props: {
		isEnabled: { type: Boolean, default: true },
		wrapperClass: { type: String, default: "resizable-layout"},
		fullViewportMode: { type: Boolean, default: false },
		wrapperStyle: { type: String, default: "" },
		resizerThickness: { type: Number, default: 10 },
		// @todo rename
		initWidthLeft: { type: String, default: "50%" },
		initWidthRight: { type: String, default: "50%" },
		orientation: { type: String, default: "EW" }
	},
	setup( props ) {
		// Default: EW (East-West)
		const orientationLayout = ref( props.orientation );
		const layoutClass = ref( props.wrapperClass );
		const handleStyle = ref( {
			left: props.initWidthLeft,
			width: props.resizerThickness + "px" 
		} );

		/**
		 * Works for both rows and columns
		 */
		function calcGridTemplateSequences( perc1, perc2 ) {
			var resizerOffset = props.resizerThickness / 2;
			var res1 = `minmax(0px, calc(${perc1} - ${resizerOffset}px)) ${props.resizerThickness}px minmax(0px, calc(${perc2} - ${resizerOffset}px))`;
			//without 
			//var res2 = `minmax(0px, ${perc1}) minmax(0px, ${perc2})`;
			return res1;
		}
		const gridStyle = ref( {
			gridTemplateColumns: calcGridTemplateSequences( props.initWidthLeft, props.initWidthRight )
		} );

		// NS (North-South)
		if ( orientationLayout.value == "NS" ) {
			handleStyle.value = {
				top: props.initWidthLeft,
				height: props.resizerThickness + 'px'
			};
			gridStyle.value = {
				gridTemplateRows: calcGridTemplateSequences( props.initWidthLeft, props.initWidthRight )
			};
		}

		function onHandlePointerdownEW( e ) {
			var parentRect = e.target.parentElement.getBoundingClientRect();
			var parentRectLeft = parentRect.left;
			var parentRectWidth = parentRect.width;
			const dragHandle = ( e ) => {
				function calcLeftPos( posX, width, minWidth = 0, maxWidth = 100 ) {
					return Math.max(
						minWidth,
						Math.min( maxWidth, (posX / width) * 100 ) );
				}
				const localX = e.clientX - parentRectLeft;
				const leftPos = calcLeftPos( localX, parentRectWidth, 0, 100 );
				const rightPos = 100 - leftPos;
				handleStyle.value = {
					left: leftPos + "%",
					right: rightPos + "%",
					width: props.resizerThickness + "px"
				};
				gridStyle.value = {
					gridTemplateColumns: calcGridTemplateSequences( leftPos + "%", rightPos + "%" )
				};
			}
			const stopDragHandle = (e) => {
				//console.log( "stop drag handle" );
				document.removeEventListener( "pointermove", dragHandle );
				document.removeEventListener( "pointerup", stopDragHandle );
				document.removeEventListener( "pointerleave", stopDragHandle );
			};
			document.addEventListener( "pointermove", dragHandle );
			document.addEventListener( "pointerup", stopDragHandle );
		}

		/**
		 * N/S counterpart to onHandlePointerdownEW()
		 * @param e
		 */
		function onHandlePointerdownNS( e ) {
			var parentRect = e.target.parentElement.getBoundingClientRect();
			var parentRectTop = parentRect.top;
			var parentRectHeight = parentRect.height;
			const dragHandle = ( e ) => {
				function calcTopPos( posY, height, minHeight = 0, maxHeight = 100 ) {
					return Math.max(
						minHeight,
						Math.min( maxHeight, (posY / height) * 100 ) );
				}
				const localY = e.clientY - parentRectTop;
				const topPos = calcTopPos( localY, parentRectHeight, 0, 100 );
				const bottomPos = 100 - topPos;
				handleStyle.value = {
					top: topPos + "%",
					bottom: bottomPos + "%",
					height: props.resizerThickness + "px"
				};
				gridStyle.value = {
					gridTemplateRows: calcGridTemplateSequences( topPos + "%", bottomPos + "%" )
				};
			}
			const stopDragHandle = (e) => {
				document.removeEventListener( "pointermove", dragHandle );
				document.removeEventListener( "pointerup", stopDragHandle );
				document.removeEventListener( "pointerleave", stopDragHandle );
			};
			document.addEventListener( "pointermove", dragHandle );
			document.addEventListener( "pointerup", stopDragHandle );
		}

		const viewportClass = ref( '' );
		watch( () => props.fullViewportMode, (n) => {
			document.body.classList.toggle( 'full-viewport-mode', n );
			viewportClass.value = n ? 'workspace--full-viewport' : '';
		});

		return {
			orientationLayout,
			layoutClass,
			handleStyle,
			gridStyle,			
			onHandlePointerdownEW,
			onHandlePointerdownNS,
			viewportClass
		}
	}
} );

</script>

<style lang="less">
.resizable-layout {
	width: 100%;
	height: 100dvh;
	box-sizing: border-box;
	background-color: #FFF;
}
.resizable-layout--fixed {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100dvh;
	z-index: 10;
	box-sizing: border-box;
	background-color:#edfaf5;
}

/* Viewport */
.workspace--full-viewport {
	position: fixed;
	inset: 0;
	width: 100%;
	height: 100dvh;
	z-index: 10;
}
.full-viewport-mode {
	overflow: hidden;
}

/* Container and grid */
.resizable-container {
	position:relative;
	width: 100%;
	max-width: 100%;
	height: 100%;
	box-sizing: border-box;
}

.resizable-grid {
	display: grid;
	/*grid-template-columns: 50% 10px 50%;*/
	width: 100%;
	max-width: 100%;
	height: 100%;
	position: relative;
	max-width:100%;
	box-sizing: border-box;

	.resizable-window {
		overflow: auto;
		box-sizing: border-box;
	}
}

/* Handle */
.resizer-handle {
	position: absolute;
	top: 0;
	bottom: 0;
	/*left: 50%;*/
	transform: translate(-50%, 0%);
	/*width: 10px;*/
	cursor: ew-resize;
	z-index: 5;
	background-color: #acacac;
	transition: background-color 0.2s ease-in;
	touch-action: none;
}
.resizer-handle:hover {
	background-color: #6c6c6c;
}

.resizer-handle-horizontal {
	position: absolute;
	top: 0;
	bottom: 0;
	width: 100%;
	transform: translate(0%, -50%);
	cursor: ns-resize;
	z-index: 5;
	background-color: #acacac;
	transition: background-color 0.2s ease-in;
	touch-action: none;
}

</style>
