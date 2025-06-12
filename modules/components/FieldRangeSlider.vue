<template>
	<div
		ref="rangeslider"
	>
		<div class="osd-range-slider-wrapper">
			<input			
				type="range"
				:name="name"
				:min="min"
				:max="max"
				class="osd-range-slider"
				v-model="val"
			>
			<span class="indicator">{{ val }}{{ suffix }}</span>
		</div>
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require( "vue" );

module.exports = defineComponent( {
	name: "FieldRangeSlider",
	props: {
		name: { type: String, default: "" },
		initialValue: { type: String, default: "100" },
		ariaLabel: { type: String, default: "" },
		min: { type: String, default: "0" },
		max: { type: String, default: "200" },
		suffix: { type: String, default: "" }
	},
	methods: {},
	mounted() {},
	watch: {
		initialValue: function(n,o) {
			console.log( "initialValue being watched changed", n );
		},
		val: function(n,o) {
			this.$emit("emitSliderValue",n);
		}
	},
	computed: {
	},
	setup(props) {
		const val = ref( props.initialValue );

		return {
			val,
		}
	}
} );

</script>

<style>
.osd-range-slider-wrapper {
	display:flex;
	margin-bottom:0.1rem;
}
.osd-range-slider-wrapper .indicator {
	width: 5em;
	padding: 0 .5em;
	font-size: .8em;
	text-align: right;
}

.osd-range-slider {
	-webkit-appearance: none;
	width: 100%;
	height: 6px;
	margin: 6px 0;
	opacity: 0.8;
	border: 0;
	border-radius: 2px;
	-webkit-transition: .2s;
	transition: opacity .2s;
	background:rgba(0,0,0,.2);
	outline: none;
}
.osd-range-slider:hover {
	opacity: 1;
}
.osd-range-slider::-webkit-slider-runnable-track {
	-webkit-appearance: none;
	cursor: pointer;
	background:transparent;
}
.osd-range-slider::-moz-range-track {
	cursor: pointer;
	width: 100%;
	height: 6px;
	background:rgba(0,0,0,.2);
}
.osd-range-slider::-ms-track {
	cursor: pointer;
	height: 6px;
	width: 100%;
	border: 0;
	border-radius: 2px;
	background:rgba(0,0,0,.2);
}
.osd-range-slider::-webkit-slider-thumb {
	-webkit-appearance: none;
	cursor: pointer;
	height: 24px;
	width: 15px;
	border: 0;
	border-radius: 2px;
	background: #5f7470 linear-gradient(180deg,hsla(0,0%,100%,.2),hsla(0,0%,100%,0));
}
.osd-range-slider::-moz-range-thumb {
	cursor: pointer;
	height: 24px;
	width: 15px;
	border: 0;
	border-radius: 2px;
	background: #5f7470 linear-gradient(180deg,hsla(0,0%,100%,.2),hsla(0,0%,100%,0));
}
.osd-range-slider::-ms-thumb {
	cursor: pointer;
	height: 24px;
	width: 15px;
	border: 0;
	border-radius: 2px;
	background: #5f7470 linear-gradient(180deg,hsla(0,0%,100%,.2),hsla(0,0%,100%,0));
}
</style>
