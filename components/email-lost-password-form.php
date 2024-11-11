<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\EmailProvider;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

$app = App::get();
$providerID = $component->providerID;

$form->transformers
    ->addToLowerCase('email')
    ->addTrim('email');

$form->constraints
    ->setRequired('email')
    ->setMaxLength('email', 200)
    ->setEmail('email');

$form->onSubmit = function ($values) use ($app, $providerID, $form) {
    $email = $values['email'];

    if (!$app->rateLimiter->logIP('ivopetkov-users-email-lost-password-form', ['10/m', '50/h'])) {
        $form->throwError(__('ivopetkov.users.tryAgainLater'));
    }

    $userID = EmailProvider::getUserID($providerID, $email);
    if ($userID !== null) {
        if ($app->rateLimiter->log('ivopetkov-users-email-lost-password-send-email', $userID, ['1/h'])) {
            $key = EmailProvider::generatePasswordResetKey($providerID, $userID);
            EmailProvider::sendPasswordResetEmail($providerID, $email, $key);
        }
    }

    return Utilities::getFormSubmitResult(['jsCode' => 'clientPackages.get("users").then(function(u){u._closeAllWindows({expectOpen:true}).then(function(){u.openProviderScreen("' . $providerID . '","lost-password-email-sent",{"email":"' . $email . '"});})});']);
};

echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
echo '<form-element-textbox name="email" label="' . htmlentities(__('ivopetkov.users.email.lostPassword.email')) . '" autocomplete="off" inputType="email" />';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.lostPassword.continue')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.lostPassword.continueWaiting')) . '" />';
echo '</form>';
