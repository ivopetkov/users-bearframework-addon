<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;
use IvoPetkov\BearFrameworkAddons\Users\User;

/**
 * 
 */
class CurrentUser extends User
{

    /**
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return $this->provider !== null && $this->id !== null;
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @param boolean $remember
     * @return void
     */
    public function login(string $providerID, string $id, bool $remember = false): void
    {
        $this->set($providerID, $id);
        Utilities::$currentUserCookieAction = 'login';
        if ($remember) {
            Utilities::$currentUserCookieAction = 'login-remember';
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @return void
     */
    public function set(string $providerID, string $id): void
    {
        $this->provider = $providerID;
        $this->id = $id;
    }

    /**
     * 
     * @return void
     */
    public function logout(): void
    {
        $this->provider = null;
        $this->id = null;
        Utilities::$currentUserCookieAction = 'logout';
    }

    /**
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setData(string $key, $value): void
    {
        if ($this->exists()) {
            $app = App::get();
            $app->users->setCustomUserData($this->provider, $this->id, $key, $value);
        }
    }

    /**
     * 
     * @param string $key
     * @return mixed
     */
    public function getData(string $key)
    {
        if ($this->exists()) {
            $app = App::get();
            return $app->users->getCustomUserData($this->provider, $this->id, $key);
        }
        return null;
    }

    /**
     * 
     * @return \IvoPetkov\BearFrameworkAddons\Users\Provider|null
     */
    public function getProvider(): ?\IvoPetkov\BearFrameworkAddons\Users\Provider
    {
        if ($this->exists()) {
            $app = App::get();
            return $app->users->getProvider($this->provider, $this->id);
        }
        return null;
    }
}
