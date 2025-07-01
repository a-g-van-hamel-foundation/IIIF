The extension comes with a suite of tools that can be used to annotate IIIF resources, display Annotations, and store and visualise data with Semantic MediaWiki. Annotation, in this context, refers to the ability for a user to do two things:

1. identify a place of interest by drawing a rectangular 'region' as an overlay on the image
2. with the aid of a form in the sidebar, associate that region with whatever data one happens to need

## How to set up the tools

- [A parser function `#iiif-annotator`]({urlBase}/Special:IIIF/pf-iiif-annotator) lets you enable an image annotation tool on a wiki page. Users can then create Annotations, which are stored as JSON in a wiki page. In 'view only' mode, the same parser function can be used to exhibit the IIIF resource in the viewer and the Annotations that were created for it.
    - [JSON schemas]({urlBase}/Special:IIIF/iiif-annotator-form) or '(form) profiles' let you create forms for use with the annotator and set up models for displaying the data. Without a JSON schema, the tool defaults to using a textarea with 'description' as the name of the input.
- [A parser function `#iiif-annotator-data`]({urlBase}/Special:IIIF/pf-iiif-annotator-data) lets you reuse Annotation data by sending them to a wiki template. This can be useful, for instance, if you want to store Annotations through Semantic MediaWiki's `#subobject` format.
- Two result formats for Semantic MediaWiki are available for visualising the result of a query:
    - [`iiif-canvas-viewer`]({urlBase}/Special:IIIF/iiif-canvas-viewer) - uses the OpenSeadragon/Annotorious image viewer to show annotated canvases.
    - [`iiif-annotation-gallery`]({urlBase}/Special:IIIF/iiif-annotation-gallery) - creates a gallery of image crops matching the annotated regions. A lightbox-type viewer is used to reveal larger images and details.

