<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\User;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;
use IvoPetkov\HTML5DOMDocument;

/**
 * Users
 */
class Users
{

    use \BearFramework\App\EventsTrait;

    private $providers = [];

    //private static $newUserCache = null;

    function addProvider(string $id, string $class): \IvoPetkov\BearFrameworkAddons\Users
    {
        $this->providers[$id] = $class;
        return $this;
    }

    function getProviders(): array
    {
        $result = [];
        foreach ($this->providers as $id => $class) {
            $result[] = [
                'id' => $id,
                'class' => $class,
            ];
        }
        return $result;
    }

    function getProvider(string $id): \IvoPetkov\BearFrameworkAddons\Users\ILoginProvider
    {
        if (!isset($this->providers[$id])) {
            throw new \Exception('Invalid provider id (' . $id . ')');
        }
        if (!class_exists($this->providers[$id])) {
            throw new \Exception('Provider class (' . $this->providers[$id] . ') not found');
        }
        $class = $this->providers[$id];
        return new $class();
    }

    function providerExists(string $id): bool
    {
        return isset($this->providers[$id]);
    }

    function make(): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        return new User();
        // $this in defineProperty does not work well
//        if (self::$newUserCache === null) {
//            self::$newUserCache = new User();
//        }
//        $object = clone(self::$newUserCache);
//        return $object;
    }

    function getUser(string $provider, string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        $user = $this->make();
        $user->provider = $provider;
        $user->id = $id;
        return $user;
    }

    function getUserData(string $provider, string $id): ?array
    {
        $app = App::get();
        $rawUserData = null;
        $cacheKey = 'ivopetkov-users-user-data-' . md5($provider) . '-' . md5($id);
        $rawUserData = $app->cache->getValue($cacheKey);
        if ($rawUserData === '-1') {
            return null;
        }
        $foundInCache = $rawUserData !== null;
        if ($rawUserData === null) {
            $rawUserData = $app->data->getValue('users/' . md5($provider) . '/' . md5($id) . '.json');
        }
        $result = null;
        if ($rawUserData !== null) {
            $data = json_decode($rawUserData, true);
            if (is_array($data) && isset($data['provider'], $data['id'], $data['data']) && $data['provider'] === $provider && $data['id'] === $id) {
                $result = $data['data'];
            }
        }
        if (!$foundInCache) {
            $app->cache->set($app->cache->make($cacheKey, $rawUserData === null ? '-1' : $rawUserData));
        }
        return $result;
    }

    function saveUserData(string $provider, string $id, array $data): void
    {
        $app = App::get();
        $dataToSave = [
            'provider' => $provider,
            'id' => $id,
            'data' => $data
        ];
        $app->data->set($app->data->make('users/' . md5($provider) . '/' . md5($id) . '.json', json_encode($dataToSave)));
        $cacheKey = 'ivopetkov-users-user-data-' . md5($provider) . '-' . md5($id);
        $app->cache->delete($cacheKey);
    }

    public function applyUI(\BearFramework\App\Response $response): void
    {
        $app = App::get();

        if ($this->hasEventListeners('beforeApplyUI')) {
            $eventDetails = new \IvoPetkov\BearFrameworkAddons\Users\BeforeApplyUIEventDetails($response);
            $this->dispatchEvent('beforeApplyUI', $eventDetails);
            if ($eventDetails->preventDefault) {
                return;
            }
        }

        $context = $app->contexts->get(__FILE__);
        $providers = $app->users->getProviders();

        $providersPublicData = [];
        foreach ($providers as $providerData) {
            $provider = $app->users->getProvider($providerData['id']);
            $providersPublicData[] = [
                'id' => $providerData['id'],
                'hasLoginButton' => $provider->hasLoginButton(),
                'loginButtonText' => $provider->getLoginButtonText()
            ];
        }

        $initializeData = [
            'currentUser' => Utilities::getCurrentUserPublicData(),
            'providers' => $providersPublicData,
            'pleaseWaitText' => __('ivopetkov.users.pleaseWait'),
            'logoutButtonText' => __('ivopetkov.users.logoutButton'),
            'profileSettingsText' => __('ivopetkov.users.profileSettings')
        ];
        $html = '<html>'
                . '<head>'
                . '<style>'
                . '.ivopetkov-users-badge{cursor:pointer;width:48px;height:48px;position:fixed;z-index:1000000;top:14px;right:14px;border-radius:2px;background-color:black;box-shadow:0 1px 2px 0px rgba(0,0,0,0.2);background-size:cover;background-position:center center;}'
                . '.ivopetkov-users-window{text-align:center;height:100%;overflow:auto;padding:0 10px;display:flex;align-items:center;}'
                . '.ivopetkov-users-login-option-button{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#000;background-color:#fff;border-radius:2px;margin-bottom:15px;padding:16px 14px;display:block;cursor:pointer;min-width:200px;text-align:center;}'
                . '.ivopetkov-users-login-option-button:hover{background-color:#f5f5f5}'
                . '.ivopetkov-users-login-option-button:active{background-color:#eeeeee}'
                . '.ivopetkov-users-loading{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;}'
                . '.ivopetkov-users-account-image{border-radius:2px;background-color:#000;width:250px;height:250px;background-size:cover;background-repeat:no-repeat;background-position:center center;display:inline-block;}'
                . '.ivopetkov-users-account-name{font-family:Arial,Helvetica,sans-serif;font-size:25px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}'
                . '.ivopetkov-users-account-description{font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#fff;margin-top:15px;max-width:350px;word-break:break-all;}'
                . '.ivopetkov-users-account-url{margin-top:15px;max-width:350px;word-break:break-all;}'
                . '.ivopetkov-users-account-url a{font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#fff;}'
                . '.ivopetkov-users-account-logout-button, .ivopetkov-guest-settings-button{cursor:pointer;font-family:Arial,Helvetica,sans-serif;font-size:15px;border-radius:2px;padding:13px 15px;color:#fff;margin-top:25px;display:inline-block;}'
                . '.ivopetkov-users-account-logout-button:hover, .ivopetkov-guest-settings-button:hover{color:#000;background-color:#f5f5f5;};'
                . '.ivopetkov-users-account-logout-button:active, .ivopetkov-guest-settings-button:active{color:#000;background-color:#eeeeee;};'
                . '<style>'
                . '</head>'
                . '<body>'
                . '<component src="js-lightbox"/>'
                . '<script src="' . htmlentities($context->assets->getURL('assets/users.min.js', ['cacheMaxAge' => 999999999, 'version' => 2, 'robotsNoIndex' => true])) . '" async/>'
                . '<script src="' . htmlentities($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1, 'robotsNoIndex' => true])) . '" async/>'
                . '<script>'
                . 'var checkAndExecute=function(b,c){if(b())c();else{var a=function(){b()?(window.clearTimeout(a),c()):window.setTimeout(a,16)};window.setTimeout(a,16)}};'
                . 'checkAndExecute(function(){return typeof ivoPetkov!=="undefined" && typeof ivoPetkov.bearFrameworkAddons!=="undefined" && typeof ivoPetkov.bearFrameworkAddons.users!=="undefined"},function(){ivoPetkov.bearFrameworkAddons.users.initialize(' . json_encode($initializeData) . ');});'
                . '</script>';

        if ($app->currentUser->exists()) {
            $html .= '<component src="file:' . $context->dir . '/components/userBadge.php"/>';
        }

        $html .= '</body>'
                . '</html>';
        $dom = new HTML5DOMDocument();
        $dom->loadHTML($response->content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
        $dom->insertHTML($app->components->process($html));
        $response->content = $dom->saveHTML();
    }

}
