<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;

/**
 * @property-read string $name The name of the user
 * @property-read string $description A description text for the user
 * @property-read string $url The profile URL of the user
 * @property-read string $image The image of the user
 */
class User
{

    use \IvoPetkov\DataObjectTrait;

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

    function __construct()
    {
        $cache = [];
        $getUserData = function($property) use (&$cache) {
            $app = App::get();
            if (strlen($this->provider) === 0 || strlen($this->id) === 0) {
                return null;
            }
            if (!$app->users->providerExists($this->provider)) {
                return null;
            }
            $cacheKey = md5($this->provider) . md5($this->id);
            if (!isset($cache[$cacheKey])) {
                $providerObject = $app->users->getProvider($this->provider);
                $cache[$cacheKey] = $providerObject->getUserProperties($this->id);
            }
            return isset($cache[$cacheKey][$property]) ? $cache[$cacheKey][$property] : null;
        };
        $this->defineProperty('name', [
            'get' => function() use (&$getUserData) {
                $value = $getUserData('name');
                if (strlen($value) === 0) {
                    return __('ivopetkov.users.anonymous');
                };
                return $value;
            },
            'readonly' => true
        ]);
        $this->defineProperty('description', [
            'get' => function() use (&$getUserData) {
                return $getUserData('description');
            },
            'readonly' => true
        ]);
        $this->defineProperty('url', [
            'get' => function() use (&$getUserData) {
                return $getUserData('url');
            },
            'readonly' => true
        ]);
        $this->defineProperty('image', [
            'get' => function() use (&$getUserData) {
                return $getUserData('image');
            },
            'readonly' => true
        ]);
    }

    function getImageUrl(int $size)
    {
        $app = App::get();
        $context = $app->context->get(__FILE__);
        return $context->assets->getUrl('assets/users/' . $this->provider . '/' . $this->id, ['width' => $size, 'height' => $size, 'cacheMaxAge' => 86400, 'robotsNoIndex' => true, 'version' => md5($this->image)]);
    }

}
