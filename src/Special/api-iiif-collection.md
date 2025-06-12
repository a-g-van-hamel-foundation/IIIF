Through the `iiif-collection` API module, you can create a [IIIF Collection](https://iiif.io/api/presentation/3.0/#51-collection), i.e. a collection of IIIF manifests, on the basis of a Semantic MediaWiki query. It returns a Collection based on a configuration containing all the data needed to build an SMW query, which can be set in LocalSettings or in a wiki page referenced by the `smwconfig` URL parameter.

[See the usage guide]({urlBase}/Special:IIIF/case-collections)

P. S. Currently, the extension still supports an older method which allows for a direct SMW query (with `smwquery`) or an SMW concept (`concept`) and which retrieves the choice of printout properties from the local settings. This method may be removed in the future.

## Parameters
[Documentation API]({urlBase}/api.php?action=help&modules=iiif-collection)

## Redirect service
Formats:
- `Special:IIIFServ/collection/smwconfig/{configid}`
- `Special:IIIFServ/collection/concept/{concept}`

## References
- https://iiif.io/api/cookbook/recipe/0032-collection/
