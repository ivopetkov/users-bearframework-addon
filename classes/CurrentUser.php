<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use IvoPetkov\BearFrameworkAddons\Users\User;

class CurrentUser extends User
{

    function exists(): bool
    {
        return $this->provider !== null && $this->id !== null;
    }

    function login(string $provider, string $id): void
    {
        $this->provider = $provider;
        $this->id = $id;
    }

    function logout(): void
    {
        $this->provider = null;
        $this->id = null;
    }

}
