<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;

/**
 * 
 */
class GuestLoginProvider extends LoginProvider
{

    /**
     * 
     */
    public function __construct()
    {
        $this->hasLogin = true;
        $this->loginText = __('ivopetkov.users.loginAsGuest');
        $this->hasLogout = true;
        $this->hasSettings = true;
    }

    /**
     * 
     * @return string
     */
    public function getSettingsForm(): string
    {
        $app = App::get();
        $context = $app->contexts->get();
        return $app->components->process('<component src="form" filename="' . $context->dir . '/components/guest-settings-form.php"/>');
    }

    /**
     * 
     * @param \IvoPetkov\BearFrameworkAddons\Users\LoginContext $context
     * @return \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
     */
    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $app = App::get();
        $id = md5(uniqid() . rand(0, 999999999));
        $app->currentUser->login('guest', $id);
        return parent::login($context);
    }

    /**
     * 
     * @param string $id
     * @return array
     */
    public function getUserProperties(string $id): array
    {
        $app = App::get();
        $properties = [];
        $userData = $app->users->getUserData('guest', $id);
        if (empty($userData)) {
            $userData = [];
        }
        $properties['name'] = isset($userData['name']) && strlen($userData['name']) > 0 ? $userData['name'] : __('ivopetkov.users.guest');
        if (isset($userData['image']) && strlen($userData['image']) > 0) {
            $properties['image'] = $app->users->getUserFilePath('guest', $userData['image']);
        }
        if (isset($userData['website'])) {
            $properties['url'] = $userData['website'];
        }
        if (isset($userData['description'])) {
            $properties['description'] = $userData['description'];
        }
        return $properties;
    }

}
