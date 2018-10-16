<?php

declare(
    strict_types = 1
);

require_once('../lib.php');

function writeErrorLogAndDie(string $message)
{
    http_response_code(500);
    header('Content-type: text/plain');
    echo $message;
    exit(1);
}

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
    } catch (OutOfBoundsException $e) {
        writeErrorLogAndDie(
            'OutOfBoundsException: ' .
            $e->getMessage()
        );
    } catch (UnexpectedValueException $e) {
        writeErrorLogAndDie(
            'UnexpectedValueException: ' .
            $e->getMessage()
        );
    }

    $pdo = null;

    try {
        $pdo = DBCommon::createConnection();
    } catch (PDOException $e) {
        writeErrorLogAndDie(
            'PDOException in createConnection: ' .
            $e->getMessage()
        );
    }

    try {
        DBCPhotostand::registrationByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (PDOException $e) {
        writeErrorLogAndDie(
            'PDOException in ragistration: ' .
            $e->getMessage()
        );
    }

    header('Content-type: text/plain');

    echo 'Success.';
}

main();

