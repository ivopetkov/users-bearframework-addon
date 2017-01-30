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
        $this->image = $user->image;
        $this->url = $user->url;
    }

    function clear(): void
    {
        $this->provider = null;
        $this->id = null;
        $this->name = null;
        $this->url = null;
        $this->image = null;
    }

}
