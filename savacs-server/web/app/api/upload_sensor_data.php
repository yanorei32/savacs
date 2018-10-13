<?php

declare(
    strict_types = 1
);

require_once('lib.php');

function writeErrorLogAndDie(string $message)
{
    http_response_code(500);
    header('Content-type: text/plain');
    echo $message;
    exit(1);
}

function main()
{
    $password               = null;
    $cpuSerialNumber        = null;
    $cdsLux                 = null;
    $temperatureCelsius     = null;
    $infraredCentimetear    = null;
    $ultrasonicCentimetear  = null;
    $pyroelectric           = null;
    $eventType              = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumber'
        );

        $cdsLux = ApacheEnvironmentWrapper::getFloatByParams(
            $_POST,
            'cdsLux'
        );

        $temperatureCelsius = ApacheEnvironmentWrapper::getFloatByParams(
            $_POST,
            'temperatureCelsius'
        );

        $infraredCentimetear = ApacheEnvironmentWrapper::getFloatByParams(
            $_POST,
            'infraredCentimetear'
        );

        $ultrasonicCentimetear = ApacheEnvironmentWrapper::getFloatByParams(
            $_POST,
            'ultrasonicCentimetear'
        );

        $pyroelectric = ApacheEnvironmentWrapper::getFloatByParams(
            $_POST,
            'pyroelectric'
        );

        $eventType = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'eventType'
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

    $fromPhotostandId = null;

    try {
        $fromPhotostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (RuntimeException $e) {
        writeErrorLogAndDie(
            'RuntimeException in Authorization: ' .
            $e->getMessage()
        );
    } catch (RangeException $e) {
        writeErrorLogAndDie(
            'RangeException in Authorization: ' .
            $e->getMessage()
        );
    }

    $sensorValue = new SensorValue(
        $cdsLux,
        $temperatureCelsius,
        $infraredCentimetear,
        $ultrasonicCentimetear,
        $pyroelectric,
        $eventType
    );


    try {
        DBCSensorData::registrationNewData(
            $pdo,
            $sensorValue,
            $fromPhotostandId
        );
    } catch (PDOException $e) {
        writeErrorLogAndDie(
            'RuntimeException in Registration: ' .
            $e->getMessage()
        );
    }

    echo 'Success.';
}

main();



