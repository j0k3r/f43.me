<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@picocss/pico' => [
        'version' => '2.0.6',
    ],
    '@picocss/pico/css/pico.min.css' => [
        'version' => '2.0.6',
        'type' => 'css',
    ],
    'timeago.js' => [
        'version' => '4.0.2',
    ],
    'htmx.org' => [
        'version' => '1.9.12',
    ],
    '@tabler/core' => [
        'version' => '1.0.0-beta21',
    ],
    '@tabler/core/dist/css/tabler.min.css' => [
        'version' => '1.0.0-beta21',
        'type' => 'css',
    ],
];
