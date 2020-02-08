<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

class LoginProvider
{

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
     * @var bool 
     */
    public $hasSettings = false;

    /**
     *
     * @var int 
     */
    public $imageMaxAge = 86400;

    /**
     * 
     * @return string
     * @throws \Exception
     */
    public function getSettingsForm(): string
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
    public function getUserProperties(string $id): array
    {
        return [];
    }
}
