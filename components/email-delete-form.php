<?php

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\Users\EmailProvider;
use IvoPetkov\BearFrameworkAddons\Users\Internal\Utilities;

$app = App::get();
$providerID = $component->providerID;

if ($app->currentUser->exists()) {

    $userID = $app->currentUser->id;

    $form->transformers
        ->addTrim('password');

    $form->constraints
        ->setRequired('password')
        ->setMinLength('password', 6)
        ->setMaxLength('password', 100);

    $canDeleteResult = EmailProvider::canDelete($providerID, $userID,);
    $canDelete = $canDeleteResult['result'];
    $canDeleteReason = $canDeleteResult['reason'];

    $form->onSubmit = function ($values) use ($app, $providerID, $userID, $form, $canDelete, $canDeleteReason) {
        $password = $values['password'];

        if (EmailProvider::checkPassword($providerID, $userID, $password)) {
            if ($canDelete) {
                $app->currentUser->logout();
                EmailProvider::delete($providerID, $userID);
                $app->users->dispatchLogoutEvent($providerID, $userID);
                $app->users->dispatchDeleteEvent($providerID, $userID);

                $provider = $app->users->getProvider($providerID);
                if (isset($provider->options['getOnDeleteURL'])) {
                    $url = call_user_func($provider->options['getOnDeleteURL']);
                } else {
                    $url = $app->urls->get('/');
                }
                return Utilities::getFormSubmitResult(['redirectURL' => $url]);
            }
        }

        $form->throwElementError('password', __('ivopetkov.users.email.delete.invalidPassword'));
    };

    echo '<form onsubmitsuccess="' . Utilities::getFormSubmitResultHandlerJsCode() . '">';
    echo '<div style="text-align:center;padding-bottom:20px;">' . ($canDelete && $canDeleteReason === '' ? sprintf(__('ivopetkov.users.email.delete.warningText'), $app->request->host) : $canDeleteReason) . '</div>';
    if ($canDelete) {
        echo '<form-element-password name="password" label="' . htmlentities(__('ivopetkov.users.email.delete.password')) . '"/>';
        echo '<form-element-submit-button data-email-delete-form-component="delete-button" text="' . htmlentities(__('ivopetkov.users.email.delete.delete')) . '" waitingText="' . htmlentities(__('ivopetkov.users.email.delete.deleteWaiting')) . '" />';
    } else {
        $onClick = 'clientPackages.get("users").then(function(users){users._closeCurrentWindow();});';
        echo '<form-element-button text="OK" onclick="' . htmlentities($onClick) . '"/>';
    }
    echo '</form>';
}
