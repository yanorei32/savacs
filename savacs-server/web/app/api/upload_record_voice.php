<?php

declare(
    strict_types = 1
);

require_once('../lib.php');

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

    $fromPhotostandId = null;

    try {
        $fromPhotostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
            $pdo,
            $cpuSerialNumber,
            $password
        );
    } catch (RuntimeException $e) {
        BasicTools::writeErrorLogAndDie(
            'RuntimeException in Authorization: ' .
            $e->getMessage()
        );
    } catch (RangeException $e) {
        BasicTools::writeErrorLogAndDie(
            'RangeException in Authorization: ' .
            $e->getMessage()
        );
    }

    if (!isActiveAssociations($pdo, $fromPhotostandId, $toPhotostandIdsArray)) {
        BasicTools::writeErrorLogAndDie(
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
        BasicTools::writeErrorLogAndDie(
            'OutOfBoundsException in Upload file: ' .
            $e->getMessage()
        );
    } catch (UnexpectedValueException $e) {
        BasicTools::writeErrorLogAndDie(
            'UnexpectedValueException in Upload file: ' .
            $e->getMessage()
        );
    }

    $uniqueFileName = BasicTools::generateUniqueFileNameByFilePath(
        $tempVoiceFilePath
    );

    $recordVoicesDirectoryInfo = ContentsDirectoryPaths::getRecordVoices();

    $dirPath = $recordVoicesDirectoryInfo->getFileSystemPath();

    $recordVoiceFileName = $uniqueFileName . '.aac';
    $recordVoiceFilePath = $dirPath . $recordVoiceFileName;

    $ret = move_uploaded_file(
        $tempVoiceFilePath,
        $recordVoiceFilePath
    );

    if ($ret === false) {
        BasicTools::writeErrorLogAndDie(
            'Upload fail.'
        );
    }

    $duration = null;

    try {
        $duration = BasicTools::getDurationByFilePath(
            $recordVoiceFilePath
        );
    } catch (RuntimeException $e) {
        BasicTools::writeErrorLogAndDie(
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

    $fromPhotostandDisplayName = DBCPhotostand::getDisplayNameByPhotostandID(
        $pdo,
        $fromPhotostandId
    );

    $toPhotostandDisplayNamesArray = Array();

    foreach ( $toPhotostandIdsArray as $id ) {
        Notification::localUploadedNotification(
            $fromPhotostandDisplayName,
            NotificationType::RECORD,
            $recordVoicesDirectoryInfo->getWebServerPath() .
                $recordVoiceFileName,
            DBCNotificationEmail::getEmailAddressesFromPhotostandId(
                $pdo,
                $id,
                NotificationType::RECORD
            )
        );

        $toPhotostandDisplayNamesArray[] = DBCPhotostand::getDisplayNameByPhotostandID(
            $pdo,
            $id
        );
    }

    Notification::globalUploadedNotification(
        $fromPhotostandDisplayName,
        $toPhotostandDisplayNamesArray,
        'Record',
        $recordVoicesDirectoryInfo->getWebServerPath() .
            $recordVoiceFileName
    );


    echo 'Success.';
}

main();

