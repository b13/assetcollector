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

class InlineCssWithUncachedElementTest extends FunctionalTestCase
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
                        'options' => ['compression' => 0],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function scriptTagForInlineCssIsRenderedUncached(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineCssWithUncachedElement.csv');
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $expected = '<style class="tx_assetcollector">h1{color:red;}';
        $body = (string)$response->getBody();
        self::assertStringContainsString($expected, $body);
        // cached
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $body = (string)$response->getBody();
        self::assertStringContainsString($expected, $body);
    }
}
