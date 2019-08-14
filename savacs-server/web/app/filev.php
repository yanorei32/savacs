<?php
    declare(
        strict_types = 1
    );

    require_once('./lib.php');

    function writeErrorLogAndDie(string $message)
    {
        http_response_code(500);
        header('Content-type: text/plain');
        echo $message;
        exit(1);
    }

    $fname = null;
    try {
        $fname = ApacheEnvironmentWrapper::getSafeFilenameByParams($_GET, 'f');
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

    $extension = explode('.', $fname)[1];

    $dir = $extension == 'aac' ? ContentsDirectoryPaths::getRecordVoices() : ContentsDirectoryPaths::getSelfyImages();

    $fspath = $dir->getFileSystemPath() . $fname;

    if (!file_exists($fspath)) {
        writeErrorLogAndDie('File not found: ' . $fname);
    }

    $webpath = $dir->getWebServerPath() . $fname;

?>
<!DOCTYPE HTML>
<html lang=en>
  <head>
    <title>SAVACS RecordPlayer</title>
    <meta name=viewport content=width=device-width,initial-scale=1>
    <style>
      html, body {
        margin: 0;
        width: 100%;
      }
      .container {
        font-family: monospace;
        max-width: 800px;
        margin: auto;
        width: calc(100%-20px);
        padding-left: 10px;
        padding-right: 10px;
      }
      audio, img {
        width: 100%;
      }
    </style>
  </head>
  <body>
    <div class=container>
      <h1>SAVACS ContentViewer</h1>
      <?php if ($extension == 'aac') { ?>
      <audio src=<?php echo $webpath; ?> controls autoplay>This browser doesn't support audio tag</audio>
      <?php } else { ?>
      <img src=<?php echo $webpath; ?>>
      <?php } ?>
    </div>
  </body>
</html>

