<?php

declare(strict_types=1);

require_once('../../lib.php');
require_once('./_manage_operation_lib.php');

function main()
{
    $pdo = null;

    try {
        $pdo = DBCommon::createConnection();
    } catch (Exception $e) {
        exitWithText(createErrorMessage($e, "create db connection"));
    }


    exitWithTable(
        DBCPhotostand::getAllValue($pdo)
    );
}

main();

