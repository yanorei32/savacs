<?php

declare(
    strict_types = 1
);
require_once('lib.php');
$pdo = DBCommon::createConnection();
var_dump(DBCPhotostand::getAllValue($pdo));



