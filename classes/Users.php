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
     * @param string $id
     * @param string $class
     * @return self
     */
    public function addProvider(string $id, string $class): self
    {
        $this->providers[$id] = $class;
        return $this;
    }

    /**
     * 
     * @return array
     */
    public function getProviders(): array
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

    /**
     * 
     * @param string $id
     * @return \IvoPetkov\BearFrameworkAddons\Users\LoginProvider|null
     */
    public function getProvider(string $id): ?\IvoPetkov\BearFrameworkAddons\Users\LoginProvider
    {
        if (!isset($this->providers[$id])) {
            return null;
        }
        if (!class_exists($this->providers[$id])) {
            return null;
        }
        $class = $this->providers[$id];
        return new $class();
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
        return new User();
        // $this in defineProperty does not work well
//        if (self::$newUserCache === null) {
//            self::$newUserCache = new User();
//        }
//        $object = clone(self::$newUserCache);
//        return $object;
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
            $context = $app->contexts->get(__FILE__);
            $dom = new HTML5DOMDocument();
            $dom->loadHTML($response->content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
            $dom->insertHTML($app->components->process('<component src="file:' . $context->dir . '/components/user-badge.php"/>'), 'afterBodyBegin');
//            $dom->insertHTML('<html>'
//                    . '<head>'
//                    . '<link rel="client-package-embed" name="users">'
//                    . '<script>clientPackages.get("users").then(function(users){users._setHasCurrentUser();});</script>'
//                    . '</head>'
//                    . '</html>');
            $response->content = $dom->saveHTML();
        }
    }

}
