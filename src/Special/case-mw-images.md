An implementation of the [IIIF Image API](https://iiif.io/api/image) lets you request an image given a preferred region, size, rotation, quality and format, all of which are specified directly in the URL's path. Many or most professional organisations that make IIIF resources available and handle such requests do so using dedicated image servers such as [Cantaloupe](https://cantaloupe-project.github.io). But what about MediaWiki? Is it up to the task?

It is true that, by default at least, MediaWiki's image file system is not designed to cater for advanced features such as the generation of tiles to optimise the performance of panning and zooming in high-resolution images, or similarly, cropping images to return a specific spatial region. Fortunately, that does not disqualify MediaWiki. The IIIF Image API allows for a low-level ('level-0') request to be made without requiring tiles. MediaWiki's thumbnailing service, while not ideal for high-resolution images, can be used to deliver the appropriate scale and resolution dynamically. 

## Generating a Manifest with the `iiif-mw-pres` API module
To view images in a IIIF-compliant viewer, you need a IIIF Manifest. To view images from a MediaWiki repository in this way, they need to be appropriately referenced in the Manifest.

The [`iiif-mw-pres` API module]({urlBase}/Special:IIIF/iiif-mw-pres) simplifies this process. It allows you to use your local MediaWiki repository (using native methods) and/or external MediaWiki sites such as Wikimedia Commons (by fetching data from the API). What comes out is a Manifest that can be loaded in your viewer. Conveniently, the API module can also be accessed through a redirect service, examples of which are given below.

In what follows, note that shorthands must be used to identify the repository of each image:
- `local` for local images
- `commons` for Wikimedia Commons
- a unique string corresponding to the 'name' you've assigned to a trusted site in your configuration

The default API version is **v2**. To use v3, append `&version=3` to the API URL or if you use the redirect service, replace `presentation` with `presentation3`. 

### 1. Using local images only
The easiest approach is as follows:

* Set `source` to 'local'
* Do either of the following:
    * use `pageids` to list one or multiple image files by [page id](https://www.mediawiki.org/wiki/Help:Page_ID). Use **commas** to separate multiple page ids.
    * use `files` to list one or multiple image files by page name. Use **semi-colons** to separate multiple file names. Page titles with and without `File:` namespace prefix are acceptable.

- Example with page IDs:
`/api.php?action=iiif-mw-pres&format=json&source=local&pageids=44730,44736,51926`
    - Redirect: `/Special:IIIFServ/presentation/manifest/local/pageids/44730,44736,51926`
- Example with file names: `/api.php?action=iiif-mw-pres&format=json&formatversion=2&source=local&files=Bg-gospel-ccc-ox-122.jpg;File:Bg-StGall-St-John.jpg;1617197682.png`
    - Redirect: `/Special:IIIFServ/presentation/manifest/local/files/Bg-gospel-ccc-ox-122.jpg;File:Bg-StGall-St-John.jpg;1617197682.png`

### 2. Using an external MediaWiki site
You can include images from other MediaWiki websites. Wikimedia Commons is supported by default so no configuration is necesssary. To query any other external repository, the site admin must configure the wiki's settings to trust it, [as described under ‘Configuration’](/Special:IIIF/config).

The easiest approach is as follows:
- Set `source` to 'commons' or the name that was set in the configuration
- Use either `pageids` or `files` as explained above

Example with page IDs:
- `/api.php?action=iiif-mw-pres&format=json&formatversion=2&source=commons&pageids=6228718,1230085,44768586`
    - Redirect: `/Special:IIIFServ/presentation/manifest/commons/pageids/6228718,1230085,44768586`
    

### 3. Using multiple repositories
So far so good but what if you want to include images from different repositories in a single Manifest? This is currently supported for use with the `pageids` option. 
- Set `source` to the main identifier.
- For each file from a different repository, prefix its page ID with the repository identifier and a colon, e.g. `local:` or `commons:`. If no identifier is used as a prefix, it will default to the main one.

Example: `/Special:IIIFServ/presentation/manifest/local/pageids/44736,commons:1230085,44730,commons:44768586`

## Links
- A IIIF Manifest follows specifications of the Presentation API:
    - https://iiif.io/api/presentation/3.0/
    - https://iiif.io/api/presentation/2.1/
