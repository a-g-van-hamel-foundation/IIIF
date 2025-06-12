<template>
<div
	ref="textareawrapper"
	class="anno-grow-wrap"
>
	<textarea
		ref="textarea"
		:name="name"
		:placeholder="placeholder"
		class="anno-textarea"
		v-model="content"
		@input="handleInput"
	></textarea>
</div>
</template>

<script>
const { defineComponent, ref, computed } = require( "vue" );

module.exports = defineComponent( {
	name: "FieldTextArea",
	props: {
		name: { type: String, default: "" },
		label: { type: String, default: "" },
		ariaLabel: { type: String, default: "" },
		//value: { type: String, default: "" },
		inputValue: { type: String, default: "" },
		placeholder: { type: String, default: "" },
		autogrow: { type: Boolean, default: false },
		class: { type: String, default: "" }
	},
	methods: {
		handleInput(e) {
			this.$refs.textareawrapper.dataset.replicatedValue = e.target.value;
			// this.$refs.textarea.value = same as = e.target.value 
			var content = e.target.value;
			this.$emit('emit-update-value', e.target.value );
			//this.$emit('update', e.target.value );
		}
	},
	setup(props) {
		const content = ref( props.inputValue );
		/*
		const textareaClasses = computed( () => ( {
			is-autogrow: props.autogrow
		} ) );
		*/
		return {
			content
		}
	}
});
</script>

<style>
.anno-textarea:hover {
	border-color: #72777d;
}
.anno-textarea:focus {
	border-color:#36c;
	box-shadow: inset 0 0 0 1px #36c;
	outline:1px solid transparent;
}

.anno-grow-wrap {
	display: grid;
}
.anno-grow-wrap::after {
	white-space: pre-wrap;
	content: attr(data-replicated-value) " ";
	visibility: hidden;
}
.anno-grow-wrap > textarea {
	background-color: #fff;
	color: #202122;
	border-color: #a2a9b1;
	box-shadow: inset 0 0 0 1px transparent;
	transition-property: background-color,color,border-color,box-shadow;
	transition-duration: .25s;
}
.anno-grow-wrap > textarea {
	resize: none;
	overflow: hidden;
}
.anno-grow-wrap > textarea,
.anno-grow-wrap::after {
	display: grid;
	padding: 0.5rem;
	grid-area: 1 / 1 / 2 / 2;
}

</style>