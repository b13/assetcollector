# Asset Collector for TYPO3

This extension adds ViewHelpers to dynamically add CSS, JS and SVG files and CSS inline strings to be added to the HTML 
document from within Fluid Templates. In addition, including JS files are added via a registry
to be only added once, which can be achieved via TypoScript as well.

The main benefit over TYPO3 Core functionality is that AssetCollector API is a straightforward approach
for integrators to not worry about having duplicate assets entries added again, and to only include the assets
that are necessary. This way, integrators can build content types or plugins and attach only necessary
resources to one content type, even if it was added multiple times by any editor.

## Installation & Requirements

Use `composer req b13/assetcollector` or install it via TYPO3's Extension Manager from the
[TYPO3 Extension Repository](https://extensions.typo3.org) using the extension key `assetcollector`.

You need TYPO3 v9 or later to use this extension.


## JavaScript includes

This is useful when adding JavaScript files via TypoScript or within a Fluid template based on content element. In contrast
to TYPO3 Core, the following functionality is given:
- A JavaScript resource can be added multiple times, but is only added once.
- By having a flexible API, additional attributes such as "async" or "amp" can be added directly.

All JavaScript data is always added within the "head" tag.

If adding via TypoScript, the following syntax applies:

    page.jsFiles {
        analytics = https://analytics.b13.com/track.js
        analytics.async = 1
        analytics.data-myattribute = true
    } 

## CSS inliner

This is useful for adding inline-CSS that targets "above the fold" content based on content the editor adds to the page, 
or to add CSS inline styles that add background images for specific breakpoints (media queries are possible in the html 
head, not in inline styles using the `style` attribute for specific elements). 

### Examples

In your Fluid templates use the ViewHelper to add CSS files or inline CSS code to the head of any page using this
template file:

```
<ac:css file="EXT:myext/Resources/Public/Css/myCssFile.css"/>
``` 
This includes the content of `myCssFile.css` inline as a style block in the HTML head of the document.

```
<ac:css>.b_example { color: red; }</ac:css>
```
This adds the string `.b_example { color: red; }` to the inline style block in the HTML head of the document.

Remember to add the Fluid namespace to your Fluid templates (or do this globally, see below):

```
<html 
    xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers"
    xmlns:ac="http://typo3.org/ns/B13/Assetcollector/ViewHelpers"
    data-namespace-typo3-fluid="true"
>
```

## CSS file include

The include option is useful for including CSS files as externally referenced files based on content of your pages.
Depending on the size of the CSS and the number of pages the same CSS is used inlining the CSS into the page might not
be the best option. To include a CSS file across all pages of your installation could result in including way too much
CSS for some of your content pages. 

The ViewHelper for including CSS can be used to include CSS files within your Fluid Templates as external files by 
adding `external="1"`. This adds the CSS file reference to the `<head>` part of your `<html>` document:

```
<ac:css file="EXT:myext/Resources/Public/Css/myCssFile.css" external="1" />
```

This will add the following code within the `<head>` of your document:

```
<link rel="stylesheet" type="text/css" href="/typo3conf/ext/myext/Resources/Public/Css/myCssFile.css" media="all">
```

You can also specify a value for the `media` argument:

``` 
<ac:css file="EXT:myext/Resources/Public/Css/myCssFile.css" external="1" media="print" />
```


## SVG Map inliner

Adds SVG files as inline map using a ViewHelper. A file can be added using a file path or an icon name set in your
TypoScript setup. 

This only includes the icons needed on a given page as inline SVG symbols in one svg map.


### Examples

#### Using a file path

```
<ac:svg file="EXT:myext/Resources/Public/Svg/myIconFile.svg" class="b_myIconClass"/>
```
This adds the svg inline code to your template output

```
<svg><use xlink:href="#icon-myIconFile"></use></svg>
```

and adds the symbol from `myIconFile.svg` to the page's inline SVG map. The file name should be unique and is used 
to identify the icon within the `<use>`-tag. Multiple uses of the same filename will result in the icon being included
only once (correctly so) in the SVG map.

#### Using a name/identifier set in your TypoScript setup

```
plugin.tx_assetcollector.icons {
  iconName = EXT:myext/Resources/Public/Svg/myIconFile.svg
}
```

```
<ac:svg name="iconName" class="b_myIconClass" />
```

This will add the svg inline code to your template output using `iconName` as an identifier.

### Notes on automated rendering of the svg map

The svg file is parsed and all children of the first `<svg>` tag are being included in a `<symbol>` section within the
svg map. The id of the symbol will be `icon-<filename>` and the `viewBox` from the original `<svg>` tag will be added
as an attribute to the `<symbol>` tag.
All `<symbol>` sections will be wrapped:

```
<svg aria-hidden="true" style="display: none;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <defs>
    <symbol></symbol>
    <symbol></symbol> 	
  </defs>
</svg>
```

## Global registering of Fluid Namespace

If you want to register the fluid namespace globally, add this to your site extensions `ext_localconf.php`:

```
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['ac'][] = 'B13\Assetcollector\ViewHelpers';
```

## License

The extension is licensed under GPL v2+, same as the TYPO3 Core. For details see the LICENSE file in this repository.

## Open Issues

If you find an issue, feel free to create an issue on GitHub or a pull request.

### Credits

This extension was created by [David Steeb](https://github.com/davidsteeb) in 2019 for [b13 GmbH](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of the way we work, we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
