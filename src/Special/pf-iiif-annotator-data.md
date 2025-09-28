So you managed to store your annotation data on a JSON page, or a JSON content slot. Congratulatons! Now you want to maximise good use of your data, for instance by presenting your work on a wiki page in an appropriate format; or by storing everything using Semantic MediaWiki's [`#subobject` parser function](https://www.semantic-mediawiki.org/wiki/Subobject), allowing for more advanced queries across multiple annotated resources. 

You can do so using the <code>#iiif-annotator-data</code> parser function, which lets you send data to a wiki template.

See also [`#iiif-annotator`]({urlBase}/Special:IIIF/pf-iiif-annotator)

## Parser function parameters

<pre>
{{#iiif-annotator-data:
|manifest=
|page=
|slot=
|targettemplate=
|data=
|sep=
|profileid=
}}
</pre>
<!-- Not documented: datamodel, 'W3C' (default) or 'Annotorious' (legacy, to be depreciated) -->

- **manifest** - Manifest URL
- **page** - the wiki page to which your annotation data have been saved
- **slot** - if the [WSSlots extension](https://github.com/Open-CSP/WSSlots) is installed, the [content slot](https://www.mediawiki.org/wiki/Manual:Slot) of the wiki page to which annotations have been saved. Defaults to `main`.
- **targettemplate** - name of the wiki template that should receive your data. If no template is provided, a JSON representation will be returned instead.
- **data** - optionally, a comma-separated list of mappings between the source variable name and the template parameter, using an equals sign to delimit each mapping. For instance, if you have a value for 'descr' in your annotation data and it needs to be assigned to your template as 'Description', write `descr=Description`. Defaults to 'all', meaning that all source variables will be transferred verbatim.
- **sep** - optionally, a custom separator for multi-valued data. See below.
- **profileid** - the page ID of the form profile that was used to create the form
- **userparam<...>** - any custom parameter you would like to forward to each instance of the template, as long as the parameter name starts with `userparam`. You can add as many such parameters as you need.

## Template parameters
Each set of annotation data consists of:
- the dataset that has been submitted through the form in the annotation tool
    - For multi-valued data, the default separator is a semi-colon (`;`). If that does not fit your needs, for instance if your values may include semi-colons, you can set `sep` to your preferred string.
- additional data. The result formats [`iiif-canvas-viewer`]({$urlBase}/Special:IIIF/iiif-canvas-viewer) and [`iiif-annotation-gallery`]({$urlBase}/Special:IIIF/iiif-annotation-gallery) require some of these to be recorded in semantic properties.
    - **manifest** - Manifest URL. Required for `iiif-canvas-viewer`.
    - **annotationid** - Annotation id
    - **index** - Annotation index based on the Canvas index and in the absence of a universally satisfying rule, the y-coordinates of the annotation shape
    - **canvasid** - Canvas id corresponding to id or @id in the Manifest. Required for `iiif-canvas-viewer` and generally recommended.
    - **canvaslabel** - Canvas label. Recommended.
    - **canvasindex** - Canvas index
    - **tilesource** - the `tileSource` identifier (without `/info.json`). Required for `iiif-canvas-viewer` and `iiif-annotation-gallery`.
    - **xywh** - shape's region in xywh fragment coordinates (comma-separated). Note that some image servers may not accept decimals in coordinates. If that is the case, you will have to round the numbers in order to be able to fetch an image crop from the server. Required for `iiif-canvas-viewer` and `iiif-annotation-gallery`.
    - **creator** - wiki name of the user (without `User:` prefix) who created the annotation
    - **timecreated** - date and time the annotation was created
    - **profileid** - available if provided to the parser function (see `profileid` above). This may be needed when you present the result of a semantic query with the result format `iiif-canvas-viewer`.
- **userparam<...>** - see above
