<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('ivopetkov/users-bearframework-addon', __DIR__ , [
    'require' => [
        'ivopetkov/js-lightbox-bearframework-addon',
        'ivopetkov/server-requests-bearframework-addon'
    ]
]);
