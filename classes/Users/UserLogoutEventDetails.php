<?php

/*
 * HTML Server Components addon for Bear Framework
 * https://github.com/ivopetkov/html-server-components-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

/**
 * @property string $providerID
 * @property string $userID
 */
class UserLogoutEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param string $providerID
     * @param string $userID
     */
    public function __construct(string $providerID, string $userID)
    {
        $this
            ->defineProperty('providerID', [
                'type' => 'string'
            ])
            ->defineProperty('userID', [
                'type' => 'string'
            ]);
        $this->providerID = $providerID;
        $this->userID = $userID;
    }
}
