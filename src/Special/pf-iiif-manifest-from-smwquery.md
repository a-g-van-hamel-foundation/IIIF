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

That was still a relatively straightforward example but what if you want to query for image files on the basis of semantic data from other pages that are semantically linked to those image files? Semantic MediaWiki already solves that for you by allowing you to use [inverse property relationships](semantic-mediawiki.org/wiki/Help:Inverse_properties) and if necessary, [subqueries](https://www.semantic-mediawiki.org/wiki/Help:Subqueries_and_property_chains).

Suppose that you have pages about Strisores, which are a clade of nocturnal or crepuscular birds that include hummingbirds and nightjars; and to query for all those pages your `#ask` query would use `[[Class::animals]] [[Has clade::Strisores]]`. And suppose those pages are semantically linked to image files using a property of data type 'Page' called "Has image file". What then does an appropriate query look like that can fetch all those file pages? In both `#ask` and `#iiif-manifest-from-smwquery`, you would invert the directional order of the property "Has image file", which lets us request file pages rather than the pages linked to them, and because the relationship is now inverted, add a subquery for pages about Strisores as the value of the condition :

```
[[-Has image file::<q>[[Class::animals]] [[Has clade::Strisores]]</q>]]
```

Of course, if you are requesting the image file or files for a single page, you do not need a subquery. If that page were called "Mousebird", this should do:

```
[[-Has image file::Mousebird]]
```
