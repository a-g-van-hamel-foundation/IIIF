# IIIF extension for MediaWiki

The IIIF extension is an extension to MediaWiki which offers a set of API services and associated tools for working with [IIIF (International Image Interoperability Framework)](https://iiif.io/), such as integrating annotations, creating collections and using MediaWiki image files. Some of these features are based on, or may benefit from, the use of [Semantic MediaWiki](https://https://www.semantic-mediawiki.org). In addition, v0.2 experimentally introduces a new tool that lets you annotate IIIF resources in the wiki.

## Installation
- Download and add the `IIIF` folder to the `extensions` folder.
- Add the following to your local settings file:
`wfLoadExtension( 'IIIF' );`
- Check Special:Version to verify that the extension has been installed.
- Done.

## Configuration and usage
Once the extension has been installed, a detailed guide to configuration and usage can be found on the special page `Special:IIIF`.

### Recommended extensions
- The [Mirador extension](https://github.com/a-g-van-hamel-foundation/Mirador) lets you use the popular Mirador image viewer along with several plugins.
- Several features are based on, or may benefit from, the use of [Semantic MediaWiki](https://www.semantic-mediawiki.org).
- Editing JSON code in the wiki is easier with [CodeEditor](https://www.mediawiki.org/wiki/Extension:CodeEditor).
- In relation to the annotation tool specifically:
    - If you need to store annotations in content slots, make sure [WSSlots](https://github.com/Open-CSP/WSSlots) is installed and configured.
    - In order for you to offer autocompletion in lookup fields, it is recommended that you install and use the [ReconciliationAPI extension](https://github.com/a-g-van-hamel-foundation/ReconciliationAPI).

## Links
- https://codecs.vanhamel.nl/Show:Lab/IIIF

## Changelog
- 0.3 (in progress). Created documentation to be consulted on Special:IIIF. Introduced annotation tool (experimental). Extended support for Presentation API v3. Revised fetching thumbnail URLs from external wikis since the imageinfo API has become insufficient (breaking change in T360589). Overhauled the organisation of PHP files and namespaced PHP classes. Added Special page aliases.
- 0.2. Released (public).
