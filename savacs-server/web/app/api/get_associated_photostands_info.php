<?php

declare(strict_types=1);

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

    $photostandId = null;

    try {
        $photostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (RuntimeException $e) {
        BasicTools::writeErrorLogAndDie(
            'RuntimeException in photostandA Authorization: ' .
            $e->getMessage()
        );
    } catch (RangeException $e) {
        BasicTools::writeErrorLogAndDie(
            'RangeException in photostandA Authorization: ' .
            $e->getMessage()
        );
    }

    $photostandInfos = DBCPhotostand::getAssociatedPhotostands(
        $pdo,
        $photostandId
    );

    $jsonString = json_encode($photostandInfos);
    assert(!($jsonString === false), 'json_encode fail.');

    header('Content-type: application/json');

    echo $jsonString;
}

main();



