<?php

declare(strict_types=1);

require_once('../lib.php');

function main()
{
    $password = null;
    $cpuSerialNumber = null;
    $limit = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumber'
        );

        $limit = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'limit'
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

    if (100 < $limit) {
        BasicTools::writeErrorLogAndDie('$limit value is too big');
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

    $toPhotostandId = null;

    try {
        $toPhotostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
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

    $rows = DBCRecordVoices::getResentryRecordVoices(
        $pdo,
        (ContentsDirectoryPaths::getRecordVoices())->getWebServerPathWithoutPrefix(),
        $toPhotostandId,
        $limit
    );

    $jsonString = json_encode($rows);
    assert(!($jsonString === false), 'json_encode fail.');

    header('Content-type: application/json');

    echo $jsonString;
}

main();


