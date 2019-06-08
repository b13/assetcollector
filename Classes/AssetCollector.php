<?php

namespace B13\Assetcollector;

/***************************************************************
 * *  Copyright notice - MIT License (MIT)
 *
 *  (c) 2019 b13 GmbH,
 *        David Steeb <david.steeb@b13.com>
 *  All rights reserved
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 ***************************************************************/


use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AssetCollector
 * @package B13\Assetcollector
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
     * @var array
     */
    protected $xmlFiles = [];

    /**
     * @param string $inlineCss
     */
    public function addInlineCss($inlineCss): void
    {
        $this->inlineCss[] = $inlineCss;
    }

    /**
     * @param string $cssFile
     */
    public function addCssFile($cssFile): void
    {
        $this->cssFiles[] = GeneralUtility::getFileAbsFileName($cssFile);
    }

    /**
     * @param array $cssFiles
     */
    public function mergeCssFiles(array $cssFiles): void
    {
        $this->cssFiles = array_merge($this->cssFiles, $cssFiles);
    }

    /**
     * @param array $xmlFiles
     */
    public function mergeXmlFiles(array $xmlFiles): void
    {
        $this->xmlFiles = array_merge($this->xmlFiles, $xmlFiles);
    }

    /**
     * @param array $inlineCss
     */
    public function mergeInlineCss(array $inlineCss): void
    {
        $this->inlineCss = array_merge($this->inlineCss, $inlineCss);
    }

    /**
     * @return array
     */
    public function getUniqueInlineCss(): array
    {
        return array_unique($this->inlineCss);
    }

    /**
     * @return array
     */
    public function getUniqueCssFiles(): array
    {
        return array_unique($this->cssFiles);
    }

    /**
     * @return array
     */
    public function getUniqueXmlFiles(): array
    {
        return array_unique($this->xmlFiles);
    }

    /**
     * @param string $xmlFile
     */
    public function addXmlFile($xmlFile): void
    {
        $this->xmlFiles[] = GeneralUtility::getFileAbsFileName($xmlFile);
    }

    /**
     *
     * @param string $xmlFile
     * @@return string
     */
    public function getIconIdentifierFromFileName($xmlFile): string
    {
        return str_replace('.svg', '', basename($xmlFile));
    }

    /**
     * @return string
     */
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
            return '<style>' . $this->compressCssString($inlineCss) . '</style>';
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
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

    /**
     * @param string $text
     * @return string
     */
    protected function removeUtf8Bom($text): string
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    /**
     * Compress a CSS string by removing comments and whitespace characters
     * Taken from /typo3/sysext/core/Classes/Resource/ResourceCompressor.php:667
     *
     * @param string $contents
     * @return string
     */
    protected function compressCssString($contents)
    {
        // Perform some safe CSS optimizations.
        // Regexp to match comment blocks.
        $comment = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
        // Regexp to match double quoted strings.
        $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
        // Regexp to match single quoted strings.
        $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
        // Strip all comment blocks, but keep double/single quoted strings.
        $contents = preg_replace(
            "<($double_quot|$single_quot)|$comment>Ss",
            '$1',
            $contents
        );
        // Remove certain whitespace.
        // There are different conditions for removing leading and trailing
        // whitespace.
        // @see http://php.net/manual/regexp.reference.subpatterns.php
        $contents = preg_replace(
            '<
				# Strip leading and trailing whitespace.
				\s*([@{};,])\s*
				# Strip only leading whitespace from:
				# - Closing parenthesis: Retain "@media (bar) and foo".
				| \s+([\)])
				# Strip only trailing whitespace from:
				# - Opening parenthesis: Retain "@media (bar) and foo".
				# - Colon: Retain :pseudo-selectors.
				| ([\(:])\s+
				>xS',
            // Only one of the three capturing groups will match, so its reference
            // will contain the wanted value and the references for the
            // two non-matching groups will be replaced with empty strings.
            '$1$2$3',
            $contents
        );
        // End the file with a new line.
        $contents = trim($contents);
        // Ensure file ends in newline.
        $contents .= LF;
        return $contents;
    }

}
