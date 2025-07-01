<template>
<div
	:id="containerId"
	:class="containerClass"	
>
	<template v-for="(img, index) in galleryItems">	
		<a v-if="img !== undefined"
			:key="`gallery-item-` + index"
			class="gallery-item"
			
			:href="img.srcfull"
			:data-pswp-width="calculateWidth(img.xywh)"
			:data-pswp-height="calculateHeight(img.xywh)"
			target="_blank"
			rel="noreferrer"
		>
			<span class="icon-alert"><cdx-icon :icon="cdxIconZoomIn"></cdx-icon></span>
			<img
				:src="img.src"
				class="iiif-img-thumb"
				alt=""
			></img>
			<template v-if="createCaption(img) !== ''">
				<div class="hidden-caption-content">
					<div v-html="createCaption(img)"></div>
				</div>
			</template>
		</a>
	</template>
</div>
</template>

<script>
const { defineComponent, defineExpose, computed, ref } = require("vue");
const { CdxIcon } = require( "@wikimedia/codex" );
const { cdxIconImage, cdxIconZoomIn } = require( "./icons.json" );
const PhotoSwipeLightbox = require( "ext.iiif.lib.photoswipe.lightbox" );
const PhotoSwipe = require( "ext.iiif.lib.photoswipe" );
console.log( "PhotoSwipe", PhotoSwipe );

module.exports = defineComponent( {
	name: "Gallery",
	props: {
		annotationItems: { type: Array, default: [] },
		settings: { type: Array, default: [] }
	},
	components: {
		CdxIcon
	},
	mounted() {
		const Lightbox = new PhotoSwipeLightbox({
			gallery: "#" + this.containerId,
			children: "a.gallery-item",
			pswpModule: PhotoSwipe
		});
		Lightbox.on("uiRegister", function() {
			Lightbox.pswp.ui.registerElement({
				name: "iiif-caption",
				order: 9,
				isButton: false,
				appendTo: "root",
				html: "Caption text",
				onInit: (el, pswp) => {
				Lightbox.pswp.on("change", () => {
					const currSlideElement = Lightbox.pswp.currSlide.data.element;
					//console.log( "currSlideElement",currSlideElement);
					let captionHTML = "";
					if (currSlideElement) {
						const hiddenCaption = currSlideElement.querySelector(".hidden-caption-content");
						//console.log( "hiddenCaption", hiddenCaption );
						if (hiddenCaption !== undefined && hiddenCaption !== null) {
							// get caption from element with class hidden-caption-content
							captionHTML = hiddenCaption.innerHTML;
						} else {
							// get caption from alt attribute?
							//captionHTML = currSlideElement.querySelector('img').getAttribute('alt');
							captionHTML = '';
						}
					}
					el.innerHTML = captionHTML || "";
				});
				}
			});
		});
		Lightbox.init();
		//console.log("Lightbox", Lightbox );
	},
	setup(props) {
		const containerId = ref( "iiif-annotation-gallery-grid" );
		// todo props.settings.layout
		const containerClass = ref( computed( () => {
			if ( props.settings.layout === "columns" ) {
				var baseClass = "layout--columns";
			} else {
				var baseClass = "layout--even-grid";
			}
			// default
			var allowedNumbersOfColumns = [ 1, 2, 3, 4, 5, 6, 8, 12 ];
			var numberOfColumns = typeof( props.settings.columns) === "string" ? parseInt(props.settings.columns) : props.settings.columns;
			if ( allowedNumbersOfColumns.includes( numberOfColumns ) ) {
				var cl = "grid-cols-" + numberOfColumns.toString();
			} else {
				var cl = "grid-cols-4";
			}
			return `${baseClass} ${cl}`;
		}) );

		console.log( "props.annotationItems", props.annotationItems );
		const galleryItems = props.annotationItems;

		function calculateWidth( xywh ) {
			var foo = xywh.split(",");
			return foo[2];
		}
		function calculateHeight( xywh ) {
			var foo = xywh.split(",");
			return foo[3];
		}

		function createCaption( img ) {
			if ( img.caption !== null ) {
				return img.caption;
			}
			return "";
		}

		//
		return {
			containerId,
			containerClass,
			//Lightbox,
			galleryItems,
			calculateWidth,
			calculateHeight,
			createCaption,

			cdxIconZoomIn
		}
	}
} );
</script>

<style>
/* Even block/rectangle grid (responsive) */
.layout--even-grid {
	--gap: 0.25em;
	--size: calc(80vw / var(--numcolumns));
	display: grid;
	grid-template-columns: repeat(var(--numcolumns), 1fr);
	grid-template-rows: auto;
	gap: var(--gap);
	align-items: stretch;
}

.layout--even-grid .gallery-item {
	grid-row: span 2;
	height: calc(var(--size) - var(--gap));
	background-color: #ababab;
	/*
	height: 150px;
	grid-column: span 2;
	height: calc(var(--size) - var(--gap));
	max-height:250px;
	*/
	position:relative;
	box-shadow: 0px 3px 15px rgba(0,0,0,0.2);
}

.layout--even-grid .iiif-img-thumb {
	width: 100%;
	height: 100%;
	-o-object-fit: cover;
	object-fit: cover;
	object-position: center center;
}

/* Regular column grid (responsive) */
.layout--columns {
	display:block;
	column-count: var(--numcolumns);
	column-gap: 0.25em;
	width:100%;
	height:auto;
}
.layout--columns .gallery-item {
	break-inside: avoid;
}
.layout--columns .iiif-img-thumb {
	margin-bottom: 0.25em;
	width: 100%;
	height: auto;
}

/* Responsive values for both layouts */
.grid-cols-1 { --numcolumns: 1; }
.grid-cols-2 { --numcolumns: 2; }
.grid-cols-3 { --numcolumns: 3; }
.grid-cols-4 { --numcolumns: 4; }
.grid-cols-5 { --numcolumns: 5; }
.grid-cols-6 { --numcolumns: 6; }
.grid-cols-8 { --numcolumns: 8; }
.grid-cols-12 { --numcolumns: 12; }

@media screen and (max-width: 767px) {
	.grid-cols-1 { --numcolumns: 1; }
	.grid-cols-2 { --numcolumns: 2; }
	.grid-cols-3 { --numcolumns: 2; }
	.grid-cols-4 { --numcolumns: 3; }
	.grid-cols-5 { --numcolumns: 4; }
	.grid-cols-6 { --numcolumns: 4; }
	.grid-cols-8 { --numcolumns: 6; }
	.grid-cols-12 { --numcolumns: 8; }
}

@media screen and (max-width: 575px) {
	.grid-cols-1 { --numcolumns: 1; }
	.grid-cols-2 { --numcolumns: 1; }
	.grid-cols-3 { --numcolumns: 2; }
	.grid-cols-4 { --numcolumns: 2; }
	.grid-cols-5 { --numcolumns: 3; }
	.grid-cols-6 { --numcolumns: 3; }
	.grid-cols-8 { --numcolumns: 4; }
	.grid-cols-12 { --numcolumns: 6; }
}

/* Indicator icon */
.gallery-item .icon-alert {
	display:none;
}
.layout--even-grid .gallery-item:hover .icon-alert {
	display:block;
	position:absolute;
	right:.4em;
	top:.2em;
	line-height: 1em;
	height: 1em;
	width: 1em;
	animation: fadeIn 0.5s;
}
.layout--even-grid .gallery-item:hover .icon-alert .cdx-icon {
	color:#FFF;
	box-shadow: 0px 3px 15px rgba(0,0,0,0.4);
}
@keyframes fadeIn {
  0% { opacity: 0; }
  100% { opacity: 1; }
}

/* Modal overlay */
.hidden-caption-content {
	display:none;
}
.pswp__iiif-caption {
	background: rgba(232, 230, 230, 0.75);
	font-size: 1em;	
	max-width: calc(100% - 5rem);
	padding: .6em 1em;
	border-radius: 2px;
	position: absolute;
	bottom: 2rem;
	left: 2rem;
	width: auto;
	/*
	width: 100%;
	left: 50%;
	transform: translateX(-50%);
	*/
	box-shadow: 0px 3px 15px rgba(0,0,0,0.2);
	max-height: 10rem;
	overflow-y: auto;
}

</style>
