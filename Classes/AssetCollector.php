<?php

declare(strict_types=1);

namespace B13\Assetcollector;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Main collector class to be used everywhere
 */
class AssetCollector
{
    protected array $inlineCss = [];
    protected array $cssFiles = [];
    protected array $jsFiles = [];
    protected array $xmlFiles = [];
    protected array $externalCssFiles = [];
    protected ?array $typoScriptConfiguration = null;

    public function addInlineCss(string $inlineCss): void
    {
        $this->inlineCss[] = $inlineCss;
    }

    public function addCssFile(string $cssFile): void
    {
        $this->cssFiles[] = $cssFile;
    }

    public function addExternalCssFile(string $fileName, string $mediaType = 'all'): void
    {
        // Only add external css file if not added already.
        foreach ($this->externalCssFiles as $cssFile) {
            if ($cssFile['fileName'] === $fileName) {
                return;
            }
        }
        $this->externalCssFiles[] = [
            'fileName' => $fileName,
            'mediaType' => $mediaType,
        ];
    }

    public function addJavaScriptFile(string $fileName, ?array $additionalAttributes = null): void
    {
        // Only add JS file if not added already.
        foreach ($this->jsFiles as $jsFile) {
            if ($jsFile['fileName'] === $fileName) {
                return;
            }
        }
        $this->jsFiles[] = [
            'fileName' => $fileName,
            'additionalAttributes' => $additionalAttributes,
        ];
    }

    public function mergeCssFiles(array $cssFiles): void
    {
        $this->cssFiles = array_merge($this->cssFiles, $cssFiles);
    }

    public function mergeXmlFiles(array $xmlFiles): void
    {
        $this->xmlFiles = array_merge($this->xmlFiles, $xmlFiles);
    }

    public function mergeInlineCss(array $inlineCss): void
    {
        $this->inlineCss = array_merge($this->inlineCss, $inlineCss);
    }

    public function getUniqueInlineCss(): array
    {
        return array_unique($this->inlineCss);
    }

    public function getUniqueCssFiles(): array
    {
        return array_unique($this->cssFiles);
    }

    public function getUniqueXmlFiles(): array
    {
        return array_unique($this->xmlFiles);
    }

    public function addXmlFile(string $xmlFile): void
    {
        $xmlFile = preg_replace('/^\//', '', $xmlFile);
        $this->xmlFiles[] = $xmlFile;
    }

    public function getExternalCssFiles(): array
    {
        return $this->externalCssFiles;
    }

    public function getJavaScriptFiles(): array
    {
        return $this->jsFiles;
    }

    public function getIconIdentifierFromFileName(string $xmlFile): string
    {
        return str_replace('.svg', '', basename($xmlFile));
    }

    /**
     * Make the inline SVG sprite self-healing: ensure every icon referenced in
     * the given markup (via <use href="#icon-…">) is collected for inline
     * rendering.
     *
     * The sprite is injected into the response by a middleware reading the
     * collected files from the "tx_assetcollector" cache, which lives next to –
     * but separate from – the page cache. If that cache ever desyncs (e.g. it is
     * cleared while the page cache survives, or it is stored on a different
     * backend), a fully cached page is delivered without the ViewHelpers being
     * re-rendered, the collector ends up empty and the sprite would be missing –
     * every icon on the page disappears until the page cache is regenerated.
     *
     * By re-resolving the referenced icons from the given icon registry
     * (identifier => SVG file) we guarantee the sprite is never delivered
     * incomplete. The registry must be passed in because the TypoScript setup is
     * not available in cached frontend scope – the caller is responsible for
     * providing it (see InlineSvgInjector, which persists it in a dedicated
     * cache). An empty registry makes this a no-op, so it can never make
     * rendering worse than before.
     *
     * @param \Closure(): array<string, string> $iconRegistryProvider lazily resolves
     *        the icon identifier => SVG file map; only invoked when the page actually
     *        references an icon that is not collected, to avoid needless cache reads
     */
    public function addReferencedIcons(string $markup, \Closure $iconRegistryProvider): void
    {
        if ($markup === '' || !preg_match_all('/(?:xlink:href|href)="#icon-([A-Za-z0-9_.-]+)"/', $markup, $matches)) {
            return;
        }
        $referencedIdentifiers = array_unique($matches[1]);
        $collectedIdentifiers = [];
        foreach ($this->getUniqueXmlFiles() as $xmlFile) {
            $collectedIdentifiers[$this->getIconIdentifierFromFileName($xmlFile)] = true;
        }
        $missingIdentifiers = array_diff($referencedIdentifiers, array_keys($collectedIdentifiers));
        if ($missingIdentifiers === []) {
            return;
        }
        $iconRegistry = $iconRegistryProvider();
        foreach ($missingIdentifiers as $identifier) {
            if (isset($iconRegistry[$identifier])) {
                $this->addXmlFile($iconRegistry[$identifier]);
            }
        }
    }

    /**
     * Map of icon identifier => SVG file path, built from the configured icon
     * registry (plugin.tx_assetcollector.icons). Returns an empty array when the
     * TypoScript setup is not available (e.g. in cached frontend scope).
     *
     * @return array<string, string>
     */
    public function getIconRegistry(): array
    {
        if ($this->typoScriptConfiguration === null) {
            $this->loadTypoScript();
        }
        $registry = [];
        foreach ($this->typoScriptConfiguration as $file) {
            $file = (string)$file;
            if ($file !== '') {
                $registry[$this->getIconIdentifierFromFileName($file)] = $file;
            }
        }
        return $registry;
    }

    public function buildInlineCssTag(): string
    {
        $inlineCss = implode("\n", $this->getUniqueInlineCss());
        $cssFiles = $this->getUniqueCssFiles();
        foreach ($cssFiles as $cssFile) {
            if (file_exists(GeneralUtility::getFileAbsFileName($cssFile))) {
                $inlineCss .= $this->cssContentWithResolvedPaths($cssFile) . "\n";
            }
        }
        if (trim($inlineCss) !== '') {
            return '<style class="tx_assetcollector">' . trim($inlineCss) . '</style>';
        }
        return '';
    }

    protected function cssContentWithResolvedPaths(string $cssFile): string
    {
        $content = '';
        $absoluteFile =  GeneralUtility::getFileAbsFileName($cssFile);
        if (file_exists($absoluteFile)) {
            $content = $this->removeUtf8Bom(file_get_contents($absoluteFile));
            preg_match_all('/url\("([a-zA-Z0-9_.\-\/]+)"\)/', $content, $matches);
            if (!empty($matches[1])) {
                $content = $this->replacePaths($matches[1], $cssFile, $content);
            }
            preg_match_all('/url\(([a-zA-Z0-9_.\-\/]+)\)/', $content, $matches);
            if (!empty($matches[1])) {
                $content = $this->replacePaths($matches[1], $cssFile, $content);
            }
        }
        return $content;
    }

    protected function replacePaths(array $relativeToCssPaths, string $cssFile, string $content): string
    {
        foreach ($relativeToCssPaths as $relativeToCssPath) {
            $absolute = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($cssFile, $relativeToCssPath);
            if (file_exists(GeneralUtility::getFileAbsFileName($absolute))) {
                $publicWebPath = PathUtility::getPublicResourceWebPath($absolute);
                $content = str_replace($relativeToCssPath, $publicWebPath, $content);
            }
        }
        return $content;
    }

    public function buildJavaScriptIncludes(): string
    {
        $includes = '';
        foreach ($this->getJavaScriptFiles() as $file) {
            if (empty($file['fileName'])) {
                return '';
            }
            $attributes = $file['additionalAttributes'] ?? [];
            $attributeCode = [];
            foreach ($attributes as $name => $value) {
                if ($value !== null && $value !== '') {
                    $attributeCode[] = htmlspecialchars($name) . '="' . htmlspecialchars($value) . '"';
                } else {
                    $attributeCode[] = htmlspecialchars($name);
                }
            }
            $webPath = (str_starts_with($file['fileName'], 'EXT:'))
                ? PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName(($file['fileName'])))
                : $file['fileName'];
            $includes .= '<script src="' . htmlspecialchars($webPath) . '"' . (!empty($attributeCode) ? ' ' . implode(' ', $attributeCode) : '') . '></script>';
        }
        return $includes;
    }

    public function buildInlineXmlTag(): string
    {
        $inlineXml = '';
        $xmlFiles = $this->getUniqueXmlFiles();
        foreach ($xmlFiles as $xmlFile) {
            $absoluteFile = GeneralUtility::getFileAbsFileName($xmlFile);
            if (file_exists($absoluteFile)) {
                $iconIdentifier = $this->getIconIdentifierFromFileName($xmlFile);
                $svgInline = '';
                $xmlContent = new \DOMDocument();
                $xmlContent->loadXML(file_get_contents($absoluteFile));

                $viewBox = $xmlContent->getElementsByTagName('svg')->item(0)->getAttribute('viewBox');
                $viewBoxAttribute = $viewBox ? ' viewBox = "' . $viewBox . '"' : '';

                $children = $xmlContent->getElementsByTagName('svg')->item(0);

                foreach ($children->childNodes as $child) {
                    $svgInline .= trim((string)($child->ownerDocument->saveHtml($child)));
                }

                $inlineXml .= '<symbol id="icon-' . $iconIdentifier . '"' . $viewBoxAttribute . '>'
                              . $svgInline
                              . '</symbol>';
            }
        }

        if (trim($inlineXml) !== '') {
            return '<svg class="tx_assetcollector" aria-hidden="true" style="display: none;" version="1.1" xmlns="http://www.w3.org/2000/svg" '
                   . 'xmlns:xlink="http://www.w3.org/1999/xlink">'
                   . '<defs>'
                   . $inlineXml
                   . '</defs></svg>';
        }
        return '';
    }

    protected function removeUtf8Bom(string $text): string
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    /**
     * Function returns the value for given TypoScript key.
     *
     * @param string $name
     * @return string
     */
    public function getTypoScriptValue(string $name): string
    {
        if ($this->typoScriptConfiguration === null) {
            $this->loadTypoScript();
        }
        if (!empty($this->typoScriptConfiguration[$name])) {
            return (string)$this->typoScriptConfiguration[$name];
        }
        return '';
    }

    protected function loadTypoScript(): void
    {
        $this->typoScriptConfiguration = [];
        $request = $this->getServerRequest();
        if ($request === null) {
            return;
        }
        /** @var FrontendTypoScript|null $typoScript */
        $typoScript = $request->getAttribute('frontend.typoscript');
        if ($typoScript === null) {
            return;
        }
        // The full setup array is only available in uncached frontend scope. On a
        // cached request getSetupArray() throws, so degrade gracefully to an empty
        // configuration instead of breaking the request.
        try {
            $setup = $typoScript->getSetupArray();
        } catch (\RuntimeException) {
            return;
        }
        $this->typoScriptConfiguration = $setup['plugin.']['tx_assetcollector.']['icons.'] ?? [];
    }

    protected function getServerRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
