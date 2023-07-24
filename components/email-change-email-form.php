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
    ->setRequired('password');

$form->onSubmit = function ($values) use ($app, $providerID, $form) {
    $email = $values['email'];
    $password = $values['password'];

    if ($app->currentUser->exists()) {
        $userID = $app->currentUser->id;

        if (EmailProvider::checkPassword($providerID, $userID, $password)) {
            if (EmailProvider::getEmail($app->currentUser->provider, $app->currentUser->id) === $email) {
                $form->throwElementError('email', __('ivopetkov.users.email.changeEmail.notChanged'));
            }
            if (EmailProvider::getUserID($app->currentUser->provider, $email) !== null) {
                $form->throwElementError('email', __('ivopetkov.users.email.changeEmail.taken'));
            }
            $key = EmailProvider::generateChangeEmailKey($providerID, $userID, $email);
            EmailProvider::sendChangeEmailEmail($providerID, $email, $key);
            return Utilities::getFormSubmitResult(['jsCode' => 'clientPackages.get("users").then(function(users){users._closeAllWindows();users.openProviderScreen("' . $providerID . '","change-email-email-sent",{"email":"' . $email . '"});});']);
        }

        $form->throwElementError('password', __('ivopetkov.users.email.changeEmail.invalidPassword'));
    }
};

echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
echo '<form-element-textbox name="email" value="' . htmlentities(EmailProvider::getEmail($app->currentUser->provider, $app->currentUser->id)) . '" label="' . htmlentities(__('ivopetkov.users.email.changeEmail.email')) . '" hintAfter="' . htmlentities(__('ivopetkov.users.email.changeEmail.emailHint')) . '" autocomplete="off" />';
echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.email.changeEmail.password')) . '"/>';
echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.changeEmail.save')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.changeEmail.saveWaiting')) . '" />';
echo '</form>';
