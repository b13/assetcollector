<?php

namespace B13\Assetcollector\Tests\Functional\Functional;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SvgViewHelperCachedTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/assetcollector'];
    protected array $coreExtensionsToLoad = ['core', 'frontend'];
    protected array $pathsToLinkInTestInstance = ['typo3conf/ext/assetcollector/Build/sites' => 'typo3conf/sites'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function scriptTagForInlineCssIsRendered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SvgViewHelper.csv');
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $expected = '<svg class="tx_assetcollector"';
        $notExected = '</svg><svg class="tx_assetcollector"';
        $bodyUncached = (string)$response->getBody();
        self::assertStringContainsString($expected, $bodyUncached);
        self::assertStringNotContainsString($notExected, $bodyUncached);
        // cached
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $bodyCached = (string)$response->getBody();
        self::assertSame($bodyUncached, $bodyCached);
    }

    /**
     * @test
     */
    public function scriptTagForInlineCssIsRenderedForInt(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SvgViewHelperInt.csv');
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $expected = '<svg class="tx_assetcollector"';
        $notExected = '</svg><svg class="tx_assetcollector"';
        $bodyUncached = (string)$response->getBody();
        self::assertStringContainsString($expected, $bodyUncached);
        self::assertStringNotContainsString($notExected, $bodyUncached);
        // cached
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $bodyCached = (string)$response->getBody();
        self::assertSame($bodyUncached, $bodyCached);
    }
}
