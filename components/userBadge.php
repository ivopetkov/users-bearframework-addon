<?php

use BearFramework\App;

$app = App::get();

$styles = 'background-image:url(' . $app->currentUser->getImageUrl(100) . ');';
$stylesAttribute = isset($styles{0}) ? 'style="' . $styles . '"' : '';
?><div class="ivopetkov-users-badge"<?= $stylesAttribute ?> onclick="ivoPetkov.bearFrameworkAddons.users.showAccount();"></div>