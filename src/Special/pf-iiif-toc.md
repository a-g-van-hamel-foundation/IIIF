_This is only a draft_

The `#iiif-toc` parser function can be used to enable what is currently a prototype of a TOC (table of contents) creator. 

## Functional aims
- Be able to add items below and in between 'items', remove them and re-sort their order of appearance.
- Be able to describe each constituent item in a resource, such as a prose text, poem, book chapter, comic book frame, etc., in a linear order
    - Be able to do so according to a custom profile for form definitions. Site admins should be afforded some flexibility in what to describe. 
     - To enrich these descriptions, be able to add references to (linked) data entities, which may be pages in your wiki or items you find on Wikidata.
- Be able to convert the resulting data to what IIIF calls Ranges, which are structural hierarchies in a manifest that can used as a navigational aid (see https://iiif.io/api/presentation/3.0/#54-range for the v3 specs). In many IIIF viewers, you can tell whether or not a manifest contains Ranges by looking for a table of contents. To facilitate the conversion, we need to:
    - Be able to record an indentation level as a cue for the convertor to nest items in a tree-like structure. Direct nesting has been kept out of the main model in part because of its potentially negative impact on performance and in large part because of the additional work that would go into implementation.
    - Be able to let an item link to Canvases in a customisable order of appearance. Canvas fragments have not been considered for the prototype just yet.
- Be able to record a relatively long list of items. 

### What you need
- WSSlots extension
- While a default form is available, you might want to create your own form definitions. See the section about creating form definitions in your wiki.

### `#iiif-toc`
[ ... ]
