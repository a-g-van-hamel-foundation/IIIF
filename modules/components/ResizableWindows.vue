<template>

	<div v-if="isEnabled" :class="layoutClass" :style="wrapperStyle">
		<div class="resizable-container">

			<div
				class="resizer-handle"
				:style="handleStyle"
				@mousedown="onHandleMousedown"
				role="separator"
				aria-orientation="vertical"
			></div>

			<div class="resizable-grid" :style="gridStyle">
				<section class="resizable-window">
					<slot name="window1"></slot>
				</section>
				<span></span>
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
const { defineComponent, computed, ref } = require("vue");

module.exports = defineComponent( {
	name: "ResizableWindows",
	props: {
		isEnabled: { type: Boolean, default: true },
		wrapperClass: { type: String, default: "resizable-layout--fixed"},
		wrapperStyle: { type: String, default: "" },
		resizerWidth: { type: Number, default: 10 },
		initWidthLeft: { type: String, default: "50%" },
		initWidthRight: { type: String, default: "50%" }
	},
	setup( props ) {
		const layoutClass = ref( props.wrapperClass );
		const handleStyle = ref({
			left: props.initWidthLeft,
			width: props.resizerWidth + "px" 
		});

		function calcGridTemplateColumns(perc1, perc2) {
			var resizerOffset = props.resizerWidth / 2;
			var res1 = `minmax(0px, calc(${perc1} - ${resizerOffset}px)) ${props.resizerWidth}px minmax(0px, calc(${perc2} - ${resizerOffset}px))`;
			//without 
			//var res2 = `minmax(0px, ${perc1}) minmax(0px, ${perc2})`;
			return res1;
		}
		const gridStyle = ref( {
			gridTemplateColumns: calcGridTemplateColumns( props.initWidthLeft, props.initWidthRight )
		} );

		function onHandleMousedown(e) {
			var parentRect = e.target.parentElement.getBoundingClientRect();
			var parentRectLeft = parentRect.left;
			var parentRectWidth = parentRect.width;

			const dragHandle = (e) => {
				function calcLeftPos(posX, width, minWidth = 0, maxWidth = 100) {
					return Math.max(
						minWidth,
						Math.min( maxWidth, (posX / width) * 100 ) );
				}
				const localX = e.clientX - parentRectLeft;
				const leftPos = calcLeftPos(localX, parentRectWidth, 0, 100);
				const rightPos = 100 - leftPos;
				handleStyle.value = {
					left: leftPos + "%",
					right: rightPos + "%",
					width: props.resizerWidth + "px"
				};
				gridStyle.value = {
					gridTemplateColumns: calcGridTemplateColumns(leftPos + "%", rightPos + "%")
				};
			}
			const stopDragHandle = (e) => {
				//console.log( "stop drag handle" );
				document.removeEventListener("mousemove", dragHandle);
				document.removeEventListener("mouseup", stopDragHandle);
				document.removeEventListener("mouseleave", stopDragHandle);
			};
			document.addEventListener( "mousemove", dragHandle );
			document.addEventListener( "mouseup", stopDragHandle );
		}

		return {
			layoutClass,
			handleStyle,
			gridStyle,
			onHandleMousedown
		}
	}
} );

</script>

<style>
.resizable-layout {
	width: 100%;
	height: 100%;
	box-sizing: border-box;
}
.resizable-layout--fixed {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100vh;
	z-index: 5;
	box-sizing: border-box;
	background-color:#edfaf5;
}

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
}
.resizable-grid .resizable-window {
	overflow: auto;
	box-sizing: border-box;
}

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
}
.resizer-handle:hover {
	background-color: #6c6c6c;
}
</style>
