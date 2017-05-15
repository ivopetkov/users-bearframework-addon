<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use BearFramework\App;
use \IvoPetkov\BearFrameworkAddons\Users\User;

/**
 * Users
 */
class Users
{

    private $providers = [];
    private static $newUserCache = null;

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

    public function enableUI(\BearFramework\App\Response $response): void
    {
        $response->enableIvoPetkovUsersUI = true;
    }

    public function disableUI(\BearFramework\App\Response $response): void
    {
        if (isset($response->enableIvoPetkovUsersUI)) {
            unset($response->enableIvoPetkovUsersUI);
        }
    }

    function getUserData(string $provider, string $id): ?array
    {
        $app = App::get();
        $result = $app->data->getValue('users/' . md5($provider) . '/' . md5($id) . '.json');
        if ($result !== null) {
            $data = json_decode($result, true);
            if (is_array($data) && isset($data['provider'], $data['id'], $data['data']) && $data['provider'] === $provider && $data['id'] === $id) {
                return $data['data'];
            }
        }
        return null;
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
    }

}
