For some API modules, you can use an alternative link using a special page (`Special:IIIFServ/...`) that redirects the web request to the relevant API module. Why? The alternative link is usually shorter and should be easier to include in a wiki link. More importantly, a path-based URI using slash separators, as opposed to a query parameter string, fits better with current practices in the IIIF community.

## Modules

### iiif-manifest
If necessary, a redirect to the `iiif-manifest` API module can be written in the following format:

`Special:IIIFServ/manifest/smwconfig/{smwconfig}/{obj}/{manifest URL}`

For example, `Special:IIIFServ/manifest/smwconfig/69168/1347/https://www.isos.dias.ie/static/manifests/RIA_MS_D_ii_1.json` redirects to `api.php?action=iiif-manifest&smwconfig=69168&obj=1347&format=json&formatversion=2&manifest=https://www.isos.dias.ie/static/manifests/RIA_MS_D_ii_1.json`

### iiif-mw-pres
Formats:
- `Special:IIIFServ/presentation/manifest/{source}/pageids/{pageids}`
- `Special:IIIFServ/presentation/manifest/{source}/files/{files}`
<!-- presentation/canvas, etc. probably need not be documented -->

Using Presentation API v3:
- `Special:IIIFServ/presentation3/manifest/{source}/pageids/{pageids}`
- `Special:IIIFServ/presentation3/manifest/{source}/files/{files}`

### iiif-mw-img
- `Special:IIIFServ/image/{source}/{pageid}/info.json` (see below for other uses of `Special:IIIFServ/image/...`)

### iiif-collection
Formats:
- `Special:IIIFServ/collection/concept/{concept}`
- `Special:IIIFServ/collection/smwconfig/{configid}`

### iiif-wiki
Format:
- `Special:IIIFServ/wiki/{pageid}`

### Other
- `Special:IIIFServ/image/{source}/{pageid}/...` facilitates two different redirects:
    - `Special:IIIFServ/image/{source}/{pageid}/info.json` redirects to the `iiif-mw-img` API as given above.
    - `Special:IIIFServ/image/{source}/{pageid}/{region}/{size}/{rotation}/{quality}.{format}`, segmented according to IIIF's [Image Request URI](https://iiif.io/api/image/3.0/), redirects to MediaWiki's thumbnail URL. MediaWiki has no built-in support for `{region}` or `{rotation}`.
