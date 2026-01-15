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
        /** @var FrontendTypoScript $typoScript */
        $typoScript = $request->getAttribute('frontend.typoscript');
        $setup = $typoScript->getSetupArray();
        $this->typoScriptConfiguration = $setup['plugin.']['tx_assetcollector.']['icons.'] ?? [];
    }

    protected function getServerRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
