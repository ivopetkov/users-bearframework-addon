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
 * @property \BearFramework\Emails\Email $email
 * @property bool $preventDefault
 */
class BeforeSendEmailEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param string $providerID
     * @param \BearFramework\Emails\Email $email
     */
    public function __construct(string $providerID, \BearFramework\Emails\Email $email)
    {
        $this
            ->defineProperty('providerID', [
                'type' => 'string'
            ])
            ->defineProperty('email', [
                'type' => '\BearFramework\Emails\Email'
            ])
            ->defineProperty('preventDefault', [
                'type' => 'bool',
                'init' => function () {
                    return false;
                }
            ]);
        $this->providerID = $providerID;
        $this->email = $email;
    }
}
