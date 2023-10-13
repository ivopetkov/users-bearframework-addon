<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

/**
 * @property string $providerID
 * @property string $userID
 */
class UserSignupEventDetails
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
