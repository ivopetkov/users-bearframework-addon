<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;
use IvoPetkov\BearFrameworkAddons\Users\UsernameProvider;

$app = App::get();
$providerID = $component->providerID;

$getOnLoginURL = function () use ($app, $providerID) {
    $provider = $app->users->getProvider($providerID);
    if (isset($provider->options['getOnLoginURL'])) {
        return call_user_func($provider->options['getOnLoginURL']);
    }
    return $app->urls->get('/');
};

$form->transformers
    ->addToLowerCase('username')
    ->addTrim('username')
    ->addTrim('password');

$form->constraints
    ->setRequired('username')
    ->setMaxLength('username', 100)
    ->setRequired('password')
    ->setMinLength('password', 6)
    ->setMaxLength('password', 100);

$form->onSubmit = function ($values) use ($app, $providerID, $form, $getOnLoginURL) {
    $username = $values['username'];
    $password = $values['password'];

    if (!$app->rateLimiter->logIP('ivopetkov-users-username-login-form', ['100/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    if (!$app->rateLimiter->log('ivopetkov-users-username-login-form-username', $username, ['10/m', '50/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    if ($app->currentUser->exists()) {
        $form->throwError(__('ivopetkov.users.alreadyLoggedIn'));
    }

    $userID = UsernameProvider::checkUsernamePassword($providerID, $username, $password);
    if ($userID !== null) {
        $app->currentUser->login($providerID, $userID, isset($values['remember']));
        $app->users->dispatchLoginEvent($providerID, $userID);

        return Utilities::getFormSubmitResult(['redirectURL' => $getOnLoginURL()]);
    }

    $form->throwElementError('password', __('ivopetkov.users.username.login.invalidPassword'));
};

if ($app->currentUser->exists()) {
    echo '<div style="text-align:center;padding-bottom:60px;">' . __('ivopetkov.users.alreadyLoggedIn') . '</div>';
    $onClick = 'clientPackages.get("users").then(function(u){u._openURL("' . $getOnLoginURL() . '",true);});';
    echo '<form-element-button text="OK" onclick="' . htmlentities($onClick) . '"/>';
} else {
    echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
    echo '<form-element-textbox name="username" label="' . htmlentities(__('ivopetkov.users.username.login.username')) . '" autocomplete="off" />';
    echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.username.login.password')) . '"/>';
    echo '<form-element-checkbox name="remember" label="' . htmlentities(__('ivopetkov.users.username.login.remember')) . '" style="display:inline-block;"/>';
    echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.username.login.login')) . '" waitingText="' . htmlentities(__('ivopetkov.users.username.login.loginWaiting')) . '" />';
    echo '</form>';
}
