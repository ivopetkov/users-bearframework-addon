<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

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
     * @param string $provider
     * @param string $id
     * @return void
     */
    public function login(string $provider, string $id): void
    {
        $this->provider = $provider;
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
    }
}
