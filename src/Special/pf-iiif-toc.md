The `#iiif-toc` parser function enables a JavaScript-based TOC (table of contents) creator that lets you create and manage a TOC's constituent 'items'. It can be used directly in combination with a IIIF resource and IIIF image viewer, as well as without them.

## Purpose
Objects such as those represented digitally by IIIF resources can be complex and unwieldy creatures. The typical use case envisaged for this tool is where you would need a structured inventory that can be used to navigate the contents of a IIIF resource. In providing a guide to individual texts, book chapters, poems, comic book frames and whatever may be found within the larger body of material, you can make users feel a little less lost as they flick through the pages. On top of that, you may want to break out of the silo of a single resource and expose anything of interest to your wiki's data management tools, such as Semantic MediaWiki or Cargo.

## Setup

### General
The workflow is broadly similar to that intended for the annotation tool:
- **Set up forms** - The parser function lets you reference a JSON profile to take care of the form field definitions to be used for each item.
- **Store data in JSON** - Data managed with this tool is stored as a JSON string on whatever page and in whatever content slot you want it to be stored.
- **(Re)use JSON data** - Once stored, JSON is available for wider use within the system:
    - (a) The `iiif-range` API module was specifically designed to convert the TOC to Ranges in the IIIF Presentation v2 or v3 format and merge those Ranges into a new Manifest.
    - (b) Extensions like Scribunto, ExternalData and TemplateFunc let you take advantage of JSON data. The primary use case for CODECS is the transformation of JSON 'items' into Semantic MediaWiki's subobjects.

### Requirements
- [WSSlots extension](https://github.com/Open-CSP/WSSlots)

### Define form fields
A simple default form is available. To truly 'own' your data, you might want to create your own field definitions in a form profile. See the section about creating form definitions in your wiki.

The following field names are reserved and must not be used in your form definition: 'id', 'label', 'canvasids', 'indentLevel', 'Select'.

### The parser function `#iiif-toc`
```wiki
{{#iiif-toc:
|targetpage= // Wiki page to be used for storing your JSON data. Mandatory.
|targetslot= // Name of the relevant content slot (default: main).
|manifest= // IIIF manifest URL (if any).
|form= // Name of the page containing your form profile. The IIIF namespace can be an appriopriate place for storing your form profiles.
|formid= // Alternatively, the page ID of that page.
|@... // any number of parameters starting with an at symbol (@). This can be used to make your form configuration work with variables.

<!-- Presentation options -->
|mode= // Either standalone (default) or windows. 
|iiifviewer= // tify (default) enables the Tify viewer. To prevent the Tify viewer from loading, use an empty (or dummy) value.
|wrapperclass= // resizable-layout--fixed (default), resizable-layout or a custom class, applicable only if mode=windows. 'resizable-layout--fixed' creates a fixed layout that claims maximum screen width.
|wrapperstyle= // Style for the same HTML element, again applicable only if mode=windows. For instance, if your site has a fixed header, 50px in height, that should remain in sight and you are using 'resizable-layout--fixed', you may want to push the layout just below the fixed header. You can then style this by adding top:50px; height:calc(100vh - 50px). 
}}
```

## Usage

Actions supported by the interface:

- **Add item** - There are two ways to add new items. Hit the button at the bottom to append a new item to the list. To insert a new item below an existing one, click the + icon that sits next to the x icon on the right.
- **Move item** - Use the drag handle on the left to move an item up or down.
- **Remove item** - To remove an item, click the x icon on the right. In case you did so by accident (we are all human), you will be asked to confirm your action.
- **Indent item** - To indicate that an item is part of a previous one, use the number-type input field to create an appropriate indentation level between 0 (default) and 5. For instance, the item for 'Book I' would have a default indentation level of 0 (no indentation), while the next two items describing Chapters 1 and 2 within Book I would both have an indentation level of 1. The underlying data structure will not change, but when the `iiif-range` API module is used, these cues are interpreted to produce the data structure of Ranges expected by the particular Presentation API used (referencing in v2, nesting in v3).
- **Name item** - The input field that's always visible is intended for an item's label corresponding to a Range's label.
- **Expand form** - To reveal the form section, expand it using the arrow handle.
- **Identify canvases** - If you have provided a manifest URL, the form will automatically come with an input field to let you select the relevant 'canvases' that contain an item. It is not currently possible to select a canvas fragment by xywh region.
- **View advanced info** - Hit the three dots in the top menu.
- **Save data** - Last but not least, do not to forget to hit "Save" every now and then to avoid losing your data!
