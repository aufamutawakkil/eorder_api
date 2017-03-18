<?php
/**
 * @AppName: BIBEX API
 * @Version: 1.0
 * @CreateDate: September 2016
 * @Author: Wisnu Hafid
 * @Docs: http://api.domain.com/docs
 * @Description: build with Slim Framework, Laravel Eloquent
 * 
 */

require '../vendor/autoload.php';
require '../config/global.php';
require '../config/db.php';

include 'boot.php';
include 'dependencies.php';
include 'router.php';

$app->run();