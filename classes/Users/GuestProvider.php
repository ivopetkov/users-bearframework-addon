<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

/**
 * 
 */
class GuestProvider extends Provider
{

    /**
     * 
     */
    public function __construct(string $id, array $options = [])
    {
        parent::__construct($id, $options);

        $this->hasLogin = true;
        $this->loginText = __('ivopetkov.users.guest.buttons.login');
        $this->hasLogout = true;
        $this->logoutConfirmText = __('ivopetkov.users.guest.logoutConfirm');
        $this->imageMaxAge = 999999999;
        if (!isset($this->options['profileFields'])) {
            $this->options['profileFields'] = ['image', 'name', 'website', 'description'];
        }
    }

    /**
     * 
     * @param \IvoPetkov\BearFrameworkAddons\Users\LoginContext $context
     * @return \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
     */
    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse
    {
        $app = App::get();
        if (!$app->rateLimiter->logIP('ivopetkov-users-guest-signup', ['10/m', '50/h'])) {
            $response = new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
            $response->jsCode .= 'alert("' . __('ivopetkov.users.tryAgainLater') . '")';
            return $response;
        }
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
        $userData = $app->users->getUserData($this->id, $id);
        $properties = Utilities::getProfileDataFromUserData($this, $userData);
        if (!isset($properties['name'])) {
            $properties['name'] = __('ivopetkov.users.guest');
        }
        return $properties;
    }
}
