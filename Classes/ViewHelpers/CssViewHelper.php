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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class CssViewHelper
 * @package B13\Assetcollector
 */
class CssViewHelper extends AbstractViewHelper
{

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
        $this->registerArgument(
            'file',
            'string',
            'css file name',
            false
        );
        $this->registerArgument(
            'external',
            'boolean',
            'Specifies if the given CSS file should be loaded within link tag.'

        );
        $this->registerArgument(
            'media',
            'string',
            'Specifies the value for the media attribute. Default is "all".'
        );
    }

    /**
     * @return void
     */
    public function render(): void
    {
        if (!empty($this->arguments['file'])) {
            if (!empty($this->arguments['external'])) {
                if (!empty($this->arguments['media'])) {
                    $this->assetCollector->addExternalCssFile($this->arguments['file'], $this->arguments['media']);
                } else {
                    $this->assetCollector->addExternalCssFile($this->arguments['file']);
                }
            } else {
                $this->assetCollector->addCssFile($this->arguments['file']);
            }
        } else {
            $this->assetCollector->addInlineCss($this->renderChildren());
        }
    }
}
