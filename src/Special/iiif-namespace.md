This extension automatically creates a [page namespace](https://www.mediawiki.org/wiki/Manual:Namespace) with the <code>IIIF:</code> prefix for any JSON content you might want to store in the wiki, whether for dedicated purposes aligned with this extension or other uses. Here you can save your configuration data, manifests, annotations, and whatever the case may be. 

This extension does not dictate any naming conventions, but it is recommended to be consistent when naming pages. Suggestions are given below.

## Dedicated purposes

### Schemas for `iiif-manifest` and `iiif-collection`
Schemas with `type: SMWConfig` are used to establish a configuration for the use of Semantic MediaWiki in the API modules `iiif-manifest` and `iiif-collection`. If and when used, they override global configuration settings. 

It is recommended to let the name of each page start with `IIIF:SMWConfig/`.

### The image annotator
Using the namespace with the image annotator serves two possible objectives:
1. to store a schema for the input form to be used with the image annotator. Such a schema should be of `type: AnnotatorFormConfig`. It is recommended to let the name of each page start with something like `IIIF:AnnotatorForm/`.
2. to let the image annotator store and retrieve annotations that are formatted as an array of `AnnotationPage`s. Using the IIIF namespace is not mandatory but may be a convenient and transparent way of storing content.

## Some features

### CodeEditor
If you have [CodeEditor](https://www.mediawiki.org/wiki/Extension:CodeEditor) installed, then the Ace editor is available to you for editing code in a user-friendly way.

### Viewing JSON
A link at the top of the page provides direct access to the JSON content. While is true that you can you always access the raw JSON by appending `?action=raw` to the page's URL, this is non-transparent to general visitors and triggers the browser to download the content as a file to your machine. Instead, a short and friendly redirect URL is offered to let you access JSON directly in the browser.

### Additional info
Schemas with `type: SMWConfig` come with additional information at the bottom of the page. Based on your settings, it lets you preview the syntax of the `#ask` queries run to provide data for 'Annotations' in the Presentation API (v2/v3) as well as for 'Collections'. 
