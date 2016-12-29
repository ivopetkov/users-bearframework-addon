<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace IvoPetkov\BearFramework\Addons;

use BearFramework\App;

/**
 * @property string $provider The user service provider
 * @property string $id The id of the user
 * @property string $name The name of the user
 * @property string $url The profile URL of the User
 * @property string $image The image of the user
 */
class User extends \IvoPetkov\DataObject
{

    function __construct()
    {
        
    }

    function save()
    {
        $app = App::get();
        $data = [
            'provider' => $this->provider,
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'image' => $this->image
        ];
        $app->data->set([
            'key' => 'users/' . md5($this->provider) . '/' . md5($this->id) . '.json',
            'body' => json_encode($data)
        ]);
    }

}
