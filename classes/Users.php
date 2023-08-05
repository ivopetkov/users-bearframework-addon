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
 * @event \IvoPetkov\BearFrameworkAddons\Users\UserDeleteEventDetails userDelete
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
            if (class_exists($class)) {
                $providerData[1] = new $class($id, $providerData[2]);
            } else {
                $providerData[1] = null;
            }
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
     * @param string $providerID
     * @param string $id
     * @return \IvoPetkov\BearFrameworkAddons\Users\User
     */
    public function getUser(string $providerID, string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        $user = $this->make();
        $user->provider = $providerID;
        $user->id = $id;
        return $user;
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @return boolean
     */
    public function userExists(string $providerID, string $id): bool
    {
        return $this->getUserData($providerID,  $id) !== null;
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
     * @param string $providerID
     * @param string $id
     * @return array|null
     */
    public function getUserData(string $providerID, string $id): ?array
    {
        $app = App::get();
        $rawUserData = null;
        $cacheKey = $this->getUserDataCacheKey($providerID, $id);
        $rawUserData = $app->cache->getValue($cacheKey);
        if ($rawUserData === '-1') {
            return null;
        }
        $foundInCache = $rawUserData !== null;
        if ($rawUserData === null) {
            $rawUserData = $app->data->getValue($this->getUserDataDataKey($providerID, $id));
        }
        $result = null;
        if ($rawUserData !== null) {
            $data = json_decode($rawUserData, true);
            if (is_array($data) && isset($data['provider'], $data['id'], $data['data']) && $data['provider'] === $providerID && $data['id'] === $id) {
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
     * @param string $providerID
     * @param string $id
     * @param array $data
     * @return void
     */
    public function saveUserData(string $providerID, string $id, array $data): void
    {
        $app = App::get();
        $dataToSave = [
            'provider' => $providerID,
            'id' => $id,
            'data' => $data
        ];
        $app->data->set($app->data->make($this->getUserDataDataKey($providerID, $id), json_encode($dataToSave)));
        $cacheKey = $this->getUserDataCacheKey($providerID, $id);
        $app->cache->delete($cacheKey);
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @return void
     */
    public function deleteUserData(string $providerID, string $id): void
    {
        $app = App::get();
        $app->data->delete($this->getUserDataDataKey($providerID, $id));
        $cacheKey = $this->getUserDataCacheKey($providerID, $id);
        $app->cache->delete($cacheKey);
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @return string
     */
    private function getUserDataCacheKey(string $providerID, string $id): string
    {
        return 'ivopetkov-users-user-data-' . md5($providerID) . '-' . md5($id);
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @return string
     */
    private function getUserDataDataKey(string $providerID, string $id): string
    {
        return 'users/' . md5($providerID) . '/' . md5($id) . '.json';
    }

    /**
     * 
     * @param string $providerID
     * @param string $sourceFileName
     * @param string $extension
     * @return string
     */
    public function saveUserFile(string $providerID, string $sourceFileName, string $extension): string
    {
        $app = App::get();
        $key = md5(uniqid() . $sourceFileName) . '.' . preg_replace('/[^a-z0-9]/', '', strtolower($extension));
        $dataItem = $app->data->make($this->getUserFileDataKey($providerID, $key), file_get_contents($sourceFileName));
        $app->data->set($dataItem);
        return $key;
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return void
     */
    public function deleteUserFile(string $providerID, string $key): void
    {
        $app = App::get();
        $app->data->delete($this->getUserFileDataKey($providerID, $key));
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return string
     */
    public function getUserFilePath(string $providerID, string $key): string
    {
        $app = App::get();
        return $app->data->getFilename($this->getUserFileDataKey($providerID, $key));
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return string
     */
    private function getUserFileDataKey(string $providerID, string $key): string
    {
        return 'users/' . md5($providerID) . '-files/' . $key;
    }

    /**
     * 
     * @param string $providerID
     * @param string $id The data id
     * @return array|null
     */
    public function getTempData(string $providerID, string $id): ?array
    {
        $app = App::get();
        $rawData = $app->data->getValue($this->getTempDataDataKey($providerID, $id));
        $result = null;
        if ($rawData !== null) {
            $data = json_decode($rawData, true);
            if (is_array($data) && isset($data['provider'], $data['id'], $data['data']) && $data['provider'] === $providerID && $data['id'] === $id) {
                $result = $data['data'];
            }
        }
        return $result;
    }

    /**
     * 
     * @param string $providerID
     * @param string $id The data id
     * @param array $data
     * @return void
     */
    public function saveTempData(string $providerID, string $id, array $data): void
    {
        $app = App::get();
        $dataToSave = [
            'provider' => $providerID,
            'id' => $id,
            'data' => $data
        ];
        $app->data->set($app->data->make($this->getTempDataDataKey($providerID, $id), json_encode($dataToSave)));
    }

    /**
     * 
     * @param string $providerID
     * @param string $id
     * @return void
     */
    public function deleteTempData(string $providerID, string $id): void
    {
        $app = App::get();
        $app->data->delete($this->getTempDataDataKey($providerID, $id));
    }

    /**
     * 
     * @param string $providerID
     * @param string $key
     * @return string
     */
    private function getTempDataDataKey(string $providerID, string $id): string
    {
        return '.temp/users/providers/' . md5($providerID) . '/' . md5($id) . '.json';
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

    /**
     * 
     * @param string $providerID
     * @param string $userID
     * @return void
     */
    public function dispatchDeleteEvent(string $providerID, string $userID)
    {
        if ($this->hasEventListeners('userDelete')) {
            $this->dispatchEvent('userDelete', new \IvoPetkov\BearFrameworkAddons\Users\UserDeleteEventDetails($providerID, $userID));
        }
    }
}
