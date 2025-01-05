<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\EmailProvider;

$app = App::get();
$providerID = $app->currentUser->provider;

if ($app->currentUser->exists()) {

    $form->transformers
        ->addTrim('oldpassword')
        ->addTrim('new-password')
        ->addTrim('newpassword2');

    $form->constraints
        ->setRequired('oldpassword')
        ->setRequired('new-password')
        ->setMinLength('new-password', 6)
        ->setMaxLength('new-password', 100)
        ->setRequired('newpassword2')
        ->setMinLength('newpassword2', 6)
        ->setMaxLength('newpassword2', 100);

    $form->onSubmit = function ($values) use ($app, $providerID, $form): void {
        $oldPassword = $values['oldpassword'];
        $newPassword = $values['new-password'];
        $newPassword2 = $values['newpassword2'];
        $userID = $app->currentUser->id;

        if ($newPassword !== $newPassword2) {
            $form->throwElementError('newpassword2', __('ivopetkov.users.email.changePassword.passwordsDontMatch'));
        }
        if (!EmailProvider::exists($providerID, $userID)) {
            $form->throwError();
        }

        if (EmailProvider::checkPassword($providerID, $userID, $oldPassword)) {
            EmailProvider::setPassword($providerID, $userID, $newPassword);
        } else {
            $form->throwElementError('oldpassword', __('ivopetkov.users.email.changePassword.currentIsInvalid'));
        }
    };

    $onSubmitSuccess = 'clientPackages.get("users").then(function(u){u._closeAllWindows();});';
    echo '<form onsubmitsuccess="' . htmlentities($onSubmitSuccess) . '">';
    echo '<form-element-password name="oldpassword" label="' . htmlentities(__('ivopetkov.users.email.changePassword.currentPassword')) . '"/>';
    echo '<form-element-password name="new-password" label="' . htmlentities(__('ivopetkov.users.email.changePassword.newPassword')) . '"/>';
    echo '<form-element-password name="newpassword2" label="' . htmlentities(__('ivopetkov.users.email.changePassword.repeatNewPassword')) . '"/>';
    echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.email.changePassword.save')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.changePassword.saveWaiting')) . '" />';
    echo '</form>';
}
