# IIIF extension for MediaWiki

The IIIF extension is an extension to MediaWiki which offers a set of API services and associated tools for working with [IIIF (International Image Interoperability Framework)](https://iiif.io/), such as integrating annotations, creating collections and using MediaWiki image files. Some of these features are based on, or may benefit from, the use of [Semantic MediaWiki](https://https://www.semantic-mediawiki.org). In addition, v0.3 experimentally introduces a new tool that lets you annotate IIIF resources in the wiki.

## Installation
- Download and add the `IIIF` folder to the `extensions` folder.
- Add the following to your local settings file:
`wfLoadExtension( 'IIIF' );`
- Check Special:Version to verify that the extension has been installed.
- Done.

## Configuration and usage
Once the extension has been installed, a detailed guide to configuration and usage can be found on the special page `Special:IIIF`.

### Recommended extensions
- The [Mirador extension](https://github.com/a-g-van-hamel-foundation/Mirador) lets you use the Mirador image viewer along with several plugins.
- Several features are based on, or may benefit from, the use of [Semantic MediaWiki](https://www.semantic-mediawiki.org).
- Editing JSON in the wiki is easier with [CodeEditor](https://www.mediawiki.org/wiki/Extension:CodeEditor).
- In relation to the annotation tool specifically:
    - If you need to store annotations in content slots, make sure [WSSlots](https://github.com/Open-CSP/WSSlots) is installed and configured.
    - In order for you to offer autocompletion in lookup fields, it is recommended that you install and use the [ReconciliationAPI extension](https://github.com/a-g-van-hamel-foundation/ReconciliationAPI).

## Links
- https://codecs.vanhamel.nl/Show:Lab/IIIF (not updated in a while)

## Changelog
- 1.1.1 (June 2026). Minor release to improve compatibility. Removed hard dependency on SESP extension. Repaired broken links in documentation. Added config option `$wgIIIFAPIValueSep` (default: semi-colon) to allow admin to set an alternative value separator for file names in the `iiif-mw-pres` API module should the server somehow treat a semi-colon as a URL parameter delimiter.
- 1.1 (June 2026). Added `$wgIIIFForeignFileRepos`: given that Wikimedia Commons and other wikis no longer honour requests for thumbnails of non-canonical sizes, you can set an array of available widths for each remote wiki - see the Special page documentation. Enabled wiki-internal access to local manifests if the wiki is private. Replaced mouse events with pointer events in resizable windows and made panels in annotation tool manually resizable. Changed semantic format results to allow for dynamic content updates in third-party usage (the immediate goal was to make them re-usable in the new faceted search feature of the ReconciliationAPI extension). AceEditor initialisation. Used `$wgExtensionAssetsPath` config variable to get the location of the extension's folder. Improved how fully qualified page URLs are built, adding broader support across different wiki configurations. Improved method for HTTP requests and retracted custom user agent. Updated documentation. Styling changes.
- 1.0 (April 2026). Minimum version is now MW 1.43. Introduced the 'TOC creator', which lets you create a table of contents for a IIIF resource or create one in standalone mode if no such resource is available; like the annotator tool, it uses your own form definitions, on top of a couple of fixed ones, and stores everything in JSON; the Tify viewer can be used and placed as the left panel of a double-window interface, with the editor on the right (panels are resizable). Added `iiif-range` API for converting data edited with the TOC creator into IIIF's Ranges (v2 or v3) and for producing a new manifest with Ranges merged in. Added several new features to the Lookup field: made 'tokens' sortable, made it accept a predefined array of selectable options as either single values or 'value'/'label' objects (in addition to remote data), added a variable option to the form definition so that it can fetch the required API URL from the parser function (`#iiif-annotator`, `#iiif-toc`), enabled keydown to get initial menu of suggestions (except for Wikibase APIs since they require a substring), made the display of a selected value - in brackets, after the label - an opt-in feature, accommodated custom styling through 'class' and 'wrapperClass'. `iiif-mw-pres` API module can now accept a base64-encoded semantic query on local MediaWiki files to produce a IIIF manifest; to generate the appropriate URL, use the parser function `#iiif-manifest-from-smwquery`. Switched to HookHandler interface. Updated documentation. Minor fixes.
- 0.9 (November 2025). CSS fix for TIFY. Switched to calling Vuex on instance and removed `configureCompat`. Fixed type declarations. Removed wgServerName in favour of wgServer. Fixed incompatible data types in API.
- 0.8 (September 2025). Further HTTP request fixes. Two further options for TIFY viewer, now with documentation.
- 0.7 (September 2025). Replaced direct use of cURL with MW's Guzzle-based HttpRequestFactory and fixed redirect issue.
- 0.6 (September 2025). Added parser function (`#iiif-tify`, or just `#tify`) for using the TIFY viewer (https://tify.rocks) with a bare-bones configuration. Changed external API requests to use cURL. Minor fixes.
- 0.5 (July 2025). Created two new SMW result formats (`iiif-canvas-viewer` and `iiif-annotation-gallery`) for visualising queries on IIIF Annotations. Changes to `#iiif-annotator`: created presentation-only mode (just add `mode=viewer` to the parser function); added 'timemodified' and 'profileid' as template parameters; added change tag for the annotator; added resource info from the manifest. Improved support for different size notations (e.g. pct:) in Image Information Requests. Updated documentation available from Special:IIIF. Some reorganisation and minor fixes.
- 0.4 (June 2025). Minor release. Changes to `#iiif-annotator-data`: added fix to reflect (previously revised) W3C-compliant 'dataset' structure, added 'sep' to let user customise separator of multi-valued data, added 'annotationid' and 'timecreated' template parameters. Fixed creator object in annotation tool. Updated documentation. Css fixes.
- 0.3 (June 2025). Created documentation to be consulted on Special:IIIF. Introduced annotation tool (experimental). Extended support for Presentation API v3. Revised fetching thumbnail URLs from external wikis since the imageinfo API has become insufficient (breaking change in T360589). Overhauled the organisation of PHP files and namespaced PHP classes. Added Special page aliases.
- 0.2 (September 2024). Released (public).
