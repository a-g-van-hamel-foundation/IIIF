Through two API modules, `iiif-manifest` and `iiif-annotations`, this extension lets you try out a proof of concept: fetch Semantic MediaWiki data linked to IIIF Canvases in a Manifest, convert the data to IIIF Annotations (Open or Web Annotations in Presentation API v2 or v3) and merge what is called ‘referenced Annotations’ into the manifest. In this way, you can show your SMW data alongside the relevant Canvas in a IIIF image viewer.

Development began with a use case on [CODECS][codecs]. Its many descriptions of the contents of manuscripts often consist of various items on texts (and paratexts). Each of these items has its property values stored in a subobject. But now that more and more IIIF resources are appearing on the web, is there any way we can interlink them with our SMW data so we can create a more connected user experience? From there the idea emerged that each item, each subobject, could point to a Canvas that contains the beginning of the text; the subobject could then form the basis for an annotation.

An example of SMW data being used as referenced Annotations and loaded into Mirador:

<p><img src="{urlBase}/extensions/IIIF/src/Special/ISOS-RIA-Dii1-annotations.png" alt="example" style="width:100%;"/></p>

## How it works

### Data structure
- It is assumed that for every such item you want to include, a [subobject](https://www.semantic-mediawiki.org/wiki/Help:Adding_subobjects) is used to record it. The subobject should include 
    - a property that records the Canvas's id - see `IIIFAnnotTargetProp` below. The Canvas's id is an identifier or URI assigned to a Canvas within a IIIF Manifest. This allows the canvas to be uniquely referenced.
	- a property that links back to the parent page - see the query condition in `IIIFv3QueryArg` below.
	- a property containing the text you want to show as your Annotation - see `IIIFAnnotTextProp` below.
	- optionally, a property for sorting items - see  `IIIFAnnotSortProp`. This may be helpful if you need to display Annotations in a predefined order of appearance.

### Configuration
Again, just as we saw in the context of creating Collections, you can set up 
- a single, site-wide configuration in your local settings.
- multiple configurations by creating schemas in the IIIF namespace.

Without the `smwconfig` parameter, the `/api.php?action=iiif-manifest&format=2&formatversion=2` ...

| Local settings  | JSON `"config"` > | Description |
| ------------- | ------------- | ------------- |
| `$wgIIIFv3QueryArg` | `IIIFv3QueryArg` > `value` | The query condition to be used for the IIIF manifest API v3, which queries for all annotations. Use an ampersand as placeholder for the resource page. |
| `$wgIIIFv2QueryArg` | `IIIFv2QueryArg` > `value` | The query condition to be used for the IIIF manifest API v2, which queries for annotations for the canvas only. Use an ampersand as placeholder for the canvas ID |
| `$wgIIIFAnnotTextProp` | `IIIFAnnotTextProp` > `value` | The property used to provide the text of the annotation. |
| `$wgIIIFAnnotTargetProp` | `IIIFAnnotTargetProp` > `value` | The property that links annotations to the canvas URI (corresponding to 'target' in v3, 'on' in v2). |
| `$wgIIIFAnnotSortProp` | `IIIFAnnotSortProp` > `value` | The property or properties used to sort multiple annotations. Use a comma to separate multiple properties. |
| `$wgIIIFAnnotDoParse` | `IIIFAnnotDoParse`  > `value` | Boolean. Whether or not annotations should be parsed as wikitext (true by default). |

### API modules
#### Redirect
If necessary, a redirect to the API can be used in the following format:

`Special:IIIFServ/manifest/smwconfig/{smwconfig}/{obj}/{manifest URL}`

e.g. `Special:IIIFServ/manifest/smwconfig/69168/1347/https://www.isos.dias.ie/static/manifests/RIA_MS_D_ii_1.json`

### Example

```
{{#subobject:
|Class=CanvasItem
|Has target URI=https://iiif.io/api/cookbook/recipe/0033-choice/canvas/p1
|For page={{FULLPAGENAME}}
|Has resource description=John Dee receives Queen Elizabeth I at Mortlake to demonstrate an experiment.
}}
```

```
{
	"config": {
		"IIIFv3QueryArg": {
			"value": "[[Class::CanvasItem]] [[For page::@]] [[Has target URI::+]]"
		},
		"IIIFv2QueryArg": {
			"value": "[[Class::CanvasItem]] [[Has target URI::@]]"	
		},
		"IIIFAnnotTargetProp": {
			"value": "Has target URI"
		},
		"IIIFAnnotTextProp": {
			"value": "Has resource description"
		},
		"IIIFAnnotDoParse": {
			"value": true
		}
	}
}
```

## Alternative: Ranges
An alternative that will be explored in the future is IIIF’s Ranges, which is the preferred data model if you want to enable a table of contents. Ranges can encompass multiple Canvases as well as regions of Canvases. It is not necessarily a replacement: while it allows for more precision when locating a text in a resource, it also requires more effort on behalf of the user providing the data and perhaps additional tools.

## Technical implementation

### API modules
Two API modules are used to convert and merge data.
- `iiif-annotations` transforms 'semantic annotations' (Semantic MediaWiki) to either Open or Web Annotations in JSON-LD, depending on the version of the IIIF Presentation API being used. It returns output that can be referenced by `iiif-manifest`.
- `iiif-manifest` merges references to the first API into the Manifest and returns the modified Manifest. The resulting Manifest can then be imported into a IIIF viewer like Mirador, as shown above.

### Embedded vs referenced annotations
Broadly speaking, IIIF allows for two ways of integrating Annotations in JSON-LD: embedded, meaning that annotations are directly nested in the IIIF Manifest, and referenced, meaning that annotations are stored separately and referenced from within the Manifest (see https://iiif.io/api/cookbook/recipe/0269-embedded-or-referenced-annotations). This extension has opted for the latter model and automatically transforms the original Manifest by merging in references to relevant Annotations.

### Differences between versions 2 and 3 of the IIIF Presentation API
There are significant differences between versions 2 and 3 that have implications for the implementation of Annotations in our two API modules:

- IIIF Presentation API v2 adopts the Open Annotations model (https://www.w3.org/community/openannotation). To reference Annotations, each Canvas that has any annotations must include a reference to an `sc:AnnotationList` (under `otherContent`).
- IIIF Presentation API v3 adopts the Web Annotations model (https://www.w3.org/TR/annotation-model/) instead. To reference Annotations, the Manifest lists references to instances of `AnnotationPage` (the 'successor' to `sc:AnnotationList`) in a separate section called `annotations`. An(other) important difference here is that the referenced `AnnotationPage` can include annotations about multiple Canvases.

[codecs]: https://codecs.vanhamel.nl
