<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;

class AnonymousLoginProvider implements ILoginProvider
{

    public function getLoginButtonText(): string
    {
        if (_INTERNAL_IVOPETKOV_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
            return 'Продължи анонимно';
        } else {
            return 'Continue anonymously';
        }
    }

    public function getDescriptionHTML(): string
    {
        return '';
    }

    public function hasLogout(): bool
    {
        return true;
    }

    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $app = App::get();
        $id = md5(uniqid() . rand(0, 999999999));
        $app->currentUser->set('anonymous', $id);
        return new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
    }

    public function makeUser(string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        $app = App::get();
        $user = $app->users->make();
        $user->provider = 'anonymous';
        $user->id = $id;
        if (_INTERNAL_IVOPETKOV_USERS_BEARFRAMEWORK_ADDON_LANGUAGE === 'bg') {
            $user->name = 'Анонимен';
        } else {
            $user->name = 'Anonymous';
        }
        return $user;
    }

}
