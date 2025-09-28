TIFY (https://tify.rocks) is a light-weight IIIF document viewer developed and made available by the Göttingen State and University Library. While relatively lean compared to a more heavy-handed viewer like Mirador, it does cover many of the essentials. Using the parser function <code>#iiif-tify</code> (or <code>#tify</code>) you can use it in your wiki to present IIIF resources. Configuration options are currently limited but more may be added in the future.

## Parameters

```
{{#iiif-tify:
|manifest=
|canvasindex=
|annotationsvisible=
|setview=
}}
```

- **manifest** (required) - IIIF Manifest URL
- **canvasindex** - index (integer) of the canvas to be displayed when the viewer is loaded. Allows for a comma-separated list of canvas indexes.
- **annotationsvisible** - whether Annotations, if any, should be shown: true (default) or false. 
- **setview** - By default, the (initial) active view type is set to media, but alternative ones are available: `thumbnails`, `info`, `help`, `export`, `toc` (if available) and `text` (if available). All of these views are already available as collapsible side panels.
