<?php

require 'kirby/bootstrap.php';
require_once 'site/plugins/studio-seo/OgImage.php';

// Register guarded OG routes before the plugin's default routes.
echo (new Kirby(['routes' => Studio\Seo\OgImage::routes()]))->render();
