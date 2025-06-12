Once you have stored your annotation data on a JSON page, you may want to make further use of them, for instance to present them on a wiki page or store them through Semantic MediaWiki's [`#subobject` parser function](https://www.semantic-mediawiki.org/wiki/Subobject). You can do so using the <code>#iiif-annotator-data</code> parser function. It lets you assign data to a wiki template.

See also [`#iiif-annotator`]({urlBase}/Special:IIIF/pf-iiif-annotator)

## Parameters

<pre>
{{#iiif-annotator-data:
|manifest=
|page=
|slot=
|profile=
|data=
|targettemplate=
}}
</pre>

- **manifest** - manifest URL
- **page** - the wiki page to which the annotations have been saved
- **slot** - if the [WSSlots extension](https://github.com/Open-CSP/WSSlots) is installed, the [content slot](https://www.mediawiki.org/wiki/Manual:Slot) of the wiki page to which annotations have been saved. Defaults to `main`.
- **profile** - 
- **data** - optionally, ...
- **targettemplate** - name of the wiki template that should receive your data

On top of the data you’ve included in your 'dataset' for each annotation, the following are sent 

* ? annotation id - not included yet
* **index** - annotation index
* **manifest** - manifest URL
* **canvasid** - canvas id
* **canvaslabel** - canvas label
* **canvasindex** - canvas index
* **tilesource** - the tileSource identifier (without `/info.json`)
* **xywh** - shape's region in xywh coordinates (comma-separated)
* **creator** - wiki name of the user who created the annotation
* ? updater (Annotorious has updatedBy; not sure about W3C format)
