<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;

class GuestLoginProvider implements ILoginProvider
{

    public function hasLoginButton(): bool
    {
        return true;
    }

    public function getLoginButtonText(): string
    {
        return __('ivopetkov.users.loginAsGuest');
    }

    public function hasLogoutButton(): bool
    {
        return true;
    }

    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $app = App::get();
        $id = md5(uniqid() . rand(0, 999999999));
        $app->currentUser->login('guest', $id);
        return new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
    }

    public function makeUser(string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        $app = App::get();
        $user = $app->users->make();
        $user->provider = 'guest';
        $user->id = $id;
        $userData = $app->users->getUserData($user->provider, $user->id);
        if (empty($userData)) {
            $userData = [];
        }
        $user->name = empty($userData['name']) ? __('ivopetkov.users.guest') : $userData['name'];
        if (!empty($userData['image'])) {
            $user->image = $app->data->getFilename('users/' . md5('guest') . '-files/' . $userData['image']);
        }
        if (!empty($userData['website'])) {
            $user->url = $userData['website'];
        }
        return $user;
    }

}
