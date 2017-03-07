<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;

/**
 * 
 */
class User
{

    /**
     *
     * @var string The user service provider
     */
    public $provider = null;

    /**
     *
     * @var string The id of the user
     */
    public $id = null;

    /**
     *
     * @var string The name of the user
     */
    public $name = null;

    /**
     *
     * @var string A description text for the user
     */
    public $description = null;

    /**
     *
     * @var string The profile URL of the user
     */
    public $url = null;

    /**
     *
     * @var string The image of the user
     */
    public $image = null;

    function getImageUrl(int $size)
    {
        $app = App::get();
        $context = $app->context->get(__FILE__);
        return $context->assets->getUrl('assets/users/' . $this->provider . '/' . $this->id, ['width' => $size, 'height' => $size, 'cacheMaxAge' => 3600, 'robotsNoIndex' => true]) . '?v=' . md5($this->image);
    }

}
