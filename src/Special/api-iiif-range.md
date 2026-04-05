The `iiif-range` API module is used to transform data created with the TOC creator (`#iiif-toc`) to IIIF's Ranges and return a new Manifest with those Ranges merged in. It supports Presentation API v2 ([2.0](https://iiif.io/api/presentation/2.0/#ranges) to be precise as 2.1 lacks broader support among image viewers) as well as [v3](https://iiif.io/api/presentation/3.0/#54-range) and will automatically follow the version that is used in the original Manifest.

## Parameters
[Documentation API]({urlBase}/api.php?action=help&modules=iiif-range)

## Redirect service
Format:
- `Special:IIIFServ/manifest/mergerange/{pageid}/{slot}`, where `pageid` represents the page ID of the page containing your JSON data and `slot` the name of the relevant content slot (default: 'main'). The URL can be found in the top menu of the TOC editor by clicking the three dots ("Copy URL shorthand"). 
