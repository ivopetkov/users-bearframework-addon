<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
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

    public function getUserProperties(string $id): array
    {
        $app = App::get();
        $properties = [];
        $userData = $app->users->getUserData('guest', $id);
        if (empty($userData)) {
            $userData = [];
        }
        $properties['name'] = empty($userData['name']) ? __('ivopetkov.users.guest') : $userData['name'];
        if (!empty($userData['image'])) {
            $properties['image'] = $app->data->getFilename('users/' . md5('guest') . '-files/' . $userData['image']);
        }
        if (!empty($userData['website'])) {
            $properties['url'] = $userData['website'];
        }
        if (!empty($userData['description'])) {
            $properties['description'] = $userData['description'];
        }
        return $properties;
    }

}
