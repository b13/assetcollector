<?php
return [
    'frontend' => [
        'b13/assetcollector' => [
            'target' => \B13\Assetcollector\Middleware\AssetRenderer::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ]
        ]
    ]
];
