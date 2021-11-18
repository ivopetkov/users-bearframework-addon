<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('ivopetkov/users-bearframework-addon', __DIR__, [
    'require' => [
        'bearframework/localization-addon',
        'ivopetkov/js-lightbox-bearframework-addon',
        'ivopetkov/server-requests-bearframework-addon',
        'ivopetkov/form-bearframework-addon',
        'ivopetkov/html-server-components-bearframework-addon',
        'ivopetkov/client-packages-bearframework-addon',
        'ivopetkov/form-elements-bearframework-addon',
        'ivopetkov/html5-dom-document-js-bearframework-addon'
    ]
]);
