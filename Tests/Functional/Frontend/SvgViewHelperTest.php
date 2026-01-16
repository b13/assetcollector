<?php

namespace B13\Assetcollector\Tests\Functional\Functional;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SvgViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/assetcollector'];
    protected array $coreExtensionsToLoad = ['core', 'frontend'];
    protected array $pathsToLinkInTestInstance = ['typo3conf/ext/assetcollector/Build/sites' => 'typo3conf/sites'];

    #[Test]
    public function scriptTagForInlineCssIsRendered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SvgViewHelper.csv');
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $expected = '<svg><use xlink:href="#icon-Extension"></use></svg>';
        $body = (string)$response->getBody();
        self::assertStringContainsString($expected, $body);
        $expected = '<svg class="tx_assetcollector"';
        self::assertStringContainsString($expected, $body);
        $expected = '<rect y="0.3" class="st0" width="256" height="256"></rect>';
        self::assertStringContainsString($expected, $body);
    }

    #[Test]
    public function scriptTagForInlineCssIsRenderedWithNoBodyTag(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SvgViewHelperWithNoBodyTag.csv');
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/'));
        $expected = '<svg><use xlink:href="#icon-Extension"></use></svg>';
        $body = (string)$response->getBody();
        self::assertStringContainsString($expected, $body);
        $expected = '<svg class="tx_assetcollector"';
        self::assertStringContainsString($expected, $body);
        $expected = '<rect y="0.3" class="st0" width="256" height="256"></rect>';
        self::assertStringContainsString($expected, $body);
    }
}
