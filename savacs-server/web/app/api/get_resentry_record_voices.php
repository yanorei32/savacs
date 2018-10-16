<?php

declare(strict_types=1);

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

    if (100 < $limit) {
        writeErrorLogAndDie('$limit value is too big');
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

    $toPhotostandId = null;

    try {
        $toPhotostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (RuntimeException $e) {
        writeErrorLogAndDie(
            'RuntimeException in photostandA Authorization: ' .
            $e->getMessage()
        );
    } catch (RangeException $e) {
        writeErrorLogAndDie(
            'RangeException in photostandA Authorization: ' .
            $e->getMessage()
        );
    }

    $recordVoiceObjects = DBCRecordVoices::getResentryRecordVoices(
        $pdo,
        $toPhotostandId,
        $limit
    );

    $responceObject = array();

    foreach ($recordVoiceObjects as $recordVoiceObject) {
        $recordVoiceURI =
            (ContentsDirectoryPaths::getRecordVoices())->getWebServerPath() .
            $recordVoiceObject->getFileName();

        $responceObject[] = array(
            'uri' => $recordVoiceURI,
            'duration' => $recordVoiceObject->getDuration(),
            'created_at' => $recordVoiceObject->getCreatedAt(),
            'send_from' => strval($recordVoiceObject->fromPhotostandId())
        );
    }

    $jsonString = json_encode($responceObject);
    assert(!($jsonString === false), 'json_encode fail.');

    header('Content-type: application/json');

    echo $jsonString;
}

main();


