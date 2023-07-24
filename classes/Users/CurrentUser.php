<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

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
}
