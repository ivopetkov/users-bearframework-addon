<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users\Internal;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Provider;

/**
 * 
 */
class Utilities
{


    static $providerRoutePrefix = '/-u/';

    static $currentUserCookieAction = null;

    /**
     * 
     * @param string $providerID
     * @param string $path
     * @return string
     */
    static function getCallbackURL(string $providerID, string $path): string
    {
        $app = App::get();
        return $app->urls->get(self::$providerRoutePrefix . self::getProviderCallbackHash($providerID) . '/' . $path);
    }

    /**
     * 
     * @param string $path
     * @return App\Response
     */
    static function handleCallbackRequest(string $path): App\Response
    {
        $app = App::get();
        $pathParts = explode('/', $path, 2);
        $providers = $app->users->getProviders();
        $providerID = null;
        foreach ($providers as $providerData) {
            if (self::getProviderCallbackHash($providerData['id']) === $pathParts[0]) {
                $providerID = $providerData['id'];
            }
        }

        $provider = $app->users->getProvider($providerID);
        if ($provider !== null) {
            $callbackResult = $provider->handleCallback($providerID, $pathParts[1]);
            if (is_array($callbackResult) && isset($callbackResult['redirectURL'])) {
                return new App\Response\TemporaryRedirect($callbackResult['redirectURL']);
            }
        }
        return new App\Response\NotFound();
    }

    /**
     * 
     * @param string $providerID
     * @return string
     */
    static function getProviderCallbackHash(string $providerID): string
    {
        return base_convert(substr(md5($providerID), 0, 8), 16, 26);
    }

    /**
     * 
     * @param mixed $data
     * @return string
     */
    static function getFormSubmitResult($data): string
    {
        if (is_array($data)) {
            if (isset($data['redirectURL'])) {
                return 'u:' . $data['redirectURL'];
            }
            if (isset($data['jsCode'])) {
                return 'j:' . $data['jsCode'];
            }
        }
        return '';
    }

    /**
     * 
     * @return string
     */
    static function getFormSubmitResultHandlerJsCode(): string
    {
        $js = 'var r=event.result;if(r.length>0){' .
            'if(r.substring(0,2)===\'u:\'){clientPackages.get(\'users\').then(function(users){users._closeAllWindows();users._showLoading();});window.location=r.substring(2);}' .
            'if(r.substring(0,2)===\'j:\'){(new Function(r.substring(2)))();}' .
            '}';
        return $js;
    }

    /**
     * 
     * @param integer $length
     * @return string
     */
    static function generateKey(int $length): string
    {
        $result = '';
        $s = "qwertyuiopasdfghjklzxcvbnm1234567890";
        $n = strlen($s);
        while ($length > 0) {
            $i = rand(0, $n - 1);
            $result .= substr($s, $i, 1);
            $length--;
        }
        return $result;
    }

    /**
     * 
     * @param string $key
     * @return string
     */
    static function getSessionDataKey(string $key): string
    {
        $keyMD5 = md5($key);
        return '.temp/users/sessions/' . substr(md5($keyMD5), 0, 2) . '/' . substr(md5($keyMD5), 2);
    }

    /**
     * 
     * @param string $key
     * @param mixed $data
     * @return void
     */
    static function setSessionData(string $key, $data): void
    {
        $app = App::get();
        $app->data->setValue(self::getSessionDataKey($key), json_encode($data));
    }

    /**
     * 
     * @param string $key
     * @return mixed
     */
    static function getSessionData(string $key)
    {
        $app = App::get();
        $result = $app->data->getValue(self::getSessionDataKey($key));
        if ($result !== null) {
            return json_decode($result, true);
        }
        return null;
    }

    /**
     * 
     * @return string
     */
    static function generateSessionKey(): string
    {
        for ($i = 0; $i < 100; $i++) {
            $key = self::generateKey(66);
            if (self::getSessionData($key) === null) {
                return $key;
            }
        }
        throw new \Exception('Too many retries!');
    }

    /**
     * 
     * @param Provider $provider
     * @param mixed $userData
     * @return array
     */
    static function getProfileDataFromUserData(Provider $provider, $userData): array
    {
        $app = App::get();
        $result = [];
        if (is_array($userData)) {
            if (isset($provider->options['profileFields'])) {
                $profileFields = $provider->options['profileFields'];
                if (array_search('name', $profileFields) !== false && isset($userData['name']) && strlen($userData['name']) > 0) {
                    $result['name'] = $userData['name'];
                }
                if (array_search('image', $profileFields) !== false && isset($userData['image']) && strlen($userData['image']) > 0) {
                    $result['image'] = $app->users->getUserFilePath($provider->id, $userData['image']);
                }
                if (array_search('website', $profileFields) !== false && isset($userData['website'])) {
                    $result['url'] = $userData['website'];
                }
                if (array_search('description', $profileFields) !== false && isset($userData['description'])) {
                    $result['description'] = $userData['description'];
                }
            }
        }
        return $result;
    }

    /**
     * 
     * @return string|null
     */
    static function getBadgeHTML(): ?string
    {
        $app = App::get();
        $context = $app->contexts->get(__DIR__);
        if ($app->currentUser->exists()) {
            return $app->clientPackages->process($app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>'));
        }
        return null;
    }
}
