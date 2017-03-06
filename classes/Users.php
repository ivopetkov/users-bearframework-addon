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
    }

    function getUser(string $provider, string $id): \IvoPetkov\BearFrameworkAddons\Users\User
    {
        if ($this->providerExists($provider)) {
            $provider = $this->getProvider($provider);
            return $provider->makeUser($id);
        }
        $user = $this->make();
        $user->provider = $provider;
        $user->id = $id;
        return $user;
    }

    public function enableUI(\BearFramework\App\Response $response): void
    {
        $response->enableIvoPetkovUsersUI = true;
    }

    function getUserData(string $provider, string $id): ?array
    {
        $app = App::get();
        $result = $app->data->getValue('users/' . md5($provider) . '/' . md5($id) . '.json');
        if ($result !== null) {
            return json_decode($result, true);
        }
        return null;
    }

    function saveUserData(string $provider, string $id, array $data): void
    {
        $app = App::get();
        $app->data->set($app->data->make('users/' . md5($provider) . '/' . md5($id) . '.json', json_encode($data)));
    }

}
