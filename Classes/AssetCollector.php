<?php
declare(strict_types = 1);
namespace B13\Assetcollector;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use B13\Assetcollector\Resource\ResourceCompressor;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Main collector class to be used everywhere
 */
class AssetCollector implements SingletonInterface
{
    /**
     * @var array
     */
    protected $inlineCss = [];

    /**
     * @var array
     */
    protected $cssFiles = [];

    /**
     * Array of JS files which are appended in script tag in head element.
     * @var array
     */
    protected $jsFiles = [];

    /**
     * @var array
     */
    protected $xmlFiles = [];

    /**
     * Array of CSS files which are appended in link tag in head element.
     *
     * @var array
     */
    protected $externalCssFiles = [];

    /**
     * @var ?array
     */
    protected $typoScriptConfiguration = null;

    public function addInlineCss(string $inlineCss): void
    {
        $this->inlineCss[] = $inlineCss;
    }

    public function addCssFile(string $cssFile): void
    {
        $this->cssFiles[] = GeneralUtility::getFileAbsFileName($cssFile);
    }

    /**
     * @param string $fileName
     * @param string $mediaType
     */
    public function addExternalCssFile(string $fileName, string $mediaType = 'all'): void
    {
        // Only add external css file if not added already.
        foreach ($this->externalCssFiles as $cssFile) {
            if ($cssFile['fileName'] == $fileName) {
                return;
            }
        }
        $this->externalCssFiles[] = [
            'fileName' => $fileName,
            'mediaType' => $mediaType,
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
        $this->xmlFiles[] = GeneralUtility::getFileAbsFileName($xmlFile);
    }

    public function getUniqueExternalCssFiles(): array
    {
        return $this->externalCssFiles;
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
            if (file_exists($cssFile)) {
                $inlineCss .= $this->removeUtf8Bom(file_get_contents($cssFile)) . "\n";
            }
        }
        if (trim($inlineCss) !== '') {
            $compressor = GeneralUtility::makeInstance(ResourceCompressor::class);
            return '<style>' . $compressor->publicCompressCssString($inlineCss) . '</style>';
        } else {
            return '';
        }
    }

    public function buildInlineXmlTag(): string
    {
        $inlineXml = '';
        $xmlFiles = $this->getUniqueXmlFiles();
        foreach ($xmlFiles as $xmlFile) {
            if (file_exists($xmlFile)) {

                $iconIdentifier = $this->getIconIdentifierFromFileName($xmlFile);
                $svgInline = '';
                $xmlContent = new \DOMDocument();
                $xmlContent->loadXML(file_get_contents($xmlFile));

                $viewBox = $xmlContent->getElementsByTagName('svg')->item(0)->getAttribute('viewBox');

                $children = $xmlContent->getElementsByTagName('svg')->item(0);

                foreach ($children->childNodes as $child) {
                    $svgInline .= trim((string)($child->ownerDocument->saveHtml($child)));
                }

                $inlineXml .= '<symbol id="icon-' . $iconIdentifier . '" viewBox="' . $viewBox . '">'
                              . $svgInline
                              . '</symbol>';

            }
        }
        if (trim($inlineXml) !== '') {
            return '<svg aria-hidden="true" style="display: none;" version="1.1" xmlns="http://www.w3.org/2000/svg" '
                   . 'xmlns:xlink="http://www.w3.org/1999/xlink">'
                   . '<defs>'
                   . $inlineXml
                   . '</defs></svg>';
        } else {
            return '';
        }
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
        $this->typoScriptConfiguration = $this->getExtbaseFrameworkConfiguration() ?? [];
    }

    protected function getExtbaseFrameworkConfiguration(): ?array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        try {
            $extbaseFrameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            if (is_array($extbaseFrameworkConfiguration['plugin.']['tx_assetcollector.']['icons.'])) {
                return $extbaseFrameworkConfiguration['plugin.']['tx_assetcollector.']['icons.'];
            }
        } catch (\TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException $e) {

        }
        return null;

    }

}
