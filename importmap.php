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
    'login' => [
        'path' => './assets/login.js',
        'entrypoint' => true,
    ],
    'modal' => [
        'path' => './assets/modal.js',
        'entrypoint' => true,
    ],
    'theme-switcher' => [
        'path' => './assets/theme-switcher.js',
        'entrypoint' => true,
    ],
    'timeago.js' => [
        'version' => '4.0.2',
    ],
    '@picocss/pico/css/pico.css' => [
        'version' => '2.1.1',
        'type' => 'css',
    ],
    'es-module-shims' => [
        'version' => '2.8.0',
    ],
];
