To design a form to be used with the image annotator (`#iiif-annotator`) or TOC creator (`#iiif-toc`), create a wiki page in the IIIF namespace and add your configuration as explained below. You can choose from a selection of different input types. 

## Naming pages
It is recommended to come up with a consistent naming pattern for pages that store your configuration. For instance, you may want to start every page name with a phrase like `IIIF:Form/`. 

## Inputs

- **type** - set this to "FormConfig".
- **description** - optional but a description may help to remind you or others of the purpose for which the form was designed.
- **properties** - an array of configuration objects for your form inputs. Each object must have an `inputType` and `name` (unique) and except for hidden inputs, should have a `label`. Avoid names that are reserved as parameter names by the parser function `#iiif-annotation-data`, such as 'index', 'creator' and 'canvasid'. 

### Input types
The properties section is where you select and configure the inputs you want from the available options. Current options for `inputType` are:

- **"textarea"** - textarea input.
- **"text"** - text input.
- **"select"** - enables a dropdown with selectable options from a predefined list. Accepts single values only.
- **"lookup"** - enables a Select2-like dropdown, with selectable options from either an API query or a predefined list. It allows for both single and multiple values to be stored. See below.
- **"texteditor"** - a minimal text editor.
- **"xmleditor"** - a simple XML editor, which may be useful if for instance, you create a transcription directly in TEI XML.
- **"hidden"** - the hidden input is useful only in conjunction with a value for `defaultValue`.
- **"free"** - No input. Add free, unformatted text to `defaultValue`.

### Example
This fictional example, which does not have any particular scenario in mind, contains illustrations for each input type.

```
{
	"type": "FormConfig",
	"description": "Example of a config",
	"properties": [
		// textarea
		{
			"inputType": "textarea",
			"label": "Description",
			"name": "description",
			"placeholder": "Description"
		},		
		// text
		{
			"inputType": "text",
			"label": "Description",
			"name": "description",
			"placeholder": "Description"
		},
		// select
		{
			"inputType": "select",
			"label": "Fruit",
			"name": "fruit",
			"placeholder": "Select the ingredient depicted",
			"multiple": false,
			"options": [
				{
					"value": "Fruit:Dragon fruit",
					"label": "dragon fruit"
				},
				{
					"value": "Fruit:Lychee",
					"label": "lychee"
				},
				{
					"value": "Fruit:Kiwano",
					"label": "kiwano (horned melon)"
				}
			]
		},
		// lookup with apiType 'wikibase'
		{
			"inputType": "lookup",
			"label": "People",
			"name": "people",
			"placeholder": "People depicted in this selection",
			"apiType": "wikibase",
			"apiUrl": "https://www.wikidata.org/w/api.php",
			"multiple": true
		},
		// lookup with apiType 'reconciliation'
		{
			"inputType": "lookup",
			"label": "Vehicle",
			"name": "vehicle",
			"placeholder": "Identify the vehicle depicted in this selection",
			"apiType": "reconciliation",
			"apiUrl": "my-wiki.example/api.php?action=recon-suggest-entity\u0026format=json\u0026source=smw\u0026profile=9815\u0026offset=0\u0026limit=25\u0026prefix=",
			"placeholder": "",
			"multiple": true
		},
		// simple text editor
		{
			"inputType": "texteditor",
			"label": "Comments",
			"name": "comments",
			"placeholder": "Comments"
		},
		// xml editor
		{
			"inputType": "xmleditor",
			"label": "Transcription",
			"name": "transcription",
			"placeholder": "Transcription"
		}
		// hidden
		{
			"inputType": "hidden",
			"name": "category",
			"defaultValue": "testing",
		}
	]
}
```

### Styling
Two additional parameters let you change the default classes
- `wrapperClass` applies to the container element, which includes the label and input elements.
- `class` currently applies only to a text input's class attribute.

### input: `lookup` with the API
The `lookup` input can be used to fetch and select data from an API using a typeahead/autocompletion field. As demonstrated in the example, it currently supports two API endpoint types which must be set in `apiType`, along with an appropriate URL to be set in `apiUrl`:

- `wikibase` : used for any site that offers the [Wikibase API](https://www.mediawiki.org/wiki/Wikibase/API). In the case of Wikidata, the URL in `apiURL` would be `'https://www.wikidata.org/w/api.php'`. 
- `reconciliation` : used for any database that offers the 'Suggest entity' service from a [Reconciliation API](https://www.w3.org/community/reports/reconciliation/CG-FINAL-specs-0.2-20230410/) (v0.2). Using the [ReconciliationAPI extension](https://www.mediawiki.org/wiki/Extension:ReconciliationAPI), a MediaWiki site can enable and configure one or multiple endpoints.

### input: `lookup` with a list of options
The 'lookup' input can also be used with a list of predefined options. Instead of adding values to `apiURL` and `apiType`, you would use the `options` parameter with either an array of single values or an array of objects with `value` and `label` keys.

Example with single values:
```
{
	"label": "Painting techniques",
	"name": "techniques",
	"inputType": "lookup",
	"multiple": true,
	"options": [ "blending", "glazing", "dry brushing", "impasto", "sfumato" ]
}
```

### input: `lookup` with variables (Reconciliation API/`#iiif-toc` only)

Form definitions are agnostic of the context in which they end up being used, but what if you need to tailor the available 'value' options to a specific subject that can change from one context to another? Say your form is applicable to logbooks, with entries written by different authors, and you want your users to be able to select from a list of relevant authors only, which is information you have recorded elsewhere. 

Follow these two steps:

1. To indicate in your form definition that we are dealing with a variable name rather than a value, prefix it with an at symbol (`@`). Example:

```json
{
	"inputType": "lookup",
	"label": "Authors",
	"name": "authors",
	"apiType": "reconciliation",
	"apiUrl": "@authors",
	"multiple": true
}
```

The use of variables is currently limited to Reconciliation APIs.

2. To make sure that the variable can be resolved, assign its value in the `#iiif-toc` parser function. The parameter name in the parser function must be identical to the variable name, which is `@authors` in our example. 

In the following example, each logbook has a wiki page that records its authors using the semantic property `Has author`. For convenience, we will use a parser function from the Reconciliation API extension that returns an appropriate API URL on the basis of a semantic query, with one template variable ('Logbook'). The API should then return the people identified as authors of the relevant logbook.

```
{{#iiif-toc:
...
|@authors={{#recon-smwquery-url:query=[[Class::people]] [[-Has author::{{{Logbook|}}}]] }}
}}
```

<!--
Not yet documented: 
- alternatives
- classes available for use with class, wrapperClass, etc.

-->