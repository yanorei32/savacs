<?php

declare(
    strict_types = 1
);

require_once('../lib.php');

function main()
{
    $password = null;
    $cpuSerialNumber = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumber'
        );

        $displayName = ApacheEnvironmentWrapper::getUnicodeStringByParams(
            $_POST,
            'displayName'
        );
    } catch (OutOfBoundsException $e) {
        BasicTools::writeErrorLogAndDie(
            'OutOfBoundsException: ' .
            $e->getMessage()
        );
    } catch (UnexpectedValueException $e) {
        BasicTools::writeErrorLogAndDie(
            'UnexpectedValueException: ' .
            $e->getMessage()
        );
    }

    $pdo = null;

    try {
        $pdo = DBCommon::createConnection();
    } catch (PDOException $e) {
        BasicTools::writeErrorLogAndDie(
            'PDOException in createConnection: ' .
            $e->getMessage()
        );
    }

    try {
        DBCPhotostand::registrationByCpuSerialNumberAndPasswordAndDisplayName(
            $pdo,
            $cpuSerialNumber,
            $password,
            $displayName
        );
    } catch (PDOException $e) {
        BasicTools::writeErrorLogAndDie(
            'PDOException in ragistration: ' .
            $e->getMessage()
        );
    }

    header('Content-type: text/plain');

    echo 'Success.';
}

main();

