<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class DataTest extends BearFrameworkAddonTestCase
{

    /**
     * 
     */
    public function testCreate()
    {
        $app = $this->getApp();
        $user = new IvoPetkov\BearFrameworkAddons\User();
        $user->provider = 'facebook';
        $user->id = '123123123';
        $user->name = 'John';
        $user->url = 'https://www.facebook.com/example';
        $user->image = 'https://www.facebook.com/image/example';
        $user->save();

        $users = new \IvoPetkov\BearFrameworkAddons\Users();

        $user2 = $users->getUser('facebook', '123123123');
        $this->assertTrue($user2->provider === $user->provider);
        $this->assertTrue($user2->id === $user->id);
        $this->assertTrue($user2->name === $user->name);
        $this->assertTrue($user2->url === $user->url);
        $this->assertTrue($user2->image === $user->image);

        $user3 = $users->getUser('facebook', 'missing');
        $this->assertTrue($user3 === null);
    }

}
