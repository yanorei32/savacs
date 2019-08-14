<?php

declare(strict_types=1);

require_once('../../lib.php');
require_once('./_manage_operation_lib.php');

function main()
{
    $passwordA = null;
    $cpuSerialNumberB = null;
    $passwordB = null;
    $cpuSerialNumberB = null;
    try {
        $passwordA = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST, 'passwordA'
        );

        $cpuSerialNumberA = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST, 'cpuSerialNumberA'
        );

        $passwordB = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST, 'passwordB'
        );

        $cpuSerialNumberB = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST, 'cpuSerialNumberB'
        );
    } catch (OutOfBoundsException $e) {
        exitWithText(createErrorMessage($e, 'parameter parse'));
    } catch (UnexpectedValueException $e) {
        exitWithText(createErrorMessage($e, 'parameter parse'));
    }

    if ($cpuSerialNumberA == $cpuSerialNumberB)
        exitWithText('A and B are equal');

    $pdo = null;
    try {
        $pdo = DBCommon::createConnection();
    } catch (PDOException $e) {
        exitWithText(createErrorMessage($e, 'DBConnection'));
    }

    $photostandAId = null;
    try {
        $photostandAId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo, $cpuSerialNumberA, $passwordA
        );
    } catch (RuntimeException $e) {
        exitWithText(createErrorMessage($e, 'photostand auth (A)'));
    } catch (RangeException $e) {
        exitWithText(createErrorMessage($e, 'photostand auth (A)'));
    }

    $photostandBId = null;
    try {
        $photostandBId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo, $cpuSerialNumberB, $passwordB
        );
    } catch (RuntimeException $e) {
        exitWithText(createErrorMessage($e, 'photostand auth (B)'));
    } catch (RangeException $e) {
        exitWithText(createErrorMessage($e, 'photostand auth (B)'));
    }


    try {
        $ret = DBCPhotostand::isActiveAssociationByPhotostandIds(
            $pdo, $photostandAId, $photostandBId
        );

        if ($ret)
            exitWithText('Already associated');

    } catch (PDOException $e) {
        exitWithText(createErrorMessage($e, 'check association'));
    }

    DBCPhotostand::createAssociationByPhotostandIds(
        $pdo, $photostandAId, $photostandBId
    );

    exitWithText("Done (PSID: $photostandAId, $photostandBId)");
}

main();

