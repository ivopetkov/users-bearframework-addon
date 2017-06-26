<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users\Internal;

class Options
{

    /**
     *
     * @var boolean 
     */
    static $useDataCache = false;

    /**
     * 
     * @param array $options
     */
    static function set(array $options)
    {
        if (isset($options['useDataCache'])) {
            self::$useDataCache = (int) $options['useDataCache'] > 0;
        }
    }

}
