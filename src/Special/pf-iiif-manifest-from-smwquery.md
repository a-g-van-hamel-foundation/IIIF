What if you want to use a Semantic MediaWiki query to create your own collection of images from the wiki and on that basis, generate a IIIF manifest that can be fed into your preferred IIIF viewer? The `#iiif-manifest-from-smwquery` parser function can be used to accept basic query arguments and return an appropriate manifest URL (Presentation API v3). The `iiif-mw-pres` API module is responsible for interpreting and handling the query.

## Parameters

```
{{#iiif-manifest-from-smwquery: <query> // Mandatory
|labelprop=<name of smw property> // The semantic property used to canvas item's label. Optional.
|sort= // SMW's 'sort'. Optional.
|order= // SMW's 'order'. Optional.
}}
```

Example with the TIFY viewer:

```
{{#iiif-tify:
|manifest={{#iiif-manifest-from-smwquery: [[File:+]] [[Depicts::western yellow wagtail]]
|labelprop=Display title of
|sort=Display title of
|order=asc
}}
}}
```
