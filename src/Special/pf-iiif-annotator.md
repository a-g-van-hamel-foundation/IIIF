_At this stage the annotator should be considered experimental. It is still under development and is known to contain some minor bugs._

The annotation tool can be enabled and configured on a wiki page by using the [parser function](https://www.mediawiki.org/wiki/Parser_functions) `#iiif-annotator`. 

## Parameters

- **manifest** - the IIIF manifest URL
- **profileid** - the page ID of the associated form in the IIIF namespace (see the relevant section). If no profile is provided, the form will offer a textarea with 'description' as its input name.
- **target** - full name of the wiki page. The convenient place for storing your annotations would be in the IIIF namespace. 
- **targetslot** - if the [WSSlots extension](https://github.com/Open-CSP/WSSlots) is installed, the [content slot](https://www.mediawiki.org/wiki/Manual:Slot) of the wiki page to which annotations should be saved. Defaults to `main`.
- ...

Example:

```
{{#iiif-annotator:
|manifest=https://www.isos.dias.ie/static/manifests/NLI_MS_G_3.json
|profileid=70486
|target=IIIF:AnnotationPages/NLIG3
}}
```

