<?php

/*
 * HTML Server Components addon for Bear Framework
 * https://github.com/ivopetkov/html-server-components-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

/**
 * @property \BearFramework\App\Response $response
 * @property bool $preventDefault
 */
class BeforeApplyUIEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param \BearFramework\App\Response $response
     */
    public function __construct(\BearFramework\App\Response $response)
    {
        $this
                ->defineProperty('response', [
                    'type' => \BearFramework\App\Response::class
                ])
                ->defineProperty('preventDefault', [
                    'type' => bool,
                    'init' => function() {
                        return false;
                    }
                ])
        ;
        $this->response = $response;
    }

}
