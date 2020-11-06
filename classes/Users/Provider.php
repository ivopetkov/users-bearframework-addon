<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

class Provider
{

    /**
     *
     * @var string 
     */
    public $id = null;

    /**
     *
     * @var bool 
     */
    public $hasLogin = false;

    /**
     *
     * @var string 
     */
    public $loginText = '';

    /**
     *
     * @var bool 
     */
    public $hasLogout = false;

    /**
     *
     * @var array 
     */
    public $screens = [];

    /**
     *
     * @var int 
     */
    public $imageMaxAge = 86400;

    /**
     * 
     * @var array
     */
    public $options = [];

    /**
     * 
     * @return string
     * @throws \Exception
     */
    public function getScreenContent(string $id): string
    {
        throw new \Exception('Not implemented!');
    }

    /**
     * 
     * @param \IvoPetkov\BearFrameworkAddons\Users\LoginContext $context
     * @return \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
     */
    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        return new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
    }

    /**
     * 
     * @param string $id
     * @return array
     */
    public function getProfileData(string $id): array
    {
        return [];
    }
}
