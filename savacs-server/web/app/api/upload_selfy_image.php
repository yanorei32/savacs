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

    $tempImageFilePath = null;

    try {
        $tempImageFilePath = ApacheEnvironmentWrapper::getJPEGImageByFilesParams(
            $_FILES,
            'selfyImage'
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
        $tempImageFilePath
    );

    $selfyImagesDirectoryInfo = ContentsDirectoryPaths::getSelfyImages();
    $dirPath = $selfyImagesDirectoryInfo->getFileSystemPath();

    $selfyImageFileName = $uniqueFileName . '.jpg';
    $selfyImageFilePath = $dirPath . $selfyImageFileName;
    $selfyImageThumbnailFileName = $uniqueFileName . '_thumb.jpg';
    $selfyImageThumbnailFilePath = $dirPath . $selfyImageThumbnailFileName;

    $ret = move_uploaded_file(
        $tempImageFilePath,
        $selfyImageFilePath
    );

    if ($ret === false) {
        writeErrorLogAndDie(
            'Upload fail.'
        );
    }

    BasicTools::createThumbnail(
        $selfyImageFilePath,
        $selfyImageThumbnailFilePath
    );

    if (!file_exists($selfyImageThumbnailFilePath)) {
        unlink($selfyImageFilePath);

        writeErrorLogAndDie(
            'Failed to creation thumbnail file'
        );
    }

    DBCSelfyImage::registrationNewImage(
        $pdo,
        $fromPhotostandId,
        $toPhotostandIdsArray,
        $selfyImageFileName,
        $selfyImageThumbnailFileName
    );

    WebhookTools::globalUploadedNotification(
        $cpuSerialNumber,
        'Selfy',
        $selfyImagesDirectoryInfo->getWebServerPath() .
            $selfyImageFileName
    );

    echo 'Success.';
}

main();

