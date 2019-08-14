<?php

declare(strict_types=1);

require_once('../../lib.php');
require_once('./_manage_operation_lib.php');

function main()
{
    $password = null;
    $cpuSerialNumber = null;
    $record = false;
    $selfy = false;
    $ignoreWillNotBeUse = false;
    $emailAddress = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST, 'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST, 'cpuSerialNumber'
        );

        $record = ApacheEnvironmentWrapper::getBoolByParams(
            $_POST, 'record'
        );

        $selfy = ApacheEnvironmentWrapper::getBoolByParams(
            $_POST, 'selfy'
        );

        $ignoreWillNotBeUse = ApacheEnvironmentWrapper::getBoolByParams(
            $_POST, 'ignoreWillNotBeUse'
        );

        $ignoreRfc822 = ApacheEnvironmentWrapper::getBoolByParams(
            $_POST, 'ignoreRfc822'
        );

        $emailAddress = ApacheEnvironmentWrapper::getEmailAddressByParams(
            $_POST, 'email', !$ignoreRfc822
        );
    } catch (Exception $e) {
        exitWithText(createErrorMessage($e, "parameter parse"));
    }

    if (!$record && !$selfy && !$ignoreWillNotBeUse) {
        exitWithText(
            "Error: this email address will not be use.\n" .
            "Please select record or selfy or both."
        );
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
            $pdo, $cpuSerialNumber, $password
        );
    } catch (Exception $e) {
        exitWithText(createErrorMessage($e, "photostand auth"));
    }


    DBCNotificationEmail::registrationNewEmail(
        $pdo, $photostandId, $emailAddress, $record, $selfy
    );

    exitWithText("Done (PSID: $photostandId)");
}

main();


