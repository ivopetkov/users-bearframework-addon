<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFramework\Addons;

use BearFramework\App;

/**
 * Users
 */
class Users
{

    function getUser($provider, $id)
    {
        $app = App::get();
        $result = $app->data->get([
            'key' => 'users/' . md5($provider) . '/' . md5($id) . '.json',
            'result' => ['body']
        ]);
        if (isset($result['body'])) {
            $body = json_decode($result['body'], true);
            $user = new \IvoPetkov\BearFramework\Addons\User();
            $user->provider = $provider;
            $user->id = $id;
            $user->name = isset($body['name']) ? $body['name'] : '';
            $user->url = isset($body['url']) ? $body['url'] : '';
            $user->image = isset($body['image']) ? $body['image'] : '';
            return $user;
        }
        return null;
    }

}
