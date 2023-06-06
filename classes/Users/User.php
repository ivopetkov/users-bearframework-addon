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
 * @property-read string|null $description A description text for the user
 * @property-read string|null $url The profile URL of the user
 * @property-read string|null $image The image of the user
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

    /**
     * 
     * @var array
     */
    private $cache = [];

    /**
     * 
     */
    public function __construct()
    {
        $this
            ->defineProperty('name', [
                'get' => function () {
                    $value = (string)$this->getProfileData('name');
                    if (strlen($value) === 0) {
                        return __('ivopetkov.users.anonymous');
                    }
                    return $value;
                },
                'readonly' => true
            ])
            ->defineProperty('description', [
                'get' => function () {
                    return $this->getProfileData('description');
                },
                'readonly' => true
            ])
            ->defineProperty('url', [
                'get' => function () {
                    return $this->getProfileData('url');
                },
                'readonly' => true
            ])
            ->defineProperty('image', [
                'get' => function () {
                    return $this->getProfileData('image');
                },
                'readonly' => true
            ]);
    }

    /**
     * 
     * @param string $property
     * @return void
     */
    private function getProfileData(string $property)
    {
        if ($this->provider === null || $this->id === null) {
            return null;
        }
        $app = App::get();
        if (!$app->users->providerExists($this->provider)) {
            return null;
        }
        $cacheKey = md5($this->provider) . md5($this->id);
        if (!isset($this->cache[$cacheKey])) {
            $providerObject = $app->users->getProvider($this->provider);
            $this->cache[$cacheKey] = $providerObject !== null ? $providerObject->getProfileData($this->id) : null;
        }
        return isset($this->cache[$cacheKey][$property]) ? $this->cache[$cacheKey][$property] : null;
    }

    /**
     * 
     * @param int $size
     * @return string
     */
    public function getImageUrl(int $size): string
    {
        $app = App::get();
        $context = $app->contexts->get(__DIR__);
        $provider = $app->users->getProvider($this->provider);
        $cacheMaxAge = $provider !== null ? (int) $provider->imageMaxAge : 99999;
        return $context->assets->getURL('assets/u/' . $this->provider . '/' . $this->id, ['width' => $size, 'height' => $size, 'cacheMaxAge' => $cacheMaxAge, 'robotsNoIndex' => true, 'version' => md5((string)$this->image)]);
    }
}
