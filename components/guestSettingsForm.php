<?php

use BearFramework\App;

$app = App::get();
$providerID = 'guest';
if (!$app->currentUser->exists()) {
    throw new Exception('No logged in user');
}
$userID = $app->currentUser->id;

$getImageDataKey = function($filename) use ($providerID) {
    return 'users/' . md5($providerID) . '-files/' . $filename;
};

$getUserData = function() use ($app, $providerID, $userID) {
    $data = $app->users->getUserData($providerID, $userID);
    if (empty($data)) {
        $data = [];
    }
    if (!isset($data['image'])) {
        $data['image'] = '';
    }
    if (!isset($data['name'])) {
        $data['name'] = '';
    }
    if (!isset($data['website'])) {
        $data['website'] = '';
    }
    return $data;
};

$form->onSubmit = function($values) use ($app, $providerID, $userID, $getUserData, $getImageDataKey) {
    $data = $getUserData();
    $data['name'] = isset($values['ivopetkov-users-guest-settings-form-name']) ? (string) $values['ivopetkov-users-guest-settings-form-name'] : '';
    $data['website'] = isset($values['ivopetkov-users-guest-settings-form-name']) ? (string) $values['ivopetkov-users-guest-settings-form-website'] : '';
    $removeImage = isset($values['ivopetkov-users-guest-settings-form-image-removed']) && (int) $values['ivopetkov-users-guest-settings-form-image-removed'] === 1;
    $tempImageFilename = null;
    $newImageFilename = null;
    if (isset($values['ivopetkov-users-guest-settings-form-image'])) {
        $newImageData = json_decode($values['ivopetkov-users-guest-settings-form-image'], true);
        $newImage = is_array($newImageData) && isset($newImageData[0]) && isset($newImageData[0]['filename']) && is_file($newImageData[0]['filename']) ? $newImageData[0] : null;
        if ($newImage !== null) {
            $pathInfo = pathinfo($newImage['value']);
            if (isset($pathInfo['extension'])) {
                $tempImageFilename = $newImage['filename'];
                $newImageFilename = md5(uniqid() . $newImage['filename']) . '.' . $pathInfo['extension'];
            }
        }
    }
    $imageToRemove = null;
    if ($newImageFilename !== null) {
        $imageDataKey = $getImageDataKey($newImageFilename);
        $dataItem = $app->data->make($imageDataKey, file_get_contents($tempImageFilename));
        $app->data->set($dataItem);
        $app->data->makePublic($imageDataKey);
        $imageToRemove = $data['image'];
        $data['image'] = $newImageFilename;
    } elseif ($removeImage) {
        $imageToRemove = $data['image'];
        $data['image'] = '';
    }
    if ($imageToRemove !== null && strlen($imageToRemove) > 0) {
        $imageDataKey = $getImageDataKey($imageToRemove);
        $app->data->rename($imageDataKey, '.recyclebin/' . $imageDataKey);
    }
    $app->users->saveUserData($providerID, $userID, $data);
};

$data = $getUserData();
$image = strlen($data['image']) > 0 ? $app->assets->getUrl($app->data->getFilename($getImageDataKey($data['image'])), ['width' => 500, 'height' => 500]) : '';
$name = $data['name'];
$website = $data['website'];

$hasImage = !empty($image);
?><html>
    <head>
        <style>
            .ivopetkov-users-guest-settings-form{
                text-align: left;
            }
            .ivopetkov-users-guest-settings-form-image input{
                width: 0.1px;
                height: 0.1px;
                opacity: 0;
                overflow: hidden;
                position: absolute;
                z-index: -1;
            }
            .ivopetkov-users-guest-settings-form-name, .ivopetkov-users-guest-settings-form-image, .ivopetkov-users-guest-settings-form-website{
                width:250px;
                font-size:15px;
                padding:13px 15px;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#eee;
                border-radius:2px;
                color:#000;
                box-sizing: border-box;
                display:block;
                margin-bottom: 21px;
            }
            .ivopetkov-users-guest-settings-form-image-preview-container{
                margin-bottom: 21px;
            }
            .ivopetkov-users-guest-settings-form-image{
                cursor:pointer;
            }
            .ivopetkov-users-guest-settings-form-image-label, .ivopetkov-users-guest-settings-form-name-label, .ivopetkov-users-guest-settings-form-website-label{
                font-family:Arial,Helvetica,sans-serif;
                font-size:15px;
                color:#fff;
                padding-bottom: 9px;
                cursor: default;
                display:block;
            }
            .ivopetkov-users-guest-settings-form-image-preview{
                width:250px;
                height:250px;
                border-radius:2px;
                background-color:black;
                background-size:cover;
                background-repeat:no-repeat;
                background-position: center center;
            }
            .ivopetkov-users-guest-settings-form-remove-image-button{
                font-family:Arial,Helvetica,sans-serif;
                font-size:14px;
                color:#fff;
                cursor:pointer;
                margin-top: 5px;
                display: inline-block;
            }
            .ivopetkov-users-guest-settings-form-button, .ivopetkov-users-guest-settings-form-button-waiting{
                cursor:pointer;
                font-family:Arial,Helvetica,sans-serif;
                background-color:#fff;
                font-size:15px;
                border-radius:2px;
                padding:13px 15px;
                color:#000;
                margin-top:25px;
                display:block;
                text-align:center;
            }
            .ivopetkov-users-guest-settings-form-button-waiting{
                background-color:#ddd;
            }
            .ivopetkov-users-guest-settings-form-button:hover{
                background-color:#f5f5f5;
            }
            .ivopetkov-users-guest-settings-form-button:active{
                background-color:#eeeeee;
            }
        </style>
    </head>
    <body><?php
        echo '<form'
        . ' class="ivopetkov-users-guest-settings-form"'
        . ' onsubmitdone="window.location.reload()"'
        . ' onrequestsent="document.querySelector(\'.ivopetkov-users-guest-settings-form-button\').style.display=\'none\';document.querySelector(\'.ivopetkov-users-guest-settings-form-button-waiting\').style.display=\'block\';"'
        . ' onresponsereceived="document.querySelector(\'.ivopetkov-users-guest-settings-form-button-waiting\').style.display=\'none\';document.querySelector(\'.ivopetkov-users-guest-settings-form-button\').style.display=\'block\';"'
        . '>';
        echo '<label for="ivopetkov-users-guest-settings-form-image" class="ivopetkov-users-guest-settings-form-image-label">Image</label>';
        echo '<div class="ivopetkov-users-guest-settings-form-image-preview-container" style="' . ($hasImage ? '' : 'display:none;') . '">';
        echo '<input type="hidden" name="ivopetkov-users-guest-settings-form-image-removed"/>';
        echo '<div class="ivopetkov-users-guest-settings-form-image-preview"' . ($hasImage ? ' style="background-image:url(' . $image . ');"' : '') . '></div>';
        echo '<span class="ivopetkov-users-guest-settings-form-remove-image-button" onclick="this.parentNode.firstChild.value=\'1\';this.parentNode.style.display=\'none\';this.parentNode.nextSibling.style.display=\'block\';">Remove selected image</span>';
        echo '</div>';
        echo '<label for="ivopetkov-users-guest-settings-form-image" class="ivopetkov-users-guest-settings-form-image" style="' . ($hasImage ? 'display:none;' : '') . '">Select image ...<input onchange="if(this.previousSibling === null){this.parentNode.insertBefore(document.createTextNode(\'\'),this);}this.previousSibling.textContent=(this.files.length === 1 ? \'1 file selected\' : (this.files.length === 0 ? \'Select file ...\' : this.files.length + \' files selected\'));" name="ivopetkov-users-guest-settings-form-image" id="ivopetkov-users-guest-settings-form-image" type="file"/></label>';
        echo '<label for="ivopetkov-users-guest-settings-form-name" class="ivopetkov-users-guest-settings-form-name-label">Name</label>';
        echo '<input name="ivopetkov-users-guest-settings-form-name" class="ivopetkov-users-guest-settings-form-name" type="text" value="' . htmlentities($name) . '"/>';
        echo '<label for="ivopetkov-users-guest-settings-form-website" class="ivopetkov-users-guest-settings-form-website-label">Website</label>';
        echo '<input name="ivopetkov-users-guest-settings-form-website" class="ivopetkov-users-guest-settings-form-website" type="text" value="' . htmlentities($website) . '"/>';
        echo '<span class="ivopetkov-users-guest-settings-form-button" onclick="this.parentNode.submit();">Save changes</span>';
        echo '<span class="ivopetkov-users-guest-settings-form-button-waiting" style="display:none;">Saving changes ...</span>';
        echo '</form>';
        ?></body>
</html>