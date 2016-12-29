<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->getContext(__FILE__);

$context->classes->add('IvoPetkov\BearFramework\Addons\User', 'classes/User.php');
$context->classes->add('IvoPetkov\BearFramework\Addons\Users', 'classes/Users.php');
