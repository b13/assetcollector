<?php
namespace B13\Assetcollector\ViewHelpers;

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


use B13\Assetcollector\AssetCollector;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Class SvgViewHelper
 * @package B13\Assetcollector
 */
class SvgViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'svg';

    /**
     * @var AssetCollector
     */
    protected $assetCollector = null;

    /**
     * @param AssetCollector $assetCollector
     */
    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    /**
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument(
            'file',
            'string',
            'svg file name',
            false
        );
        $this->registerArgument(
            'name',
            'string',
            'SVG file name from TypoScript setup.',
            false
        );
    }

    /**
     * @return String rendered tag
     */
    public function render(): string
    {
        $file = '';
        if (!empty($this->arguments['name'])) {
            $file = $this->assetCollector->getTypoScriptValue((string)$this->arguments['name']);
        }
        if ($file === '' && !empty($this->arguments['file'])) {
            $file = $this->arguments['file'];
        }
        if ($file === '') {
            return '';
        }

        $this->assetCollector->addXmlFile($file);
        $iconIdentifier = $this->assetCollector->getIconIdentifierFromFileName($file);
        $content = '<use xlink:href="#icon-' . $iconIdentifier . '"></use>';

        $this->tag->forceClosingTag(true);
        $this->tag->setContent($content);

        return $this->tag->render();

    }
}