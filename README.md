# EXT:assetcollector

This extension adds ViewHelpers to dynamically add CSS and SVG files and CSS inline strings to be added to the HTML 
document from within Fluid Templates. 

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
<html xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers"
			xmlns:ac="http://typo3.org/ns/B13/Assetcollector/ViewHelpers"
			data-namespace-typo3-fluid="true"
>
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