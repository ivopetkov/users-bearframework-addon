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
use IvoPetkov\HTML5DOMDocument;

/**
 * Users
 * @event \IvoPetkov\BearFrameworkAddons\Users\BeforeApplyUIEventDetails beforeApplyUI
 * @event \IvoPetkov\BearFrameworkAddons\Users\UserSignupEventDetails userSignup
 * @event \IvoPetkov\BearFrameworkAddons\Users\UserLoginEventDetails userLogin
 * @event \IvoPetkov\BearFrameworkAddons\Users\UserLogoutEventDetails userLogout
 */
class Users
{

    use \BearFramework\EventsTrait;

    /**
     *
     * @var array 
     */
    private $providers = [];

    /**
     * 
     * @var
     */
    static private $newUserCache = null;

    /**
     * 
     * @param string $id
     * @param string $class
     * @param array $options
     * @return self
     */
    public function addProvider(string $id, string $class, array $options = []): self
    {
        $this->providers[$id] = [$class, false, $options];
        return $this;
    }

    /**
     * 
     * @return array
     */
    public function getProviders(): array
    {
        $result = [];
        foreach ($this->providers as $id => $data) {
            $result[] = [
                'id' => $id,
                'class' => $data[0],
            ];
        }
        return $result;
    }

    /**
     * 
     * @param string $id
     * @return \IvoPetkov\BearFrameworkAddons\Users\Provider|null
     */
    public function getProvider(string $id): ?\IvoPetkov\BearFrameworkAddons\Users\Provider
    {
        if (!isset($this->providers[$id])) {
            return null;
        }
        $providerData = $this->providers[$id];
        if ($providerData[1] === false) {
            $class = $providerData[0];
            $providerData[1] = class_exists($class) ? new $class() : null;
            $providerData[1]->id = $id;
            $providerData[1]->options = $providerData[2];
        }
        return $providerData[1];
    }

    /**
     * 
     * @param string $id
     * @return bool
     */
    public function providerExists(string $id): bool
    {
        return isset($this->providers[$id]);
    }

    /**
     * 
     * @return \IvoPetkov\BearFrameworkAddons\Users\User
     */
    public function make(): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        if (self::$newUserCache === null) {
            self::$newUserCache = new User();
        }
        return clone (self::$newUserCache);
    }

    /**
     * 
     * @param string $provider
     * @param string $id
     * @return \IvoPetkov\BearFrameworkAddons\Users\User
     */
    public function getUser(string $provider, string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        $user = $this->make();
        $user->provider = $provider;
        $user->id = $id;
        return $user;
    }

    /**
     * 
     * @param string $provider
     * @param string $id
     * @return boolean
     */
    public function userExists(string $provider, string $id): bool
    {
        return $this->getUserData($provider,  $id) !== null;
    }

    /**
     * 
     * @return \IvoPetkov\DataList
     */
    public function getList(): \IvoPetkov\DataList
    {
        return new \IvoPetkov\DataList(function (\IvoPetkov\DataListContext $context) {
            // Optimize for $context
            $result = [];
            $app = App::get();
            $providers = $this->getProviders();
            foreach ($providers as $provider) {
                $providerID = $provider['id'];
                $list = $app->data->getList()->filterBy('key', 'users/' . md5($providerID) . '/', 'startWith')->sliceProperties(['value']);
                foreach ($list as $item) {
                    $data = json_decode($item->value, true);
                    if (is_array($data) && isset($data['provider'], $data['id'], $data['data']) && $data['provider'] === $providerID) {
                        $result[] = $this->getUser($providerID, $data['id']);
                    }
                }
            }
            return $result;
        });
    }

    /**
     * 
     * @param string $provider
     * @param string $id
     * @return array|null
     */
    public function getUserData(string $provider, string $id): ?array
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

    /**
     * 
     * @param string $provider
     * @param string $id
     * @param array $data
     * @return void
     */
    public function saveUserData(string $provider, string $id, array $data): void
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

    /**
     * 
     * @param string $provider
     * @param string $sourceFileName
     * @param string $extension
     * @return string
     */
    public function saveUserFile(string $provider, string $sourceFileName, string $extension): string
    {
        $app = App::get();
        $key = md5(uniqid() . $sourceFileName) . '.' . preg_replace('/[^a-z0-9]/', '', strtolower($extension));
        $dataItem = $app->data->make('users/' . md5($provider) . '-files/' . $key, file_get_contents($sourceFileName));
        $app->data->set($dataItem);
        return $key;
    }

    /**
     * 
     * @param string $provider
     * @param string $key
     * @return void
     */
    public function deleteUserFile(string $provider, string $key): void
    {
        $app = App::get();
        $app->data->delete('users/' . md5($provider) . '-files/' . $key);
    }

    /**
     * 
     * @param string $provider
     * @param string $key
     * @return string
     */
    public function getUserFilePath(string $provider, string $key): string
    {
        $app = App::get();
        return $app->data->getFilename('users/' . md5($provider) . '-files/' . $key);
    }

    /**
     * 
     * @param \BearFramework\App\Response $response
     * @return void
     */
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

        if ($app->currentUser->exists()) {
            $context = $app->contexts->get(__DIR__);
            $dom = new HTML5DOMDocument();
            $dom->loadHTML($response->content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
            $dom->insertHTML($app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>'), 'afterBodyBegin');
            $response->content = $dom->saveHTML();
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return void
     */
    public function dispatchSignupEvent(string $providerID, string $userID)
    {
        if ($this->hasEventListeners('userSignup')) {
            $this->dispatchEvent('userSignup', new \IvoPetkov\BearFrameworkAddons\Users\UserSignupEventDetails($providerID, $userID));
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return void
     */
    public function dispatchLoginEvent(string $providerID, string $userID)
    {
        if ($this->hasEventListeners('userLogin')) {
            $this->dispatchEvent('userLogin', new \IvoPetkov\BearFrameworkAddons\Users\UserLoginEventDetails($providerID, $userID));
        }
    }

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return void
     */
    public function dispatchLogoutEvent(string $providerID, string $userID)
    {
        if ($this->hasEventListeners('userLogout')) {
            $this->dispatchEvent('userLogout', new \IvoPetkov\BearFrameworkAddons\Users\UserLogoutEventDetails($providerID, $userID));
        }
    }
}
