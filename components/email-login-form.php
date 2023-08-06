<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\EmailProvider;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

$app = App::get();
$providerID = $component->providerID;

$form->transformers
    ->addToLowerCase('email')
    ->addTrim('email')
    ->addTrim('password');

$form->constraints
    ->setRequired('email')
    ->setMaxLength('email', 200)
    ->setEmail('email')
    ->setRequired('password')
    ->setMinLength('password', 6)
    ->setMaxLength('password', 100);

$form->onSubmit = function ($values) use ($app, $providerID, $form) {
    $email = $values['email'];
    $password = $values['password'];

    if (!$app->rateLimiter->logIP('ivopetkov-users-email-login-form', ['100/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    if (!$app->rateLimiter->log('ivopetkov-users-email-login-form-email', $email, ['10/m', '50/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    $userID = EmailProvider::checkEmailPassword($providerID, $email, $password);
    if ($userID !== null) {

        $app->currentUser->login($providerID, $userID, isset($values['remember']));
        $app->users->dispatchLoginEvent($providerID, $userID);

        $provider = $app->users->getProvider($providerID);
        if (isset($provider->options['getOnLoginURL'])) {
            $url = call_user_func($provider->options['getOnLoginURL']);
        } else {
            $url = $app->urls->get('/');
        }
        return Utilities::getFormSubmitResult(['redirectURL' => $url]);
    }

    $form->throwElementError('password', __('ivopetkov.users.email.login.invalidPassword'));
};

echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
echo '<form-element-textbox name="email" label="' . htmlentities(__('ivopetkov.users.email.login.email')) . '" autocomplete="off" />';
echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.email.login.password')) . '"/>';
echo '<form-element-checkbox name="remember" label="' . htmlentities(__('ivopetkov.users.email.login.remember')) . '" style="display:inline-block;"/>';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.login.login')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.login.loginWaiting')) . '" />';

$onClick = 'clientPackages.get("users").then(function(users){users.openProviderScreen("' . $providerID . '","lost-password");});';
echo '<div style="padding-top:20px;text-align:center;"><a onclick="' . htmlentities($onClick) . '" href="javascript:void(0);" style="color:#555;text-decoration:none;">' . __('ivopetkov.users.email.login.lostPassword') . '</a></div>';

echo '</form>';
