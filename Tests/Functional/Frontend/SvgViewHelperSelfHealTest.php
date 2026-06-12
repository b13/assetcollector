<?php

declare(strict_types=1);

namespace B13\Assetcollector\Tests\Functional\Frontend;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SvgViewHelperSelfHealTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/assetcollector'];
    protected array $coreExtensionsToLoad = ['core', 'frontend'];
    protected array $pathsToLinkInTestInstance = ['typo3conf/ext/assetcollector/Build/sites' => 'typo3conf/sites'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                    'tx_assetcollector' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                    'tx_assetcollector_registry' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    /**
     * Reproduces the situation where the page cache survives but the
     * "tx_assetcollector" cache that holds the collected SVG files is gone (e.g.
     * desynced because it sits on a different backend, or was cleared on its
     * own). The cached page must still be delivered with the full inline SVG
     * sprite instead of silently losing all its icons.
     */
    #[Test]
    public function inlineSvgSpriteIsRebuiltForCachedPageWhenAssetCacheIsLost(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SvgViewHelper.csv');

        // 1. uncached request: the sprite is rendered and the icon registry gets persisted
        $bodyUncached = (string)$this->executeFrontendSubRequest(new InternalRequest('http://localhost/'))->getBody();
        self::assertStringContainsString('<svg class="tx_assetcollector"', $bodyUncached);
        self::assertStringContainsString('<symbol id="icon-Extension"', $bodyUncached);

        // 2. the page cache survives, but the collected-asset cache is lost
        GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_assetcollector')->flush();

        // 3. cached request: the sprite must be rebuilt from the persisted registry
        $bodyCached = (string)$this->executeFrontendSubRequest(new InternalRequest('http://localhost/'))->getBody();
        self::assertStringContainsString('<symbol id="icon-Extension"', $bodyCached);
        self::assertSame($bodyUncached, $bodyCached);
    }
}
