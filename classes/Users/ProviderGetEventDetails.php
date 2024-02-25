<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

/**
 * @property \IvoPetkov\BearFrameworkAddons\Users\Provider $provider
 */
class ProviderGetEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param \IvoPetkov\BearFrameworkAddons\Users\Provider $provider
     */
    public function __construct(\IvoPetkov\BearFrameworkAddons\Users\Provider $provider)
    {
        $this
            ->defineProperty('provider', [
                'type' => \IvoPetkov\BearFrameworkAddons\Users\Provider::class
            ]);
        $this->provider = $provider;
    }
}
