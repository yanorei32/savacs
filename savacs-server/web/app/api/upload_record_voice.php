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

function isActiveAssociations(
    PDO $pdo,
    int $fromPhotostandId,
    array $toPhotostandIdsArray
) {
    foreach ($toPhotostandIdsArray as $toPhotostandId) {
        $ret = DBCPhotostand::isActiveAssociationByPhotostandIds(
            $pdo,
            $fromPhotostandId,
            $toPhotostandId
        );

        if (!$ret) {
            return false;
        }
    }

    return true;
}

function main()
{
    $password = null;
    $cpuSerialNumber = null;
    $toPhotostandIdsArray = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumber'
        );

        $toPhotostandIdsArray = ApacheEnvironmentWrapper::getIntArrayByParams(
            $_POST,
            'toPhotostandIdsArray'
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

    if (!isActiveAssociations($pdo, $fromPhotostandId, $toPhotostandIdsArray)) {
        writeErrorLogAndDie(
            'Association is not active'
        );
    }

    $tempVoiceFilePath = null;

    try {
        $tempVoiceFilePath = ApacheEnvironmentWrapper::getAACAudioByFilesParams(
            $_FILES,
            'recordVoice'
        );
    } catch (OutOfBoundsException $e) {
        writeErrorLogAndDie(
            'OutOfBoundsException in Upload file: ' .
            $e->getMessage()
        );
    } catch (UnexpectedValueException $e) {
        writeErrorLogAndDie(
            'UnexpectedValueException in Upload file: ' .
            $e->getMessage()
        );
    }

    $uniqueFileName = BasicTools::generateUniqueFileNameByFilePath(
        $tempVoiceFilePath
    );

    $dirPath = (ContentsDirectoryPaths::getRecordVoices())->getFileSystemPath();

    $recordVoiceFileName = $uniqueFileName . '.aac';
    $recordVoiceFilePath = $dirPath . $recordVoiceFileName;

    $ret = move_uploaded_file(
        $tempVoiceFilePath,
        $recordVoiceFilePath
    );

    if ($ret === false) {
        writeErrorLogAndDie(
            'Upload fail.'
        );
    }

    $duration = null;

    try {
        $duration = BasicTools::getDurationByFilePath(
            $recordVoiceFilePath
        );
    } catch (RuntimeException $e) {
        writeErrorLogAndDie(
            'RuntimeException in get duration: ' .
            $e->getMessage()
        );
    }

    DBCRecordVoices::registrationNewVoice(
        $pdo,
        $fromPhotostandId,
        $toPhotostandIdsArray,
        $recordVoiceFileName,
        $duration
    );

    echo 'Success.';
}

main();

