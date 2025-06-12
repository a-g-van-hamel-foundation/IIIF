The extension comes with an annotation tool that can be used in the wiki to let you annotate IIIF resources. Annotation, in this context, refers to the ability for a user to do two things:

1. identify a place of interest by drawing a rectangular 'region' over the image
2. with the aid of a form, associate that region with whatever data one needs

## How to set up the tool

- [A parser function `#iiif-annotator`](/Special:IIIF/pf-iiif-annotator) lets you enable the annotation tool on a wiki page.
- [JSON schemas](/Special:IIIF/iiif-annotator-form) let you create forms for use with the annotator. Without a JSON schema, the tool defaults to using a textarea with 'description' as the name of the input.
- [A parser function `#iiif-annotator-data`](/Special:IIIF/pf-iiif-annotator-data) lets you format annotations through a wiki template. This can be useful, for instance, if you want to store annotations through Semantic MediaWiki's `#subobject` format.

## Usage 

### Example
<p><img src="/extensions/IIIF/src/Special/ISOS-NLIG3-corvus.png" alt="example" style="width:100%;"/></p>

This example uses a digitised facsimile of an Irish manuscript in the National Library of Ireland, MS G 3, available from Irish Script on Screen (https://www.isos.dias.ie/NLI/NLI_MS_G_3.html).

### How to view and navigate the document
- On the top left of the toolbar you'll find a menu to help you navigate through the document.
- On the top right of the viewer, to the left of the sidebar, are some interactive buttons for drawing annotations (pencil icon), rotating the canvas, adjusting image filters and jumping into full screen mode.
- To zoom in and out, use the keyboard shortcuts SHIFT+arrow up and SHIFT+arrow down.
- The sidebar is reserved for creating and managing annotations.

### How to draw shapes
- To create a new annotation shape, a semi-transparent layer over the image canvas, first enable the drawing tool (pencil icon).
    - You can now draw a rectangle or 'region': click where you would like your rectangle to start, move your cursor to create the desired shape and click again to finish.
    - When you're ready, a form should pop up in the sidebar to let you describe and 'annotate' the current selection - see the next section.
- To move or resize an annotation shape, make sure the right one is selected and the drawing tool is disabled. Hit 'Save' in the sidebar to confirm any changes.
- To delete a rectangle - including all associated data - make sure it is selected first. If you're in the process of creating one, you can just hit the backspace key and start afresh. If you need to select another shape to remove it, make sure the drawing tool is disabled, select the target by clicking it and hit the backspace key.

### How to use the form
- For any shape that is currently selected, a form appears in the sidebar to let you associate that selection with descriptions and other data. It is up to the site admin to configure and put together a form that suit the project a user is working on. It could be a straightforward textarea or a more elaborate form consisting of different input types such as a text editor or a typehead input for fetching structured data from a site like Wikidata.
- Once the form is filled out, submit it by hitting **'Save'** in the top right of the sidebar.
- Note that currently, any unsaved changes you have made will be lost if you select another annotation shape.
- Currently, nothing is saved permanently until you hit **"Save all annotations"**. 
    - The plan is ultimately to remove this button and store data automatically whenever a new shape is drawn and whenever the form is submitted.

### Where to find annotations
All canvases with permanently saved annotations are conveniently listed under a tabbed heading called "Annotations". 

## Technical
- This tool is written in Vue 3, with some use of Wikimedia's Codex library based on what 's available in MW 1.39.
- It is based on the [OpenSeadragon](https://openseadragon.github.io/) and [Annotorious](https://annotorious.dev/) libraries. 
- The text editor relies on [Quill](https://quilljs.com/), the XML editor on [Ace](https://ace.c9.io/).
