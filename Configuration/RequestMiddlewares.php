<?php
return [
    'frontend' => [
        'b13/assetcollector' => [
            'target' => \B13\Assetcollector\Middleware\AssetRenderer::class,
            'before' => [
                'typo3/cms-frontend/content-length-headers'
            ],
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ]
        ]
    ]
];
