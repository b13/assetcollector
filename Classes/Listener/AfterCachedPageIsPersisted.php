<?php

declare(strict_types=1);

namespace B13\Assetcollector\Listener;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use B13\Assetcollector\Hooks\AssetRenderer;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;

#[AsEventListener(identifier: 'b13-assetcollector-after-cached-page-is-persisted')]
class AfterCachedPageIsPersisted
{
    public function __construct(private readonly AssetRenderer $assetRenderer)
    {
    }

    public function __invoke(AfterCachedPageIsPersistedEvent $event)
    {
        $this->assetRenderer->collectInlineAssets($event->getRequest());
    }
}
