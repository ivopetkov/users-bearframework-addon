<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\UsernameProvider;

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

    $form->onSubmit = function ($values) use ($app, $providerID, $form) {
        $oldPassword = $values['oldpassword'];
        $newPassword = $values['new-password'];
        $newPassword2 = $values['newpassword2'];
        $userID = $app->currentUser->id;

        if ($newPassword !== $newPassword2) {
            $form->throwElementError('newpassword2', __('ivopetkov.users.username.changePassword.passwordsDontMatch'));
        }
        if (!UsernameProvider::exists($providerID, $userID)) {
            $form->throwError();
        }

        if (UsernameProvider::checkPassword($providerID, $userID, $oldPassword)) {
            UsernameProvider::setPassword($providerID, $userID, $newPassword);
        } else {
            $form->throwElementError('oldpassword', __('ivopetkov.users.username.changePassword.currentIsInvalid'));
        }
    };

    $onSubmitSuccess = 'clientPackages.get("users").then(function(users){users._closeAllWindows();});';
    echo '<form onsubmitsuccess="' . htmlentities($onSubmitSuccess) . '">';
    echo '<form-element-password name="oldpassword" label="' . htmlentities(__('ivopetkov.users.username.changePassword.currentPassword')) . '"/>';
    echo '<form-element-password name="new-password" label="' . htmlentities(__('ivopetkov.users.username.changePassword.newPassword')) . '"/>';
    echo '<form-element-password name="newpassword2" label="' . htmlentities(__('ivopetkov.users.username.changePassword.repeatNewPassword')) . '"/>';
    echo '<form-element-submit-button text="' . htmlentities(__('ivopetkov.users.username.changePassword.save')) . '" waitingText="' . htmlentities(__('ivopetkov.users.username.changePassword.saveWaiting')) . '" />';
    echo '</form>';
}
