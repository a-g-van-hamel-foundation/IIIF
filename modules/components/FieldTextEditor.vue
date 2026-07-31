<template>
	<div class="text-editor-field">
		<textarea
			style="display:none"
			:name="name"
			v-model="storableContent"
		></textarea>
		<quill-editor
			ref="quillEditor"
			theme="snow"
			v-model:content="content"
			content-type="html"
			@update:content="handleUpdatedContent"
			:toolbar="['bold', 'italic', 'underline']"
		></quill-editor>
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require("vue");
const { QuillEditor } = require("ext.iiif.lib.quill");

module.exports = defineComponent({
	name: "FieldTextEditor",
	components: {
		QuillEditor,
	},
	props: {
		name: { type: String, default: "" },
		label: { type: String, default: "" },
		ariaLabel: { type: String, default: "" },
		inputValue: { type: String, default: "" },
	},
	emits: ['update:inputValue'],
	setup(props, { emit } ) {
		const content = ref(props.inputValue);
		const storableContent = computed({
			get() { return content.value; },
			// For a non-FormData approach like TOC
			set(val) { emit('update:inputValue', val); }
		});

		function handleUpdatedContent(str) {
			content.value = sanitiseContent(str);
			if (content.value === "<p><br></p>") {
				storableContent.value = "";
			} else {
				storableContent.value = content.value;
			}
		}

		// https://gomakethings.com/how-to-sanitize-html-strings-with-vanilla-js-to-reduce-your-risk-of-xss-attacks/
		function sanitiseContent(str) {
			// convert string to html obj
			let parser = new DOMParser();
			let doc = parser.parseFromString(str, "text/html");
			let html = doc.body || document.createElement("body");
			//
			removeTags(html);
			return html.innerHTML;
		}

		function removeTags(html) {
			// Get content only
			let tags = html.querySelectorAll("html, body, a, h1, h2, h3, h4");
			for (let t of tags) {
				t.replaceWith(t.innerHTML);
			}
			// Remove entirely
			let removableTags = html.querySelectorAll(
				"img, script, audio, video, embed, frame, input"
			);
			for (let r of removableTags) {
				r.remove();
			}
		}

		return {
			content,
			storableContent,
			// methods
			handleUpdatedContent,
			sanitiseContent,
		};
	},
});
</script>

<style lang="less">
ul.cdx-menu {
	margin-left: 0 !important;
}

.ql-container {
	font-family: inherit;
}

.ql-toolbar.ql-snow {
	font-family: inherit;
	padding: 4px 8px;
}

.ql-editor {
	border-color: #a2a9b1;
	box-shadow: inset 0 0 0 2px transparent;
	transition-property: background-color, color, border-color, box-shadow;
	transition-duration: 0.25s;
	// Match Codex padding for text inputs
	padding: 8px;
	&:hover {
		border-color: #72777d;
	}
	&:focus {
		border-color: #36c;
		box-shadow: inset 0 0 0 2px #36c;
		outline: 1px solid transparent;
	}
	ol, ul {
		padding-left: 0;
		margin-bottom: 0.5rem !important;
		li:not(.ql-direction-rtl) {
			padding-left: 0.5em;
		}
		ul > li::before {
			content: none;
		}
	}
}
</style>
