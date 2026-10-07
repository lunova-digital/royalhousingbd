<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules Directory
    |--------------------------------------------------------------------------
    |
    | This is the directory, relative to the application's base path, that
    | holds the application's own modules. Every module, whether it lives
    | here or is installed via Composer, is listed in bootstrap/modules.
    |
    */

    'path' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Modules Namespace
    |--------------------------------------------------------------------------
    |
    | The modules directory is autoloaded beneath this root namespace. For
    | example, the "Blog" module lives in the "Modules\Blog" namespace, so
    | "Modules\Blog\Models\Post" is found at Modules/Blog/Models/Post.php.
    |
    */

    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Routes
    |--------------------------------------------------------------------------
    |
    | These attributes are applied to the route files of every module. A
    | module's "routes/web.php" file is loaded within the "web" group and
    | its "routes/api.php" file is loaded within the "api" group below.
    |
    | The only API here is the admin's JSON, and the admin signs in with the
    | session cookie, so the "api" group runs the "web" middleware too: the
    | session, and CSRF through the X-XSRF-TOKEN header (resources/js/lib/api.ts).
    |
    */

    'routes' => [

        'web' => [
            'middleware' => ['web'],
        ],

        'api' => [
            'prefix' => 'api',
            'middleware' => ['web'],
        ],

    ],

];
