<?php

declare(strict_types=1);
namespace B13\Assetcollector\Resource;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

/**
 * This new class is used to allow to separately compress CSS code, which is not possible
 * by directly using TYPO3 Core.
 */
class ResourceCompressor extends \TYPO3\CMS\Core\Resource\ResourceCompressor
{
    public function publicCompressCssString(string $content): string
    {
        return $this->compressCssString($content);
    }
}
