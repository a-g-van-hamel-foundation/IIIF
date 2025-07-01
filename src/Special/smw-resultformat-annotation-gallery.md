The result format `iiif-annotation-gallery` lets you transform the result of a Semantic MediaWiki query on Annotations into a collection of images in a gallery grid layout. Clicking an image triggers a lightbox-type modal overlay with a caption.

For a guide on how to store Annotations as subobjects, see the documentation on the [parser function `#iiif-annotator-data`]({urlBase}/Special:IIIF/pf-iiif-annotator-data).

## Required properties
A couple of printout properties are required to provide some necessary data. In order for those properties to get recognised by the data model, they should be [(re-)labelled](https://www.semantic-mediawiki.org/wiki/Help:Displaying_information#Printing_property_values) with the predefined names given below. A query using this result format must use:
1. a property that provides the xywh fragment of the Annotation shape, using `xywh` as its printout label.
2. a property that provides the identifier for the tile source (without `/info.json`), using `tilesource` as the printout label.

## Adding captions
You can add captions to accompany each image at the bottom of the image overlay. To associate an overlay with a caption, you can do one of two things:

1. The simplest option is to provide printout properties with the following labels:
    - `resourcelabel` - identifies the resource/object. If a property of data type Page is used, either the display title (given it exists) or the page name is shown.
    - `canvaslabel` - provides the label of the Canvas from which the Annotation is taken. Can be omitted if the resource
    - `description` - optionally, a description
2. Power users may want to use the `template` parameter instead.
    - name the wiki template to be used for formatting and customising your output (cf. https://www.semantic-mediawiki.org/wiki/Help:Template_result_formats).
    - To offer context, it may be useful if you linked to the full canvas in a IIIF viewer on the site or elsewhere.

## Layout options
- `layout` - defaults to a grid in which each gallery item has the same dimensions, creating evenly sized rows and columns. Within these size constraints, images are centred and clipped off around the edges. As an alternative, choose 'columns' to create a column layout in which every image is made to fit the width of the column. The aspect ratio is preserved either way.
    <div style="display:flex;gap:1em;">
        <div style="width:50%;">Example of the default grid layout:<br><img src="{urlBase}/extensions/IIIF/src/Special/layout-default.jpg" alt="Example of the default layout" style="width:90%" /></div>
        <div style="width:50%;">Example of the 'columns' layout:<br><img src="{urlBase}/extensions/IIIF/src/Special/layout-columns.jpg" alt="Example of the 'columns' layout" style="width:90%;" /></div>
    </div>
- `columns` - number of grid columns (2, 3, 4, 5, 6, 8, or 12) on medium-size screens and up. Default: 4. The CSS classes used are responsive in such a way that the number of columns decreases below a viewport of 768px.

## Example

<pre>
{{#ask: [[Class::Annotations]] [[Comments on feature::enlarged initial]]

// Mandatory:
|?Has tile source ID=tilesource
|?Has XYWH region=xywh

// Example of captions (option 1)
|?Belongs to resource=resourcelabel
|?Has canvas label=canvaslabel
|?Has description=description

// Example of captions (option 2)
|?Belongs to resource
|?Has canvas label
|?Has description
|?Has canvas ID
|?IIIF manifest URL
|template=Format resource caption

// Layout options
|layout=columns
|columns=5

}}
</pre>

## Credits
The lightbox-type modal was created with the open-source PhotoSwipe library (https://photoswipe.com)
