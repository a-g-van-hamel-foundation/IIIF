To design a form to be used with the image annotator, create a wiki page in the IIIF namespace and add your configuration as explained below. You can choose from a selection of different input types. 

## Naming pages
It is recommended to come up with a consistent naming pattern for pages that store your configuration. For instance, you may want to start every page name with a phrase like `IIIF:AnnotatorForm/`. 

## Inputs

- **type** - set this to "AnnotatorFormConfig".
- **description** - optional but a description may help to remind you or others of the purpose for which the form was designed.
- **properties** - an array of configuration objects for your form inputs. Each object must have an `inputType` and `name` (unique) and except for hidden inputs, should have a `label`. 

### Input types
The properties section is where you select and configure the inputs you want from the available options. Current options for `inputType` are:

- "textarea"
- "text"
- "select" - enables a dropdown with selectable options
- "lookup" - see below
- "texteditor" - a minimal text editor
- "xmleditor"
- "hidden" - the hidden input is useful only in conjunction with a value for `defaultValue`.

### Example
This fictional example, which does not have any particular scenario in mind, contains illustrations for each input type.

```
{
	"type": "AnnotatorFormConfig",
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
		// hidden @todo
		{
			"inputType": "hidden",
			"name": "category",
			"defaultValue": "testing",
		}
	]
}
```

### input: `lookup`
The `lookup` input can be used to fetch and select data from an API using a typeahead/autocompletion field. As demonstrated in the example, it currently supports two API endpoint types which must be set in `apiType`, along with an appropriate URL to be set in `apiUrl`:

- `wikibase` : used for any site that offers the [Wikibase API](https://www.mediawiki.org/wiki/Wikibase/API). In the case of Wikidata, the URL in `apiURL` would be `'https://www.wikidata.org/w/api.php'`. 
- `reconciliation` : used for any database that offers the 'Suggest entity' service from a [Reconciliation API](https://www.w3.org/community/reports/reconciliation/CG-FINAL-specs-0.2-20230410/) (v0.2). Using the [ReconciliationAPI extension](https://www.mediawiki.org/wiki/Extension:ReconciliationAPI), a MediaWiki site can enable and configure one or multiple endpoints.
