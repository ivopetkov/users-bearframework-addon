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
     * @var array
     */
    public $options = [];

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
     * @var string
     */
    public $logoutConfirmText = '';

    /**
     *
     * @var int 
     */
    public $imageMaxAge = 86400;

    /**
     *
     * @var array 
     */
    public $screens = [];

    /**
     * 
     * @param string $id
     * @param array $options
     */
    public function __construct(string $id, array $options = [])
    {
        $this->id = $id;
        $this->options = $options;
    }

    /**
     * @param string $id
     * @param array $data 
     * @return string|array 'content' or ['title'=>'', 'content'=>'', 'width'=>'']
     * @throws \Exception
     */
    public function getScreenContent(string $id, array $data = [])
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

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return array|null
     */
    public function handleCallback(string $providerID, string $key): ?array
    {
        return null;
    }
}
