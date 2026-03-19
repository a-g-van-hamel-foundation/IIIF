# Working with context variables for the Lookup input (TOC creator only)

When you define a form profile and you set the possible options or remote dataset for a select input, these options apply anywhere the form gets used. But what if the list of available options to choose from needs to be more context-aware each time the form gets used?

An example first. Say your form is intended for digitised versions of manuscripts. With each manuscript you have added to the wiki, you have also created entities for each distinct scribal hand attested over the course of the manuscript's pages. Now you want your TOC creator to include an input field that lets you select the hand that you think wrote a given item. In this situation, it makes sense to restrict the list of available options to only those hands that are connected to the manuscript in question. But how to cater for such context variables?

## The solution
- The `#iiif-toc` parser function lets you reserve keys and corresponding values. Each such key MUST start with the `@` symbol.
- Where the form definition for the Lookup field usually lets you set a specific URL for the API data service to be queried (`apiUrl`), it also accepts a reference to a key.
- If the `#iiif-toc` parser function has set a URL for that key, the reference will be resolved to that URL.

## Example of this solution in action

### `#iiif-toc` inside a template

```
{{#iiif-toc:
|form=IIIF:Form/MSTOC
|targetpage=IIIF/TOC/Example
|targetslot=main
|manifest={{{Manifest|}}}
|@scribal hands={{#recon-smwquery-url:query=[[Class::Subject:scribal hands]] [[Belongs to manuscript::{{{Manuscript|}}}]] }}
}}
```

The key `@scribal hands` is associated with a URL for a local reconciliation service. The URL is never the same because it depends on the page name for the manuscript in the wiki. To build this URL, we are relying on a utility, the `#recon-smwquery-url` parser function that comes with the Reconciliation API extension.

### Form definition for the Lookup input

```JSON
{
	"inputType": "lookup",
	"label": "Hands",
	"name": "hands",
	"apiType": "reconciliation",
	"apiUrl": "@scribal hands",
	"multiple": true
}
```

Notice that `apiUrl` is set to the reference `@scribal hands`. 
