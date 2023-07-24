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

class UsernameProvider extends Provider
{

    /**
     * 
     */
    public function __construct()
    {
        $this->hasLogin = true;
        $this->loginText = __('ivopetkov.users.username.buttons.loginWithUsername');
        $this->hasLogout = true;
        $this->screens[] = ['id' => 'change-password', 'name' => __('ivopetkov.users.username.buttons.changePassword'), 'showInProfile' => true];
        $this->screens[] = ['id' => 'signup', 'name' => __('ivopetkov.users.username.buttons.signUp')];
        $this->screens[] = ['id' => 'login', 'name' => __('ivopetkov.users.username.buttons.login')];
        $this->imageMaxAge = 999999999;
    }

    /**
     * @param string $id
     * @param array $data 
     * @return string|array 'content' or ['title'=>'', 'content'=>'', 'width'=>'']
     * @throws \Exception
     */
    public function getScreenContent(string $id, array $data = [])
    {
        $app = App::get();
        $context = $app->contexts->get();
        if (
            (array_search($id, ['signup', 'login']) !== false) ||
            (array_search($id, ['change-password']) !== false && $app->currentUser->exists() && $app->currentUser->provider === $this->id)
        ) {
            $content = $app->components->process('<component src="form" filename="' . $context->dir . '/components/username-' . $id . '-form.php" providerID="' . htmlentities($this->id) . '"/>');
            $titles = [
                'change-password' => __('ivopetkov.users.username.changePasswordTitle'),
                'signup' => __('ivopetkov.users.username.signUpTitle'),
                'login' => __('ivopetkov.users.username.loginTitle'),
            ];
            return [
                'width' => '350px',
                'title' => $titles[$id],
                'content' => $content
            ];
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
        $response = new \IvoPetkov\BearFrameworkAddons\Users\LoginResponse();
        $response->jsCode = "clientPackages.get('users').then(function(users){users.openProviderLogin('" . $context->providerID . "');});";
        return $response;
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
        if (!isset($properties['name']) && is_array($userData)) {
            $properties['name'] = $userData['u'];
        }
        if (!isset($properties['name'])) {
            $properties['name'] = __('ivopetkov.users.anonymous'); // just in case it's missing
        }
        return $properties;
    }

    /**
     * 
     * @param string $providerID
     * @param string $username
     * @return boolean
     */
    static function usernameExists(string $providerID, string $username): bool
    {
        return self::exists($providerID, self::getUserID($username));
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return boolean
     */
    static function exists(string $providerID, string $userID): bool
    {
        $app = App::get();
        return $app->users->getUserData($providerID, $userID) !== null;
    }

    /**
     * s
     * @param string $providerID
     * @param string $username
     * @param string $password
     * @return string
     */
    static function create(string $providerID, string $username, string $password): string
    {
        $app = App::get();
        $userID = self::getUserID($username);
        if ($app->users->getUserData($providerID, $userID) !== null) {
            throw new \Exception('Username exists (' . $username . ')!');
        }
        $app->users->saveUserData($providerID, $userID, [
            'u' => $username,
            'd' => time(),
            'p' => self::hashPassword($password)
        ]);
        return $userID;
    }

    /**
     * 
     * @param string $providerID
     * @param string $username
     * @param string $password
     * @return string|null Returns the user ID if password is valid
     */
    static function checkUsernamePassword(string $providerID, string $username, string $password): ?string
    {
        return self::checkPassword($providerID, self::getUserID($username), $password);
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $password
     * @return boolean
     */
    static function checkPassword(string $providerID, string $userID, string $password): bool
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null && password_verify($password, $userData['p'])) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @param string $password
     * @return void
     */
    static function setPassword(string $providerID, string $userID, string $password): void
    {
        $app = App::get();
        $userData = $app->users->getUserData($providerID, $userID);
        if ($userData !== null) {
            $userData['p'] = self::hashPassword($password);
            $app->users->saveUserData($providerID, $userID, $userData);
        }
    }

    /**
     * 
     * @param string $password
     * @return string
     */
    static private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 
     * @param string $username
     * @return string
     */
    static private function getUserID(string $username): string
    {
        return md5($username);
    }
}
