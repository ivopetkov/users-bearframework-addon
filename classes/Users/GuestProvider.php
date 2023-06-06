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
class GuestProvider extends Provider
{

    /**
     * 
     */
    public function __construct()
    {
        $this->hasLogin = true;
        $this->loginText = __('ivopetkov.users.guest.loginButton');
        $this->hasLogout = true;
        $this->logoutConfirmText = __('ivopetkov.users.guest.logoutConfirm');
        $this->screens = [
            ['id' => 'settings', 'name' => __('ivopetkov.users.guest.settingsButton'), 'showInProfile' => true]
        ];
        $this->imageMaxAge = 999999999;
    }


    public function getScreenContent(string $id)
    {
        if ($id === 'settings') {
            $app = App::get();
            $context = $app->contexts->get();
            if ($app->currentUser->exists() && $app->currentUser->provider === $this->id) {
                $content = $app->components->process('<component src="form" filename="' . $context->dir . '/components/guest-settings-form.php"/>');
                return ['title' => __('ivopetkov.users.guest.settingsButton'), 'content' => $content, 'width' => '300px'];
            }
        }
        return '';
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
        $app->users->dispatchSignupEvent($this->id, $id);
        $app->currentUser->login($this->id, $id);
        $app->users->dispatchLoginEvent($this->id, $id);
        return parent::login($context);
    }

    /**
     * 
     * @param string $id
     * @return array
     */
    public function getProfileData(string $id): array
    {
        $app = App::get();
        $properties = [];
        $userData = $app->users->getUserData($this->id, $id);
        if (empty($userData)) {
            $userData = [];
        }
        $properties['name'] = isset($userData['name']) && strlen($userData['name']) > 0 ? $userData['name'] : __('ivopetkov.users.guest');
        if (isset($userData['image']) && strlen($userData['image']) > 0) {
            $properties['image'] = $app->users->getUserFilePath($this->id, $userData['image']);
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
