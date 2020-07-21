<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;

class UsernameProvider extends Provider
{

    public function __construct()
    {
        $this->hasLogin = true;
        $this->hasLogout = true;
        $this->screens = [
            ['id' => 'changepassword', 'name' => __('ivopetkov.users.username.changePasswordButton'), 'showInProfile' => true],
            ['id' => 'signup', 'name' => __('ivopetkov.users.username.signUpButton')],
            ['id' => 'login', 'name' => __('ivopetkov.users.username.loginButton')]
        ];
        $this->imageMaxAge = 999999999;
    }

    /**
     * 
     * @return string
     */
    public function getScreenContent(string $id): string
    {
        $app = App::get();
        $context = $app->contexts->get();
        if (array_search($id, ['signup', 'login']) !== false) {
            return $app->components->process('<component src="form" filename="' . $context->dir . '/components/username-' . $id . '-form.php" providerID="' . htmlentities($this->id) . '"/>');
        } elseif ($id === 'changepassword') {
            if ($app->currentUser->exists()) {
                return $app->components->process('<component src="form" filename="' . $context->dir . '/components/username-' . $id . '-form.php" providerID="' . htmlentities($this->id) . '"/>');
            }
        }
        return '';
    }

    public function getProfileData(string $id): array
    {
        $app = App::get();
        $properties = [];
        $userData = $app->users->getUserData($this->id, $id);
        if (is_array($userData)) {
            $properties['name'] = $userData['u'];
        } else {
            $properties['name'] = 'Anonymous'; // just in case it's missing
        }
        return $properties;
    }
}
