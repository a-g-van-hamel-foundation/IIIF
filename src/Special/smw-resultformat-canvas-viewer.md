The result format `iiif-canvas-viewer` lets you use a deep-zoom image viewer, one canvas at a time, to show the result of a Semantic MediaWiki query on [subobjects representing Annotations]({urlBase}/Special:IIIF/pf-iiif-annotator-data). It uses the same image/annotation viewer, based on OpenSeadragon and Annotorious, that is used for the [annotation tool]({urlBase}/Special:IIIF/pf-iiif-annotator).

Unlike the annotation tool, which focuses on a single existing resource, it generates a new one from the Canvases and Annotations that are returned by the query. It may gather data from multiple Manifests to draw everything together. 

## Required properties
A number of printout properties are required to provide the necessary data. In order for those properties to get recognised by the data model, they should be (re-)labelled with the predefined names given below:

- `manifest` - the Manifest URL is required primarily because we need to fetch data relating to Canvas items and reuse them in our new data resource.
- `canvasid` - the Canvas identifier (`id`/`@id`)
- `xywh` - the xywh values of the image region
- `tilesource` - tile source identifier (without `/info.json`)
- `profileid` - required unless 'template' is used. Refers to the page ID of the profile that was used to configure the [annotation form]({urlBase}/Special:IIIF/iiif-annotator-form). See below.

## Formatting annotations
The sidebar on the right is where data associated with your Annotation are presented. There are two ways in which you can use semantic properties to create appropriate content.

### Option 1: use a wiki template
A wiki template offers a lot of flexibility and customisability.

### Option 2: use the profile/schema
In addition to configuring forms, the [form profile]({urlBase}/Special:IIIF/iiif-annotator-form) can also serve as a reference model for presenting the data submitted. In fact, this is what the annotation tool already relies on when it goes into 'view only mode'.

To adopt the same approach here is more difficult because (a) not every annotation returned by the query was created using the same form profile, and (b) because there is no easy to trace connection between a form profile and the semantic properties that end up recording the data. That said, there are some steps you can take to achieve the same thing.

- (a) Use the profile id:
    - For each annotation, store the profile's ID in a semantic property, as already explained
    - Add this property to the printout properties of the query and give it the label `profileid`.
- (b) Each printout property of the dataset must be given a label identical to the input name of the form profile. See the example below.

The main advantages are:
- The presentation is consistent with the annotation tool in 'view-only mode'.
- Automatically supports use of the Reconciliation and Wikibase APIs, which may not be so easily achieved through a wiki template.
- No wiki template needed

## Example
<pre>{{#ask: ...

// Mandatory
|?Has IIIF manifest=manifest
|?Has canvas ID=canvasid
|?Has XYWH region=xywh
|?Has tile source ID=tilesource

// Option 1: use a profileid
|?Has annotator form config ID=profileid
// If your profile has 'wikidata' as the name of an input, relabel your property accordingly
|?Depicts Wikidata item=wikidata

// Option 2:
|template=Format annotation

// Not used?
|?Has canvas label=canvaslabel
|?Has canvas index=canvasindex

}}
</pre>
