In order to be able to point to any particular canvas, you usually need its canvas ID, but having to look it up manually for every single item can be cumbersome. 

To get the relevant data about canvases out of the Manifest and transfer everything to instances of a wiki template, you can use the `#iiif-get-canvases` parser function. It should support V2 as well as V3 Manifests.

## Parameters

```
{{#iiif-get-canvases:
|manifest=
|template=
|userparam1=
|userparam2=
|userparam3=
}}
```

- **manifest** - manifest URL
- **template** - name of the wiki template to use for outputting canvas data. If `template` is omitted, a JSON representation is returned instead.
- **userparam1**, **userparam2**, **userparam3** - can be used to send custom data to each instance of the wiki template. 

Each instance of a template comes with data for the following parameters:

- **manifest** - manifest URL
- **key** - numerical Canvas key (in order of appearance)
- **id** - Canvas id
- **label** - Canvas label (if available)
- **description** - Canvas description (if available)
- **image** - full image URL
- **serviceid** - service identifier (if available), without `/info.json`
- **userparam1**, **userparam2**, **userparam3** - custom data
