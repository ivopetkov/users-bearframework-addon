<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\User;

class CurrentUser extends User
{

    function exists(): bool
    {
        return $this->provider !== null && $this->id !== null;
    }

    function set(string $provider, string $id): void
    {
        $app = App::get();
        $providerObject = $app->users->getProvider($provider);
        $user = $providerObject->makeUser($id);
        $this->provider = $provider;
        $this->id = $id;
        $this->name = $user->name;
        $this->description = $user->description;
        $this->url = $user->url;
        $this->image = $user->image;
    }

    function clear(): void
    {
        $this->provider = null;
        $this->id = null;
        $this->name = null;
        $this->description = null;
        $this->url = null;
        $this->image = null;
    }

    function login(string $provider, string $id): void
    {
        $this->set($provider, $id);
    }

    function logout(): void
    {
        $this->clear();
    }

}
