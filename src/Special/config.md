_For a list of default and current settings on this wiki, [see here]({urlBase}/Special:IIIF/settings)_

## How it works

### Configuration for Semantic MediaWiki
The API modules `iiif-manifest` (and with it, `iiif-annotations`) and `iiif-collection` allow you to use [Semantic MediaWiki](https://www.semantic-mediawiki.org) to provide data with which to create or modify IIIF resources. The default settings make assumptions about the properties and query conditions used to run the relevant queries, but you'll likely want to modify these global defaults for your own wiki. The extension lets you customise them in two ‘cascading’ ways:

1. As default [configuration settings](https://www.mediawiki.org/wiki/Manual:Configuration_settings) in your LocalSettings.php file.
2. As a JSON schema/profile stored in the [`IIIF:` namespace]({urlBase}/Special:IIIF/namespace). A schema can be used to override your site-wide settings simply by pointing the API module to the page ID that identifies the schema.

### Configuration for trusted MediaWiki image repositories
To allow Wikimedia Commons, set `$wgUseInstantCommons = true`. Any other external MediaWiki repository from which you want to pull images must be registered as a trusted site in [`$wgForeignFileRepos`](https://www.mediawiki.org/wiki/Manual:$wgForeignFileRepos). For instance, if you were to add [Wikivoyage](https://en.wikivoyage.org/), you could add the following to your LocalSettings.php file:

```
$wgForeignFileRepos[] = [
	'class' => ForeignAPIRepo::class, // Required
	'name' => 'wikivoyage', // Required. A simple, unique name to identify the repo, without spaces and punctation
	'apibase' => 'https://en.wikivoyage.org/w/api.php', // Required. Must be HTTPS
	'hashLevels' => 2,
	'fetchDescription' => true, 
	'descriptionCacheExpiry' => 43200,
	'apiThumbCacheExpiry' => 86400
];
```

What’s especially important for our purposes is the 'name' (here `wikivoyage`) with which you have identified the repository. We will rely on this identifier to set up the API module.

#### $wgIIIFForeignFileRepos
On top of `$wgForeignFileRepos`, which is native to MediaWiki, you MAY also need to set `$wgIIIFForeignFileRepos` to set a list of (canonical) thumbnail widths allowed by the wiki. For MediaWiki sites in general, this extension uses 320, 640, 800, 1024, 1280 and 2560 px as the default, which is taken from MediaWiki's default list (minus the smaller thumb sizes). Because Wikimedia Commons uses an alternative configuration (20, 40, 60, 120, 250, 330, 500, 960, 1280, 1920 and 3840 px, at the time of writing - April 2026) and no longer honours requests for non-canonical sizes, a wiki-specific default is set in `$wgIIIFForeignFileRepos` that overrides the general default:

```php
$wgIIIFForeignFileRepos = [
	[
		"name": "commons",
		"thumbsteps": [ 120, 250, 330, 500, 960, 1280, 1920, 3840 ]
	]
]
```

If those values happen to become invalid for Wikimedia Commons (after all, maintainers may change their minds), just find the new set of values and change this entry in `$wgIIIFForeignFileRepos` accordingly. If you have added other services to `$wgForeignFileRepos`, such as 'wikivoyage' in our example above, add new entries in the same way and make sure each entry has a corresponding 'name'.

## JSON schema in the IIIF namespace
It is recommended to use the IIIF namespace to store your JSON configuration schemas and identify each schema with the appropriate `type`, such as 'SMWConfig' and 'FormConfig'.
<!-- SemanticAnnotationsConfig should now be SMWConfig -->

### type: SMWConfig
Schemas with type `SMWConfig` are used to provide the necessary ingredients for a semantic query, overriding the default configuration for Semantic MediaWiki. Their usage is explained in the usage guides on [building Collections]({urlBase}/Special:IIIF/case-collections) and [working with Annotations]({urlBase}/Special:IIIF/case-smw-annotations).

### type: FormConfig
Schemas with type `FormConfig` are used to create the form to be used with the [annotation tool]({urlBase}/Special:IIIF/iiif-annotator). See [Creating forms]({urlBase}/Special:IIIF/config-form).
