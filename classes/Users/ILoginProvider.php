<?php

/*
 * Users addon for Bear Framework
 * https://github.com/ivopetkov/users-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\Users;

interface ILoginProvider
{

    public function hasLoginButton(): bool;

    public function getLoginButtonText(): string;

    public function hasLogoutButton(): bool;

    public function login(\IvoPetkov\BearFrameworkAddons\Users\LoginContext $context): \IvoPetkov\BearFrameworkAddons\Users\LoginResponse;

    public function getUserProperties(string $id): array;
}
