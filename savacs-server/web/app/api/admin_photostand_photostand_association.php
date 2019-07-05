<?php

declare(strict_types=1);

require_once('../lib.php');

function main()
{
    $passwordA = null;
    $cpuSerialNumberA = null;
    $passwordB = null;
    $cpuSerialNumberB = null;

    try {
        $passwordA = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'passwordA'
        );

        $cpuSerialNumberA = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumberA'
        );

        $passwordB = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'passwordB'
        );

        $cpuSerialNumberB = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumberB'
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

    $photostandAId = null;

    try {
        $photostandAId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumberA,
            $passwordA
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

    $photostandBId = null;

    try {
        $photostandBId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumberB,
            $passwordB
        );
    } catch (RuntimeException $e) {
        BasicTools::writeErrorLogAndDie(
            'RuntimeException in photostandB Authorization: ' .
            $e->getMessage()
        );
    } catch (RangeException $e) {
        BasicTools::writeErrorLogAndDie(
            'RangeException in photostandB Authorization: ' .
            $e->getMessage()
        );
    }

    try {
        $ret = DBCPhotostand::isActiveAssociationByPhotostandIds(
            $pdo,
            $photostandAId,
            $photostandBId
        );

        if ($ret === true) {
            BasicTools::writeErrorLogAndDie(
                'Already associated.'
            );
        }
    } catch (PDOException $e) {
        BasicTools::writeErrorLogAndDie(
            'PDOException in check association: ' .
            $e->getMessage()
        );
    }

    $ret = DBCPhotostand::createAssociationByPhotostandIds(
        $pdo,
        $photostandAId,
        $photostandBId
    );

    header('Content-type: text/plain');

    echo 'Success.';
}

main();

