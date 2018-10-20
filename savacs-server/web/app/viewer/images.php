<?php

declare(
    strict_types = 1
);

require_once('../lib.php');

function writeErrorLogAndDie(string $message) : void
{
    http_response_code(500);
    header('Content-type: text/plain');
    echo $message;
    exit(1);
}

const MINUTES_OF_RANGE = 5;
$motionImageArray = null;
$range = null;

function main() : void
{
    global $motionImageArray;
    global $range;

    $password           = null;
    $cpuSerialNumber    = null;
    $baseDateTime       = null;

    try {
        $password = ApacheEnvironmentWrapper::getPasswordStringByParams(
            $_GET,
            'password'
        );

        $cpuSerialNumber = ApacheEnvironmentWrapper::getCpuSerialNumberByParams(
            $_GET,
            'cpu_serial_number'
        );

        $baseDateTime = ApacheEnvironmentWrapper::getDateTimeByParams(
            $_GET,
            'datetime'
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

    $photostandId = null;

    try {
        $photostandId = DBCPhotostand::getIdByCpuSerialNumberAndPassword(
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

    // TODO: Deep cloneの方法がわからないので鑑定対処
    $baseDateTime->modify(
        sprintf('-%d minutes', MINUTES_OF_RANGE)
    );
    $drBegin = new DateTime(
        $baseDateTime->format('Y-m-d H:i:s')
    );
    $baseDateTime->modify(
        sprintf('+%d minutes', MINUTES_OF_RANGE*2)
    );
    $drEnd = new DateTime(
        $baseDateTime->format('Y-m-d H:i:s')
    );

    $range = sprintf(
        '%s - %s<br>',
        $drBegin->format('Y-m-d H:i:s'),
        $drEnd->format('Y-m-d H:i:s')
    );

    $motionImageArray = DBCMotionImage::getImagesByTimeRange(
        $pdo,
        $photostandId,
        $drBegin,
        $drEnd
    );
}

main();

?>
<!DOCTYPE HTML>
<html lang=ja>
    <head>
        <meta charset=utf-8>
        <title>Image List</title>
        <meta name=viewport content="width=device-width, initial-scale=1.0">
        <link rel=stylesheet href=css/pure-min.css>
    </head>
    <body>
        <h1>Image List</h1>
        <p>Range is <?php echo $range; ?></p>
        <p>Found images count: <?php echo count($motionImageArray); ?></p>
        <h2>Images</h2>
        <div>
            <?php foreach ($motionImageArray as $image) { ?>
                <div>
                    <a href=<?php
                        echo ContentsDirectoryPaths::getMotionImages()
                            ->getWebServerPath() . $image->getFileName();
                    ?>>
                        <p><?php echo $image->getCreatedAt(); ?></p>
                        <img src=<?php
                            echo ContentsDirectoryPaths::getMotionImages()
                                ->getWebServerPath() . $image->getThumbnailFileName();
                        ?>>
                    </a>
                </div>
            <?php } ?>
        </div>
    </body>
</html>


