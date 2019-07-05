<?php

declare(
    strict_types = 1
);

require_once('../lib.php');

const NEW_GROUP_THRESHOLD_SEC = 5;

function main()
{
    $password           = null;
    $cpuSerialNumber    = null;
    $noiseLevel         = null;
    $changedPixel       = null;
    $areaCenterX        = null;
    $areaCenterY        = null;
    $areaWidth          = null;
    $areaHeight         = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_POST,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_POST,
            'cpuSerialNumber'
        );

        $noiseLevel = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'noiseLevel'
        );

        $changedPixel = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'changedPixel'
        );

        $areaCenterX = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'areaCenterX'
        );

        $areaCenterY = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'areaCenterY'
        );

        $areaWidth = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'areaWidth'
        );

        $areaHeight = ApacheEnvironmentWrapper::getIntValueByParams(
            $_POST,
            'areaHeight'
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

    try {
        $tempImageFilePath = ApacheEnvironmentWrapper::getJPEGImageByFilesParams(
            $_FILES,
            'motionImage'
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
        $tempImageFilePath
    );

    $dirPath = (ContentsDirectoryPaths::getMotionImages())->getFileSystemPath();

    $motionImageFileName = $uniqueFileName . '.jpg';
    $motionImageFilePath = $dirPath . $motionImageFileName;
    $motionImageThumbnailFileName = $uniqueFileName . '_thumb.jpg';
    $motionImageThumbnailFilePath = $dirPath . $motionImageThumbnailFileName;

    $ret = move_uploaded_file(
        $tempImageFilePath,
        $motionImageFilePath
    );

    if ($ret === false) {
        BasicTools::writeErrorLogAndDie(
            'Upload fail.'
        );
    }

    BasicTools::createThumbnail(
        $motionImageFilePath,
        $motionImageThumbnailFilePath
    );

    if (!file_exists($motionImageThumbnailFilePath)) {
        unlink($motionImageFilePath);

        BasicTools::writeErrorLogAndDie(
            'Failed to creation thumbnail file'
        );
    }

    $createNewGroup = false;
    $gid = null;

    try {
        $latestImage = DBCMotionImage::getLatestImage($pdo, $fromPhotostandId);
        $gid = $latestImage->getGroupId();

        $createdAt = new DateTime(
            $latestImage->getCreatedAt(),
            new DateTimeZone('Asia/Tokyo')
        );

        $now = new DateTime();

        $diff = ($now->getTimestamp() - $createdAt->getTimestamp());
        assert($diff >= 0, 'diff seconds variable is negative value');

        $createNewGroup = NEW_GROUP_THRESHOLD_SEC < $diff;
    } catch (RuntimeException $e) {
        // Probably, DB has not image from this photostand.
        if ($e->getMessage() === "Image not found.") {
            $createNewGroup = true;
        } else {
            // Oops
            throw $e;
        }
    }

    if ($createNewGroup) {
        $gid = DBCMotionImageGroup::createNewGroup($pdo);
    }

    $mdi = new MotionDetectedInfo(
        $noiseLevel,
        $changedPixel,
        $areaCenterX,
        $areaCenterY,
        $areaWidth,
        $areaHeight
    );

    DBCMotionImage::registrationNewImage(
        $pdo,
        $fromPhotostandId,
        $gid,
        $motionImageFileName,
        $motionImageThumbnailFileName,
        $mdi
    );

    echo 'Success.';
}

main();

