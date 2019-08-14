<?php

declare(strict_types=1);

require_once('../../lib.php');
require_once('./_manage_operation_lib.php');

function main()
{
    $password = null;
    $cpuSerialNumber = null;
    $simple;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST, 'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST, 'cpuSerialNumber'
        );

        $simple = ApacheEnvironmentWrapper::getBoolByParams(
            $_POST, 'simple'
        );
    } catch (Exception $e) {
        exitWithText(createErrorMessage($e, "parameter parse"));
    }

    $pdo = null;

    try {
        $pdo = DBCommon::createConnection();
    } catch (Exception $e) {
        exitWithText(createErrorMessage($e, "create db connection"));
    }

    $photostandId = null;

    try {
        $photostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (Exception $e) {
        exitWithText(createErrorMessage($e, "photostand auth"));
    }


    if ($simple)
        exitWithList(
            DBCNotificationEmail::getEmailAddressesFromPhotostandId(
                $pdo,
                $photostandId,
                NotificationType::ALL
            )
        );

    exitWithTable(
        DBCNotificationEmail::getEmailAddressesFromPhotostandId4debug(
            $pdo,
            $photostandId,
            NotificationType::ALL
        )
    );
}

main();



