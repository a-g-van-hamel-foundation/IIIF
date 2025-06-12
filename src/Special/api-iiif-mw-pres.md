The `iiif-mw-pres` API module implements the IIIF Presentation API (currently 2.0 only) using the MediaWiki file system. It supports image files (level 0, tileless, pyramidically sized) from the local MediaWiki repository as well as from external repositories such as Wikimedia Commons.

[See here for a usage guide]({urlBase}/Special:IIIF/case-mw-images)

## Parameters
[Documentation API]({urlBase}/api.php?action=help&modules=iiif-mw-pres)

## Redirect service
- `Special:IIIFServ/presentation/manifest/{source}/pageids/{pageids}`
- `Special:IIIFServ/presentation/manifest/{source}/files/{files}`

## Links
- The `iiif-mw-pres` API module is inspired by [Tom Crane's 2018 blog post on the topic of IIIF and Wikimedia](https://gist.github.com/tomcrane/70e879884a744ce69d329cc6a33a34ac) and two services he created, ['mediawiki-iiifproxy'](https://github.com/tomcrane/mediawiki-iiifproxy) and ['wikipedia-to-iiif'](https://github.com/tomcrane/wikipedia-to-iiif)). 
- https://commons.wikimedia.org/wiki/Commons:International_Image_Interoperability_Framework

