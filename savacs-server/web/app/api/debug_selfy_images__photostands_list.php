<?php

declare(strict_types=1);

require_once('../lib.php');

$pdo = DBCommon::createConnection();

header('Content-type: text/plain');

var_dump(DBCSelfyImage::debugGetSelfyImagesPhotostands($pdo));



