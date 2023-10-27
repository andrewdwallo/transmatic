<?php

use Wallo\Transmatic\Services\Translators\AwsTranslate;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Translation Service
    |--------------------------------------------------------------------------
    |
    |
    */

    'translator' => AwsTranslate::class,

    /*
    |--------------------------------------------------------------------------
    | Source Locale
    |--------------------------------------------------------------------------
    |
    | The source locale to be used for all translations. This is the language
    | code from which all translations to other languages will be made. This
    | must be the language that your application is written in. Typically, this
    | would match the "locale" setting in your "config/app.php" file.
    |
    */

    'source_locale' => env('TRANSMATIC_SOURCE_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Translation Storage
    |--------------------------------------------------------------------------
    |
    | The mechanism used for storing translations. You can choose between
    | either storing translations in the cache or in JSON language files.
    |
    | Supported: "cache", "file"
    |
    */

    'storage' => env('TRANSMATIC_STORAGE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for caching translations. The
    | "duration" specifies the number of minutes that translations should be
    | cached for. This can help improve performance by reducing redundant
    | translation operations. The "key" is the name of the base cache key that
    | will be used to store the translations. The locale will be appended to
    | this key.
    |
    */

    'cache' => [
        'duration' => env('TRANSMATIC_CACHE_DURATION', 60 * 24 * 30),
        'key' => env('TRANSMATIC_CACHE_KEY', 'translations'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for storing translations in JSON. The
    | "path" specifies the directory where the JSON language files will be
    | stored. The translations for each locale will have a corresponding file
    | within this directory. The file name will be the language code for the
    | locale. The default is set to "resources/data" to avoid issues with
    | Laravel's auto-reload behavior when files are written to standard "lang"
    | directories. Feel free to change this path as needed.
    |
    */

    'file' => [
        'path' => env('TRANSMATIC_FILE_PATH', 'resources/data/lang'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Batching Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the queue and chunk size for the batch of jobs
    | that are used to translate text. The "queue" is the name of the queue
    | that the batch will be dispatched to. The "chunk_size" defines the number
    | of text strings each job in the batch will handle. The "allow_failures"
    | option determines whether the batch should be marked as "cancelled" if a
    | job within the batch fails. Setting this to "true" allows the batch to
    | continue running even if a job fails.
    |
    */

    'batching' => [
        'queue' => env('TRANSMATIC_BATCHING_QUEUE', 'translations'),
        'chunk_size' => env('TRANSMATIC_BATCHING_CHUNK_SIZE', 50),
        'allow_failures' => env('TRANSMATIC_BATCHING_ALLOW_FAILURES', true),
    ],
];
