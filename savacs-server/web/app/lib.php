<?php

declare(
    strict_types = 1
);

class Notification
{
    /**
     * Post webhook message
     *
     * @param string    $message          Webhook message
     * @param string    $userWebhookURI   User webhook uri
     *
     * @return bool $isSuccess
     */
    private static function _postWebhook(
        string $userWebhookURI,
        string $message
    ) : bool {
        $options = array(
            'http' => array(
                'method'    => 'POST',
                'header'    => 'Content-Type: application/json',
                'content'   => json_encode(array(
                    'username'  => 'SAVACS',
                    'text'      => $message
                ))
            )
        );

        $reponse = file_get_contents(
            $userWebhookURI,
            false,
            stream_context_create($options)
        );

        return $reponse === 'ok';
    }

    /**
     * Global uploaded notification - Webhook impl
     *
     * @param string $message
     *
     * @return bool $isSuccess
     */
    private static function _globalUploadedNotificationWebhookImpl(
        string  $message
    ) : bool {
        $globalWebhookURI = getenv('GLOBAL_WEBHOOK');

        if ($globalWebhookURI === false) {
            return true;
        }

        $isSuccess = self::_postWebhook(
            $globalWebhookURI,
            $message
        );

        return $isSuccess;
    }

    /**
     * Email Send To User
     *
     * @param string    $message
     * @param string    $title
     * @param array     $bccEmails
     *
     * @return bool     $isSuccess
     */
    public static function emailSendToUser(
        string  $message,
        string  $title,
        array   $bccEmails
    ) : bool {
        $savacsEmail = getenv('SAVACS_EMAIL');

        if ($savacsEmail === false) {
            return true;
        }

        $header = "";
        foreach ($bccEmails as $bccEmail) {
            $header .= "Bcc: $bccEmail <$bccEmail>\n";
        }

        $isSuccess = mb_send_mail($savacsEmail, $title, $message, $header);

        return $isSuccess;
    }

    /**
     * Global uploaded notification Email impl
     *
     * @param string $message
     *
     * @return bool $isSuccess
     */
    private static function _globalUploadedNotificationEmailImpl(
        string  $message,
        string  $title
    ) : bool {
        $globalEmail = getenv('GLOBAL_EMAIL');

        if ($globalEmail === false) {
            return true;
        }

        $isSuccess = mb_send_mail($globalEmail, $title, $message);

        return $isSuccess;
    }


    /**
     * Global uploaded notification
     *   - for debug / management
     *
     * @param string    $fromPhotostandDisplayName  DisplayName
     * @param array     $toPhotostandDisplayNames   DisplayNames
     * @param string    $notificationType           Notification type
     * @param string    $path                       Web path
     *
     * @return bool $isSuccess
     */
    public static function globalUploadedNotification(
        string $fromPhotostandDisplayName,
        array  $toPhotostandDisplayNames,
        string $notificationType,
        string $path
    ) : bool {
        $message = '';

        $toPhotostandDisplayNames = implode(', ', $toPhotostandDisplayNames);

        $title = "[SAVACS] From $fromPhotostandDisplayName New $notificationType";
        $message .= "From: $fromPhotostandDisplayName\n";
        $message .= "To: $toPhotostandDisplayNames\n";
        $message .= "Type: $notificationType\n";

        $globalServer = getenv('SAVACS_GLOBAL');

        if ($globalServer !== false) {
            $uri = $globalServer. $path;
            $message .= "Global: $uri\n";
        }

        $localServer = getenv('SAVACS_LOCAL');

        if ($localServer !== false) {
            $uri = $localServer . $path;
            $message .= "Local: $uri\n";
        }

        $webhook = self::_globalUploadedNotificationWebhookImpl($message);
        $email = self::_globalUploadedNotificationEmailImpl($message, $title);

        return $webhook && $email;
    }

    /**
     * Local uploaded notification
     *
     * @param string    $fromPhotostandDisplayName  DisplayName
     * @param int       $notificationType           Notification type
     * @param string    $filename                   Filename
     * @param array     $emailAddresses             Email addresses
     *
     * @return bool $isSuccess
     */
    public static function localUploadedNotification(
        string  $fromPhotostandDisplayName,
        int     $notificationType,
        string  $filename,
        array   $emailAddresses
    ) : bool {
        $title = sprintf(
            "[SAVACS] %sからの新着%s",
            $fromPhotostandDisplayName,
            NotificationType::STR_JP[$notificationType]
        );

        $message = sprintf(
            "%sからの新着%sがあります。\n",
            $fromPhotostandDisplayName,
            NotificationType::STR_JP[$notificationType]
        );

        $globalServer = getenv('SAVACS_GLOBAL');
        $baseDirectory = getenv('SAVACS_ALIAS');

        if ($baseDirectory === false) {
            $baseDirectory = '/';
        } else {
            $baseDirectory .= '/';
        }

        if ($globalServer !== false) {
            $uri = $globalServer . $baseDirectory . 'filev.php?f=' . $filename;
            $message .= "\nURL: $uri\n";
        }

        return self::emailSendToUser($message, $title, $emailAddresses);
    }
}

class ContentsDirectoryPath
{
    // base paths
    private static $_fileSystemBasePath = '/var/www/contents/';
    private static $_contentsDirName    = 'contents/';

    private $_directoryName;

    /**
     * Get file system path
     *
     * @return string $fileSystemPath
     */
    public function getFileSystemPath() : string
    {
        $fileSystemPath = self::$_fileSystemBasePath . $this->_directoryName;

        return $fileSystemPath;
    }

    /**
     * Get web server path
     *
     * @return string $webServerPath
     */
    public function getWebServerPath() : string
    {
        $baseDirectory = getenv('SAVACS_ALIAS');

        if ($baseDirectory === false) {
            $baseDirectory = '/';
        } else {
            $baseDirectory .= '/';
        }

        $webServerPath = $baseDirectory .
            self::$_contentsDirName . $this->_directoryName;

        return $webServerPath;
    }

    /**
     * Get web server path without prefix
     *
     * @return string $webServerPathWithoutPrefix
     */
    public function getWebServerPathWithoutPrefix() : string
    {
        $webServerPathWithoutPrefix = '/' . self::$_contentsDirName . $this->_directoryName;

        return $webServerPathWithoutPrefix;
    }

    /**
     * Constructor
     *
     * @param string $directoryName
     */
    public function __construct(string $directoryName)
    {
        $this->_directoryName = $directoryName;
    }
}

class ContentsDirectoryPaths
{
    /**
     * Get selfy images directory prefix
     *
     * @return string $selfyImageDirectoryPath
     */
    public static function getSelfyImages() : ContentsDirectoryPath
    {
        return new ContentsDirectoryPath('selfy_images/');
    }

    /**
     * Get motion images directory prefix
     *
     * @return string $motionImageDirectoryPath
     */
    public static function getMotionImages() : ContentsDirectoryPath
    {
        return new ContentsDirectoryPath('motion_images/');
    }

    /**
     * Get record voices directory prefix
     *
     * @return string $recordVoiceDirectoryPath
     */
    public static function getRecordVoices() : ContentsDirectoryPath
    {
        return new ContentsDirectoryPath('record_voices/');
    }
}

class ApacheEnvironmentWrapper
{
    /**
     * Get ANY VALUE by params
     *   WARN: This function dows not type check.
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  mixed   $parameter
     *
     * @throws  OutOfBoundsException
     */
    private static function _getAnyValueByParams(
        array   $parameters,
        string  $parameterName
    ) {
        if (!isset($parameters[$parameterName])) {
            throw new OutOfBoundsException(
                "Parameter '$parameterName' not found."
            );
        }

        return $parameters[$parameterName];
    }

    /**
     * Is valid int array
     *
     * @param   string  $intArrayString
     *
     * @return  bool    $isValid
     */
    private static function _isValidIntArrayString(
        string $intArrayString
    ) : bool {
        $ret = preg_match('/^(\d+,)*\d+$/', $intArrayString);

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Is safe filename
     *
     * @param   string  $filename
     *
     * @return  bool    $isValid
     */
    private static function _isSafeFilename(
        string  $filename
    ) : bool {
        $ret = preg_match(
            '/^\d+_[0-9a-f_]+\.(jpg|aac)$/',
            $filename
        );

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get safe filename by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  string  $safe filename
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getSafeFilenameByParams(
        array   $parameters,
        string  $parameterName
    ) : string {
        $filename= self::_getAnyValueByParams(
            $parameters,
            $parameterName
        );

        if (!self::_isSafeFilename($filename)) {
            throw new UnexpectedValueException(
                'This filename is unsafe.'
            );
        }

        return $filename;
    }

    /**
     * Is valid email (HTML5 input[type=email] like)
     *
     * @param   string  $emailAddress
     *
     * @return  bool    $isValid
     */
    private static function _isValidEmailHTML5(
        string  $emailAddress
    ) : bool {
        $ret = preg_match(
            '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/',
            $emailAddress
        );

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get email address by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     * @param   bool    $useRFC822
     *
     * @return  string  $emailAddress
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getEmailAddressByParams(
        array   $parameters,
        string  $parameterName,
        bool    $useRFC822 = true
    ) : string {
        $emailAddress = self::_getAnyValueByParams(
            $parameters,
            $parameterName
        );

        if (!self::_isValidEmailHTML5($emailAddress))
            throw new UnexpectedValueException(
                "Parameter '$parameterName' is not valid (HTML5)"
            );

        if ($useRFC822)
            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL))
                throw new UnexpectedValueException(
                    "Parameter '$parameterName' is not valid (RFC822)"
                );

        return $emailAddress;
    }

    /**
     * Get bool by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  bool    $boolean
     */
    public static function getBoolByParams(
        array   $parameters,
        string  $parameterName
    ) : bool {
        return isset($parameters[$parameterName]);
    }

    /**
     * Get int array by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  array   $intArray
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getIntArrayByParams(
        array   $parameters,
        string  $parameterName
    ) : array {
        $intArrayString = self::_getAnyValueByParams(
            $parameters,
            $parameterName
        );

        if (!self::_isValidIntArrayString($intArrayString)) {
            throw new UnexpectedValueException(
                "Parameter '$parameterName' is not valid"
            );
        }

        $intArray = array_map(
            'intval',
            explode(',', $intArrayString)
        );

        return $intArray;
    }

    /**
     * Is valid int
     *
     * @param   string  $intString
     *
     * @return  bool    $isValid
     */
    private static function _isValidIntString(
        string $intString
    ) : bool {
        $ret = preg_match('/^\d+$/', $intString);

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get int value by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  int     $value
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getIntValueByParams(
        array   $parameters,
        string  $parameterName
    ) : int {
        $value = self::_getAnyValueByParams($parameters, $parameterName);

        if (!self::_isValidIntString($value)) {
            throw new UnexpectedValueException(
                "Parameter '$parameterName' is not valid"
            );
        }

        return intval($value);
    }

    /**
     * Get any file by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  string  $tempFilePath
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    private static function _getAnyFileByParams(
        array   $parameters,
        string  $parameterName
    ) : string {
        $file = self::_getAnyValueByParams(
            $parameters,
            $parameterName
        );

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;

            case UPLOAD_ERR_INI_SIZE:
                throw new UnexpectedValueException('Upload failed. The uploaded file exceeds the upload_max_filesize directive in php.ini');
                break;

            case UPLOAD_ERR_FORM_SIZE:
                throw new UnexpectedValueException('Upload failed. The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                break;

            case UPLOAD_ERR_PARTIAL:
                throw new UnexpectedValueException('Upload failed. The uploaded file was only partially uploaded.');
                break;

            case UPLOAD_ERR_NO_FILE:
                throw new UnexpectedValueException('Upload failed. No file was uploaded.');
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                throw new UnexpectedValueException('Upload failed. Missing a temporary folder.');
                break;

            case UPLOAD_ERR_EXTENSION:
                throw new UnexpectedValueException('Upload failed. PHP extension stopped the file uploaded.');
                break;
        }

        $tempFilePath = $file['tmp_name'];

        if (!is_uploaded_file($tempFilePath)) {
            throw new UnexpectedValueException('Upload failed. Unknown issue.');
        }

        return $tempFilePath;
    }


    /**
     * Get AAC Audio by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  string  $tempFilePath
     *
     */
    public static function getAACAudioByFilesParams(
        array   $parameters,
        string  $parameterName
    ) : string {
        $tempFilePath = self::_getAnyFileByParams(
            $parameters,
            $parameterName
        );

        if (mime_content_type($tempFilePath) !== 'audio/x-hx-aac-adts') {
            throw new UnexpectedValueException(
                'Uploaded file is not a AAC file.'
            );
        }

        return $tempFilePath;
    }

    /**
     * Get JPEG image by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  string  $tempFilePath
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getJPEGImageByFilesParams(
        array   $parameters,
        string  $parameterName
    ) : string {
        $tempFilePath = self::_getAnyFileByParams(
            $parameters,
            $parameterName
        );

        if (mime_content_type($tempFilePath) !== 'image/jpeg') {
            throw new UnexpectedValueException(
                'Uploaded file is not a JPEG file.'
            );
        }

        return $tempFilePath;
    }

    /**
     * Is valid password string
     *
     * @param   string  $password
     *
     * @return  bool    $isValid
     */
    private static function _isValidPassword(
        string $password
    ) : bool {
        $ret = preg_match('/^[\w!"#$%&-^~\\|`[\]{}<> ]+$/', $password);

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get password string by params
     *
     * @param   array   $params
     * @param   string  $paramName
     *
     * @return  string  $password
     *
     * @throws OutOfBoundsException
     * @throws UnexpectedValueException
     */
    public static function getPasswordStringByParams(
        array   $params,
        string  $paramName
    ) : string {
        $password = self::_getAnyValueByParams($params, $paramName);

        if (!self::_isValidPassword($password)) {
            throw new UnexpectedValueException(
                "Parameter '$paramName' is not valid"
            );
        }

        return $password;
    }

    /**
     * Is valid cpu serial number
     *
     * @param   string  $cpuSerialNumber
     *
     * @return  bool    $isValid
     */
    private static function _isValidCpuSerialNumber(
        string $cpuSerialNumber
    ) : bool {
        $ret = preg_match('/^[0-9a-f]{16}$/', $cpuSerialNumber);

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get cpu serial number string by params
     *
     * @param   array   $params
     * @param   string  $paramName
     *
     * @return  string  $cpuSerialNumber
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getCpuSerialNumberByParams(
        array   $params,
        string  $paramName
    ) : string {
        $cpuSerialNumber = self::_getAnyValueByParams($params, $paramName);

        if (!self::_isValidCpuSerialNumber($cpuSerialNumber)) {
            throw new UnexpectedValueException(
                "Parameter '$paramName' is not valid"
            );
        }

        return $cpuSerialNumber;
    }

    /**
     * Is valid date time string
     *
     * @param   string      $dateTimeString
     *
     * @return  bool        $isValid
     *
     * @throws  UnexpectedValueException
     */
    public static function _isValidDateTimeString(
        string  $dateTimeString
    ) : bool {
        $ret = preg_match('/^\d{4}(-\d{2}){2} \d{2}(:\d{2}){2}$/', $dateTimeString);

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get date time by params
     *
     * @param   array       $params
     * @param   string      $paramName
     *
     * @return  DateTime    $dateTime
     *
     * @throws UnexpectedValueException
     * @throws OutOfBoundsException
     */
    public static function getDateTimeByParams(
        array   $params,
        string  $paramName
    ) : DateTime {
        $dateTimeString = self::_getAnyValueByParams($params, $paramName);

        if (!self::_isValidDateTimeString($dateTimeString)) {
            throw new UnexpectedValueException(
                "Parameter '$paramName' is not valid"
            );
        }

        return new DateTime($dateTimeString);
    }

    /**
     * Is valid float value string
     *
     * @param   string  $floatValueString
     *
     * @return  bool    $isValid
     *
     * @throws UnexpectedValueException
     */
    public static function _isValidFloatValueString(
        string $floatValueString
    ) : bool {
        $ret = preg_match('/^\d+(\.\d+)?$/', $floatValueString);

        if ($ret === 1) {
            return true;
        } elseif ($ret === 0) {
            return false;
        } elseif ($ret === false) {
            assert(false, 'Regex pattern is not valid.');
        }
    }

    /**
     * Get float by params
     *
     * @param   array   $params
     * @param   stirng  $paramName
     *
     * @return  float   $floatValue
     *
     * @throws  UnexpectedValueException
     * @throws  OutOfBoundsException
     */
    public static function getFloatByParams(
        array $params,
        string $paramName
    ) : float {
        $floatString = self::_getAnyValueByParams($params, $paramName);

        if (!self::_isValidFloatValueString($floatString)) {
            throw new UnexpectedValueException(
                "Parameter '$paramName' is not valid"
            );
        }

        return floatval($floatString);
    }

    /**
     * Get unicode string by params
     *
     * @param   array   $params
     * @param   string  $paramName
     *
     * @return  string  $stringValue
     *
     * @throws  UnexpectedValueException
     * @throws  OutOfBoundsException
     */
    public static function getUnicodeStringByParams(
        array   $params,
        string  $paramName
    ) : string {
        $rawString = self::_getAnyValueByParams($params, $paramName);

        if (mb_check_encoding($rawString, 'UTF-8') !== true) {
            throw new UnexpectedValueException(
                "Parameter '$paramName' is not valid unicode string"
            );
        }

        return $rawString;
    }
}

class BasicTools
{
    /**
     * Write error log and Die
     *
     * @param string    $messgae
     * @param int       $statusCode
     */
    public static function writeErrorLogAndDie(
        string  $message,
        int     $statusCode = 500
    ) : void {
        // for client
        http_response_code($statusCode);
        header('Content-type: text/plain');
        echo $message;

        // for server
        error_log($message);

        exit(1);
    }


    /**
     * Create thumbnail
     *
     * @param string    $originalFilePath
     * @param string    $outputFilePath
     * @param int       $width (default: 160px)
     * @param int       $height (default: 120px)
     * @param int       $quality (default: 50)
     */
    public static function createThumbnail(
        string  $originalFilePath,
        string  $outputFilePath,
        int     $width = 160,
        int     $height = 120,
        int     $quality = 50
    ) : void {
        $size = $width.'x'.$height;

        $resizeCommand = sprintf(
            'convert -geometry %s -quality %d %s %s',
            $size,
            $quality,
            $originalFilePath,
            $outputFilePath
        );

        shell_exec($resizeCommand);

        chmod($outputFilePath, 0644);
    }

    /**
     * Get duration by file path
     *
     * @param string $targetFilePath
     *
     * @return int $duration
     *
     * @throws RuntimeException
     */
    public static function getDurationByFilePath(
        string $targetFilePath
    ) : int {
        $stdoutArray = array();
        $statusCode = null;

        exec(
            "ffprobe $targetFilePath -show_entries format -print_format json 2>/dev/null",
            $stdoutArray,
            $statusCode
        );

        assert($statusCode === 0, 'ffprobe fail.');

        $jsonString = implode($stdoutArray);
        $json = json_decode($jsonString, true);

        if (!isset($json['format'])) {
            throw new RuntimeException('Not set key format.');
        }

        if (!isset($json['format']['duration'])) {
            throw new RuntimeException('Not set key duration.');
        }

        return intval(round(floatval($json['format']['duration'])));
    }

    /**
     * Generate unique file name by file path
     *
     * @param string    $targetFilePath
     *
     * @return string   $generatedFileName
     *
     * @throws UnexpectedValueException
     */
    public static function generateUniqueFileNameByFilePath(
        string $targetFilePath
    ) : string {
        $md5hash = md5_file($targetFilePath);

        if ($md5hash === false) {
            throw new UnexpectedValueException('Failed to calc md5 hash');
        }

        $time = round(microtime(true) * 1000);

        $generatedFileName = sprintf(
            '%s_%s',
            $time,
            $md5hash
        );

        return $generatedFileName;
    }
}

class DBCommon
{
    private static $_DSN =
        'mysql:' .
        'host=db;' .
        'dbname=savacs_db;' .
        'charset=utf8mb4';

    private static $_USERNAME   = 'savacs';
    private static $_PASSWORD   = '';
    private static $_OPTIONS    = array(
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES  => false,
    );

    /**
     * Create database connection
     *
     * @return PDO DB Connection
     */
    public static function createConnection() : PDO
    {
        return new PDO(
            self::$_DSN,
            self::$_USERNAME,
            self::$_PASSWORD,
            self::$_OPTIONS
        );
    }
}

class DBCPhotostand
{
    /**
     * Is active associations
     *
     * @param PDO   $pdo                PDO object
     * @param int   $formPhotostandId   From Photostand Id
     * @param array $toPhotosntandIds   To Photostand Ids
     *
     * @return bool isValid?
     */
    public static function isActiveAssociations(
        PDO     $pdo,
        int     $fromPhotostandId,
        array   $toPhotosntandIds
    ) : bool {
        foreach ($toPhotostandIdsArray as $toPhotostandId) {
            $ret = DBCPhotostand::isActiveAssociation(
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

    /**
     * Get display name by photostand ID
     *
     * @param PDO   $pdo    PDO object
     * @param int   $ids    Photostand ID (range validated)
     *
     * @return array DisplayNames
     */
    public static function getDisplayNameByPhotostandID(
        PDO $pdo,
        int $id
    ) : string {
        $sql = <<<EOT
SELECT
    `display_name`
FROM
    `photostands`
WHERE
    `id` = :id
LIMIT
    1
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':id',
            $id,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $row = $statement->fetch(PDO::FETCH_NUM);
        $statement->closeCursor();

        return $row[0];
    }


    /**
     * Is active association by photostand IDs
     *
     * @param PDO   $pdo    PDO object
     * @param int   $idA    Photostand A ID (range validated)
     * @param int   $idB    Photostand B ID (range validated)
     */
    public static function isActiveAssociationByPhotostandIds(
        PDO $pdo,
        int $idA,
        int $idB
    ) : bool {
        $sql = <<<EOT
SELECT
    `photostand_a`,
    `photostand_b`
FROM
    `photostands__photostands`
WHERE
    (
        `photostand_a` = :photostand_a_0
        and
        `photostand_b` = :photostand_b_0
    ) or (
        `photostand_a` = :photostand_b_1
        and
        `photostand_b` = :photostand_a_1
    )
LIMIT
    1
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':photostand_a_0',
            $idA,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_a_1',
            $idA,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_b_0',
            $idB,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_b_1',
            $idB,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $row = $statement->fetch(PDO::FETCH_NUM);
        $statement->closeCursor();

        return !($row === false);
    }

    /**
     * Get associated photostands
     *
     * @param PDO   $pdo    PDO object
     * @param int   $id     Photostand ID
     *
     * @return array $photostandInfos
     */
    public static function getAssociatedPhotostands(
        PDO $pdo,
        int $photostandId
    ) : array {
        $sql = <<<EOT
WITH `associated_photostands` AS (
    SELECT
        `photostand_b`
    FROM
        `photostands__photostands`
    WHERE
        `photostand_a` = :photostand_id_0
    UNION
    SELECT
        `photostand_a`
    FROM
        `photostands__photostands`
    WHERE
        `photostand_b` = :photostand_id_1
)

SELECT
    `associated_photostands`.`photostand_b` as `id`,
    `photostands`.`display_name`
FROM `associated_photostands`
    INNER JOIN `photostands`
        ON `associated_photostands`.`photostand_b` = `photostands`.`id`
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':photostand_id_0',
            $photostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_id_1',
            $photostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $statement->closeCursor();

        return $rows;
    }

    /**
     * Get active associations
     *
     * @param PDO   $pdo    PDO object
     * @param int   $id     Photostand ID
     *
     * @return array $photostandIds
     */
    public static function getActiveAssociations(
        PDO $pdo,
        int $photostandId
    ) : array {
        $sql = <<<EOT
SELECT
    *
FROM
    `photostands__photostands`
WHERE
    `photostand_a` = :photostand_id_0
    or
    `photostand_b` = :photostand_id_1
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':photostand_id_0',
            $photostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_id_1',
            $photostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $rows = $statement->fetchAll(PDO::FETCH_NUM);

        $statement->closeCursor();

        $relatedPhotostandIds = array();

        foreach ($rows as $row) {
            $relatedPhotostandIds = array_merge(
                $relatedPhotostandIds,
                $row
            );
        }

        $relatedPhotostandIds = array_unique($relatedPhotostandIds);

        unset(
            $relatedPhotostandIds[array_search(
                $photostandId,
                $relatedPhotostandIds
            )]
        );

        return $relatedPhotostandIds;
    }

    /**
     * Create association by photostand IDs
     *
     * @param PDO   $pdo    PDO object
     * @param int   $idA    Photostand A ID (range checked)
     * @param int   $idB    Photostand B ID (range checked)
     */
    public static function createAssociationByPhotostandIds(
        PDO $pdo,
        int $idA,
        int $idB
    ) : void {
        $sql= <<<EOT
INSERT
INTO `photostands__photostands` (
    `photostand_a`,
    `photostand_b`
)
VALUES
(
    :photostand_a,
    :photostand_b
);
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':photostand_a',
            $idA,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_b',
            $idB,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
    }

    /**
     * Get row by cpu_serial_number
     *
     * @param PDO       $pdo                PDO object
     * @param string    $cpuSerialNumber    Raspberry Pi CPU Serial Number
     *
     * @return array $row
     *
     * @throws RangeException
     */
    private static function getRowByCpuSerialNumber(
        PDO     $pdo,
        string  $cpuSerialNumber
    ) : array {
        $sql = <<<EOT
SELECT
    `id`,
    `password_hash`
FROM
    `photostands`
WHERE
    `cpu_serial_number` = :cpu_serial_number
LIMIT
    1
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':cpu_serial_number',
            $cpuSerialNumber,
            PDO::PARAM_STR
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $row = $statement->fetch(PDO::FETCH_NUM);

        $statement->closeCursor();

        if ($row === false) {
            throw new RangeException('$cpuSerialNumber not found.');
        }

        return $row;
    }

    /**
     * Get id by cpu_serial_number and password
     *
     *
     * @param PDO       $pdo                PDO object
     * @param string    $cpuSerialNumber    Validated Raspberry Pi CPU Serial Number
     * @param string    $password           Validated Password
     *
     * @return int $id
     *
     * @throws RuntimeException
     * @throws RangeException
     */
    public static function getIdByCpuSerialNumberAndPassword(
        PDO     $pdo,
        string  $cpuSerialNumber,
        string  $password
    ) : int {
        $row = self::getRowByCpuSerialNumber($pdo, $cpuSerialNumber);

        if (!password_verify($password, $row[1])) {
            throw new RuntimeException('$password is not match.');
        }

        return $row[0];
    }

    /**
     * Update display name by Id
     *
     * @param PDO       $pdo                PDO object
     * @param int       $photostandId       Photostand Id
     * @param string    $newDisplayName     New display name
     */
    public static function updateDisplayNameById(
        PDO     $pdo,
        int     $photostandId,
        string  $newDisplayName
    ) : void {
        $sql = <<<EOT
UPDATE
    `photostands`
SET
    `display_name` = :new_display_name
WHERE
    `id` = :id
;
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':new_display_name',
            $newDisplayName,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':id',
            $photostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
    }

    /**
     * Registration photostand
     *
     * @param PDO       $pdo                PDO object
     * @param string    $cpuSerialNumber    Validated Raspberry Pi CPU Serial Number
     * @param string    $password           Validated Password
     * @param string    $displayName        Validated DisplayName
     */
    public static function registrationByCpuSerialNumberAndPasswordAndDisplayName(
        PDO     $pdo,
        string  $cpuSerialNumber,
        string  $password,
        string  $displayName
    ) : void {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        assert(!($passwordHash === false), 'Failed to calc hash.');

        $sql = <<<EOT
INSERT
INTO `photostands` (
    `cpu_serial_number`,
    `password_hash`,
    `display_name`
)
VALUES
(
    :cpu_serial_number,
    :password_hash,
    :display_name
);
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':cpu_serial_number',
            $cpuSerialNumber,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':password_hash',
            $passwordHash,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':display_name',
            $displayName,
            PDO::PARAM_STR
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
    }

    /**
     * Get all value for debug
     *
     * @param PDO $pdo PDO object
     *
     * @return array cols
     */
    public static function getAllValue(
        PDO     $pdo
    ) : array {
        $sql = <<<EOT
SELECT
    `id`,
    `cpu_serial_number`,
    `display_name`
FROM
    `photostands`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $rows;
    }

    /**
     * Get all association for debug
     *
     * @param PDO $pdo PDO object
     *
     * @return array cols
     */
    public static function getAllAssociationValue(
        PDO     $pdo
    ) : array {
        $sql= <<<EOT
SELECT
    `photostand_a`,
    `photostand_b`
FROM
    `photostands__photostands`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $rows;
    }
}

class RecordVoice
{
    private $_fileName;
    private $_duration;
    private $_createdAt;
    private $_fromPhotostandId;

    /**
     * Get duration
     *
     * @return int $duration
     */
    public function getDuration() : int
    {
        return $this->_duration;
    }

    /**
     * Get file name
     *
     * @return string $fileName
     */
    public function getFileName() : string
    {
        return $this->_fileName;
    }

    /**
     * Get created at
     *
     * @return string $createdAt
     */
    public function getCreatedAt() : string
    {
        return $this->_createdAt;
    }

    /**
     * Get from photostand id
     *
     * @return int $photostandId
     */
    public function fromPhotostandId() : int
    {
        return $this->_fromPhotostandId;
    }

    /**
     * Constructor
     *
     * @param string    $fileName
     * @param int       $duration
     * @param string    $createdAt
     * @param int       $fromPhotostandId
     */
    public function __construct(
        string  $fileName,
        int     $duration,
        string  $createdAt,
        int     $fromPhotostandId
    ) {
        $this->_fileName            = $fileName;
        $this->_duration            = $duration;
        $this->_createdAt           = $createdAt;
        $this->_fromPhotostandId    = $fromPhotostandId;
    }
}

class DBCRecordVoices
{
    /**
     * Registration new voice
     *
     * @param PDO       $pdo                PDO object
     * @param int       $fromPhotostandId   From photostand ID
     * @param array     $toPhotostandIds    To photostand IDs (int array)
     * @param string    $fileName           voice file name
     * @param int       $duration           voice duration
     */
    public static function registrationNewVoice(
        PDO     $pdo,
        int     $fromPhotostandId,
        array   $toPhotostandIds,
        string  $fileName,
        int     $duration
    ) : void {
        $ret = $pdo->beginTransaction();
        assert(!($ret === false), 'Failed to begin transaction.');

        $sql = <<<EOT
INSERT
INTO `record_voices` (
    `file_name`,
    `duration`,
    `from_photostand_id`
)
VALUES
(
    :file_name,
    :duration,
    :from_photostand_id
);
EOT;
        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':file_name',
            $fileName,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':duration',
            $duration,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        $statement->closeCursor();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $recordVoicesTableId = $pdo->lastInsertId();

        $sql = <<<EOT
INSERT
INTO `record_voices__photostands` (
    `to_photostand_id`,
    `record_voices_id`
)
VALUES
(
    :to_photostand_id,
    :record_voices_id
);
EOT;

        foreach ($toPhotostandIds as $toPhotostandId) {
            $statement = $pdo->prepare($sql);
            assert(!($statement === false), 'Failed to prepare sql.');

            $ret = $statement->bindParam(
                ':to_photostand_id',
                $toPhotostandId,
                PDO::PARAM_INT
            ) && $statement->bindParam(
                ':record_voices_id',
                intval($recordVoicesTableId),
                PDO::PARAM_INT
            );
            assert(
                !($ret === false),
                'Failed to bind param.' .
                '$toPhotostandId is not an integer value.' .
                ' (from $toPhotostandIds array)'
            );

            $ret = $statement->execute();
            $statement->closeCursor();

            assert(
                !($ret === false),
                'Failed to execute statement. Propably SQL syntax error.'
            );
        }

        $ret = $pdo->commit();
        assert(!($ret === false), 'Failed to commit.');
    }

    /**
     * Get resentry record voices
     *
     * @param PDO       $pdo                PDO object
     * @param string    $uriPrefix          URI prefix
     * @param int       $toPhotostandId     To photostand ID
     * @param int       $limit              Limit
     *
     * @throws RuntimeException
     *
     * @return array $recordVoices (type RecordVoice)
     */
    public static function getResentryRecordVoices(
        PDO     $pdo,
        string  $uriPrefix,
        int     $toPhotostandId,
        int     $limit
    ) : array {
        $sql = <<<EOT
WITH `target_records` AS (
    SELECT
        *
    FROM
        `record_voices__photostands`
    WHERE
        `to_photostand_id` = :to_photostand_id
)

SELECT
    CONCAT(:uri_prefix, `record_voices`.`file_name`) as `uri`,
    `record_voices`.`duration`,
    `record_voices`.`created_at`,
    `record_voices`.`from_photostand_id`,
    `photostands`.`display_name` as `send_from`

FROM `target_records`
    LEFT JOIN `record_voices`
        ON `target_records`.`record_voices_id` =  `record_voices`.`id`
    INNER JOIN `photostands`
        ON `record_voices`.`from_photostand_id` = `photostands`.`id`

ORDER BY
    `record_voices`.`created_at`
    DESC

LIMIT
    :limit
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':to_photostand_id',
            $toPhotostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':limit',
            $limit,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':uri_prefix',
            $uriPrefix,
            PDO::PARAM_STR
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $statement->closeCursor();

        if ($rows === false) {
            throw new RuntimeException('Record voice not found.');
        }

        return $rows;
    }

    public static function debugGetRecordVoices(
        PDO     $pdo
    ) : array {
        $sql = <<<EOT
SELECT
    `id`,
    `file_name`,
    `duration`,
    `from_photostand_id`
FROM
    `record_voices`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $rows;
    }

    public static function debugGetRecordVoicesPhotostands(
        PDO     $pdo
    ) : array {
        $sql= <<<EOT
SELECT
    `to_photostand_id`,
    `record_voices_id`
FROM
    `record_voices__photostands`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $rows;
    }
}

class SelfyImage
{
    private $_fileName;
    private $_thumbnailFileName;
    private $_createdAt;

    /**
     * Get thumbnail file name
     *
     * @return string $thumbnailFileName
     */
    public function getThumbnailFileName() : string
    {
        return $this->_thumbnailFileName;
    }

    /**
     * Get file name
     *
     * @return string $fileName
     */
    public function getFileName() : string
    {
        return $this->_fileName;
    }

    /**
     * Get created at
     *
     * @return string $createdAt
     */
    public function getCreatedAt() : string
    {
        return $this->_createdAt;
    }

    /**
     * Constructor
     *
     * @param string $fileName
     * @param string $thumbnailFileName
     * @param string $createdAt
     */
    public function __construct(
        string $fileName,
        string $thumbnailFileName,
        string $createdAt
    ) {
        $this->_fileName            = $fileName;
        $this->_thumbnailFileName   = $thumbnailFileName;
        $this->_createdAt           = $createdAt;
    }
}

class NotificationType
{
    public const ALL    = 0;
    public const SELFY  = 1;
    public const RECORD = 2;

    public const STR_DB = array(
        self::ALL       => 'true',
        self::SELFY     => 'selfy_notification',
        self::RECORD    => 'record_notification'
    );

    public const STR_JP = array(
        self::ALL       => '_',
        self::SELFY     => '自撮り',
        self::RECORD    => '伝言'
    );
}

class DBCNotificationEmail
{
    /**
     * Get email addresses from photostand id 4 debug
     *
     * @param PDO $pdo
     * @param int $photostandId
     * @param int $type
     */
    public static function getEmailAddressesFromPhotostandId4debug(
        PDO $pdo,
        int $photostandId,
        int $notificationType
    ) : array {
        $notification_type_sql  = NotificationType::STR_DB[$notificationType];
        $sql = <<<EOT
SELECT
    `id`,
    `email`,
    `record_notification` as `record`,
    `selfy_notification` as `selfy`
FROM
    `notification_emails`
WHERE
    `photostand_id` = :photostand_id
    and
    $notification_type_sql is true
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');
        $ret = $statement->bindParam(
            ':photostand_id',
            $photostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $rows;
    }

    /**
     * Get email addresses from photostand id
     *
     * @param PDO $pdo
     * @param int $photostandId
     * @param int $type
     */
    public static function getEmailAddressesFromPhotostandId(
        PDO $pdo,
        int $photostandId,
        int $notificationType
    ) : array {
        $notification_type_sql  = NotificationType::STR_DB[$notificationType];
        $sql = <<<EOT
SELECT
    `email`
FROM
    `notification_emails`
WHERE
    `photostand_id` = :photostand_id
    and
    $notification_type_sql is true
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');
        $ret = $statement->bindParam(
            ':photostand_id',
            $photostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();

        if ($rows === false) {
            return [];
        }

        return array_column($rows, 0);
    }

    /**
     * Registration new email
     *
     * @param PDO       $pdo
     * @param int       $phototstandId
     * @param string    $email
     * @param bool      $recordNotificationIsEnable
     * @param bool      $selfyNotificationIsEnable
     */
    public static function registrationNewEmail(
        PDO     $pdo,
        int     $photostandId,
        string  $email,
        bool    $recordNotificationIsEnable,
        bool    $selfyNotificationIsEnable
    ) : void {
        $sql = <<<EOT
INSERT
INTO `notification_emails` (
    `email`,
    `record_notification`,
    `selfy_notification`,
    `photostand_id`
)
VALUES
(
    :email,
    :record_notification,
    :selfy_notification,
    :photostand_id
);
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':email',
            $email,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':record_notification',
            $recordNotificationIsEnable,
            PDO::PARAM_BOOL
        ) && $statement->bindParam(
            ':selfy_notification',
            $selfyNotificationIsEnable,
            PDO::PARAM_BOOL
        ) && $statement->bindParam(
            ':photostand_id',
            $photostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        $statement->closeCursor();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
    }
}

class DBCSelfyImage
{
    /**
     * Regisitration new image
     *
     * @param PDO       $pdo                PDO object
     * @param int       $fromPhotostandId   From photostand ID
     * @param array     $toPhotostandIds    To photostand IDs (int array)
     * @param string    $fileName           Image file name
     * @param string    $thumbnailFileName  Thumbnail Image file name
     */
    public static function registrationNewImage(
        PDO     $pdo,
        int     $fromPhotostandId,
        array   $toPhotostandIds,
        string  $fileName,
        string  $thumbnailFileName
    ) : void {
        $ret = $pdo->beginTransaction();
        assert(!($ret === false), 'Failed to begin transaction.');

        $sql = <<<EOT
INSERT
INTO `selfy_images` (
    `file_name`,
    `thumbnail_file_name`,
    `from_photostand_id`
)
VALUES
(
    :file_name,
    :thumbnail_file_name,
    :from_photostand_id
);
EOT;
        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':file_name',
            $fileName,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':thumbnail_file_name',
            $thumbnailFileName,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        $statement->closeCursor();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $selfyImagesTableId = $pdo->lastInsertId();

        $sql = <<<EOT
INSERT
INTO `selfy_images__photostands` (
    `to_photostand_id`,
    `selfy_image_id`
)
VALUES
(
    :to_photostand_id,
    :selfy_image_id
);
EOT;

        foreach ($toPhotostandIds as $toPhotostandId) {
            $statement = $pdo->prepare($sql);
            assert(!($statement === false), 'Failed to prepare sql.');

            $ret = $statement->bindParam(
                ':to_photostand_id',
                $toPhotostandId,
                PDO::PARAM_INT
            ) && $statement->bindParam(
                ':selfy_image_id',
                intval($selfyImagesTableId),
                PDO::PARAM_INT
            );
            assert(
                !($ret === false),
                'Failed to bind param.' .
                '$toPhotostandId is not an integer value.' .
                ' (from $toPhotostandIds array)'
            );

            $ret = $statement->execute();
            $statement->closeCursor();

            assert(
                !($ret === false),
                'Failed to execute statement. Propably SQL syntax error.'
            );
        }

        $ret = $pdo->commit();
        assert(!($ret === false), 'Failed to commit.');
    }

    /**
     * Get latest image
     *
     * @param PDO   $pdo                PDO object
     * @param int   $fromPhotostandId   From photostand ID
     * @param int   $toPhotostandId     To photostand ID
     *
     * @throws RuntimeException
     *
     * @return SelfyImage $selfyImage
     */
    public static function getLatestImage(
        PDO     $pdo,
        int     $fromPhotostandId,
        int     $toPhotostandId
    ) : SelfyImage {
        // TODO: This sql has performance issue.
        $sql = <<<EOT
SELECT
    `selfy_images`.`file_name`,
    `selfy_images`.`thumbnail_file_name`,
    `selfy_images`.`created_at`

FROM
    `selfy_images`

    INNER JOIN `selfy_images__photostands`
        ON `selfy_images`.`id` =
            `selfy_images__photostands`.`selfy_image_id`

WHERE
    `selfy_images`.`from_photostand_id` = :from_photostand_id
    and
    `selfy_images__photostands`.`to_photostand_id` = :to_photostand_id

ORDER BY
    `selfy_images`.`created_at`
    DESC

LIMIT
    1
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':to_photostand_id',
            $toPhotostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $row = $statement->fetch(PDO::FETCH_NUM);
        $statement->closeCursor();

        if ($row === false) {
            throw new RuntimeException('Image not found.');
        }

        return new SelfyImage($row[0], $row[1], $row[2]);
    }

    public static function debugGetSelfyImages(
        PDO     $pdo
    ) : array {
        $sql = <<<EOT
SELECT
    `id`,
    `file_name`,
    `thumbnail_file_name`,
    `from_photostand_id`
FROM
    `selfy_images`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $rows;
    }

    public static function debugGetSelfyImagesPhotostands(
        PDO     $pdo
    ) : array {
        $sql= <<<EOT
SELECT
    `to_photostand_id`,
    `selfy_image_id`
FROM
    `selfy_images__photostands`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $rows;
    }
}

class MotionDetectedInfo
{
    private $_noiseLevel;
    private $_changedPixel;
    private $_areaCenterX;
    private $_areaCenterY;
    private $_areaWidth;
    private $_areaHeight;

    /**
     * Get noise level
     *
     * @return int $noiseLevel
     */
    public function getNoiseLevel() : int
    {
        return $this->_noiseLevel;
    }

    /**
     * Get changed pixel
     *
     * @return int $changedPixel
     */
    public function getChangedPixel() : int
    {
        return $this->_changedPixel;
    }

    /**
     * Get area center x
     *
     * @return int $areaCenterX
     */
    public function getAreaCenterX() : int
    {
        return $this->_areaCenterX;
    }

    /**
     * Get area center y
     *
     * @return int $areaCenterY
     */
    public function getAreaCenterY() : int
    {
        return $this->_areaCenterY;
    }

    /**
     * Get area width
     *
     * @return int $areaWidth
     */
    public function getAreaWidth() : int
    {
        return $this->_areaWidth;
    }

    /**
     * Get area height
     *
     * @return int $areaHeight
     */
    public function getAreaHeight() : int
    {
        return $this->_areaHeight;
    }

    /*
     * Constructor
     *
     * @param int       $areaWidth          Changed area width
     * @param int       $areaHeight         Changed area height
     * @param int       $areaCenterX        Changed area center x
     * @param int       $areaCenterY        Changed area center y
     * @param int       $changedPixel       Changed pixel count
     * @param int       $noiseLevel         Noise level
     */
    public function __construct(
        int     $noiseLevel,
        int     $changedPixel,
        int     $areaCenterX,
        int     $areaCenterY,
        int     $areaWidth,
        int     $areaHeight
    ) {
        $this->_noiseLevel          = $noiseLevel;
        $this->_changedPixel        = $changedPixel;
        $this->_areaCenterX         = $areaCenterX;
        $this->_areaCenterY         = $areaCenterY;
        $this->_areaWidth           = $areaWidth;
        $this->_areaHeight          = $areaHeight;
    }
}

class MotionImage
{
    private $_fromPhotostandId;
    private $_fileName;
    private $_thumbnailFileName;
    private $_mdi;
    private $_createdAt;
    private $_groupId;
    private $_humanClassify;
    private $_aiClassify;
    private $_usedAiId;

    /**
     * Get from photostand id
     *
     * @return int $fromPhotostandId
     */
    public function getFromPhotostandId() : int
    {
        return $this->_fromPhotostandId;
    }

    /**
     * Get file name
     *
     * @return string $fileName
     */
    public function getFileName() : string
    {
        return $this->_fileName;
    }

    /**
     * Get thumbnail file name
     *
     * @return string $thumbnailFileName
     */
    public function getThumbnailFileName() : string
    {
        return $this->_thumbnailFileName;
    }

    /**
     * Get Motion Detected Info
     *
     * @return MotionDetectedInfo $mdi
     */
    public function getMotionDetectedInfo() : MotionDetectedInfo
    {
        return $this->_mdi;
    }

    /**
     * Get created at
     *
     * @return string $createdAt
     */
    public function getCreatedAt() : string
    {
        return $this->_createdAt;
    }

    /**
     * Get group id
     *
     * @return int $groupId
     */
    public function getGroupId() : int
    {
        return $this->_groupId;
    }

    /**
     * Get human classify
     *
     * @return ?int $humanClassify
     */
    public function getHumanClassify() : ?int
    {
        return $this->_humanClassify;
    }

    /**
     * Get used ai id
     *
     * @return ?int $usedAiId
     */
    public function getUsedAiId() : ?int
    {
        return $this->_usedAiId;
    }

    /**
     * Get ai classify
     *
     * @return ?int $aiClassify
     */
    public function getAiClassify() : ?int
    {
        return $this->_aiClassify;
    }

    /**
     * Constructor
     *
     * @param string                $fileName
     * @param string                $thumbnailFileName
     * @param MotionDetectedInfo    $mdi
     * @param string                $createdAt
     * @param int                   $groupId
     * @param ?int                  $humanClassify
     * @param ?int                  $usedAiId
     * @param ?int                  $aiClassify
     */
    public function __construct(
        int                 $fromPhotostandId,
        string              $fileName,
        string              $thumbnailFileName,
        MotionDetectedInfo  $mdi,
        string              $createdAt,
        int                 $groupId,
        ?int                $humanClassify,
        ?int                $usedAiId,
        ?int                $aiClassify
    ) {
        $this->_fromPhotostandId    = $fromPhotostandId;
        $this->_fileName            = $fileName;
        $this->_thumbnailFileName   = $thumbnailFileName;
        $this->_mdi                 = $mdi;
        $this->_createdAt           = $createdAt;
        $this->_groupId             = $groupId;
        $this->_humanClassify       = $humanClassify;
        $this->_usedAiId            = $usedAiId;
        $this->_aiClassify          = $aiClassify;
    }
}

class DBCMotionImageGroup
{
    /*
     * Create new group
     *
     * @param PDO
     *
     * @return created group id
     */
    public static function createNewGroup(
        PDO     $pdo
    ) : int {
        $sql = 'INSERT INTO `motion_image_groups` () VALUES ()';

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->execute();
        $statement->closeCursor();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        return intval($pdo->lastInsertId());
    }
}

class DBCMotionImage
{
    /**
     * Get images by time range
     *
     * @param PDO       $pdo                PDO object
     * @param int       $fromPhotostandId   From photostand ID
     * @param DateTime  $dtBegin            Begin
     * @param DateTime  $dtEnd              End
     *
     * @throws RuntimeException
     * @throws PDOException
     *
     * @return array $motionImages (type: MotionImage[])
     */
    public static function getImagesByTimeRange(
        PDO         $pdo,
        int         $fromPhotostandId,
        DateTime    $dtBegin,
        DateTime    $dtEnd
    ) : array {
        $sql = <<<EOT
SELECT
    `file_name`,
    `thumbnail_file_name`,
    `from_photostand_id`,
    `area_width`,
    `area_height`,
    `area_center_x`,
    `area_center_y`,
    `changed_pixel`,
    `noise_level`,
    `group_id`,
    `created_at`
    `human_classify`,
    `used_ai_id`,
    `ai_classify`

FROM
    `motion_images`

WHERE
    `from_photostand_id` = :from_photostand_id
    and :dt_begin   < `created_at`
    and `created_at` < :dt_end

ORDER BY
    `created_at`
    DESC
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':dt_begin',
            $dtBegin->format('Y-m-d H:i:s'),
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':dt_end',
            $dtEnd->format('Y-m-d H:i:s'),
            PDO::PARAM_STR
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();

        if ($rows === false) {
            throw new RuntimeException('Image not found.');
        }

        $motionImages = array();

        foreach ($rows as $row) {
            $mdi = new MotionDetectedInfo(
                $row[8], // noiseLevel
                $row[7], // changedPixel
                $row[5], // areaCenterX
                $row[6], // areaCenterY
                $row[3], // areaWidth
                $row[4]  // areaHeight
            );

            $motionImages[] = new MotionImage(
                $row[2],  // fromPhotostandId
                $row[0],  // fileName
                $row[1],  // thumbnailFileName
                $mdi,     // mdi
                $row[10], // createdAt
                $row[9],  // groupId
                $row[11], // humanClassify
                $row[12], // usedAiId
                $row[13]  // aiClassify
            );
        }

        return $motionImages;
    }


    /**
     * Get latest image
     *
     * @param PDO   $pdo                PDO object
     * @param int   $fromPhotostandId   From photostand ID
     *
     * @throws RuntimeException
     * @throws PDOException
     */
    public static function getLatestImage(
        PDO $pdo,
        int $fromPhotostandId
    ) : MotionImage {
        $sql = <<<EOT
SELECT
    `file_name`,
    `thumbnail_file_name`,
    `from_photostand_id`,
    `area_width`,
    `area_height`,
    `area_center_x`,
    `area_center_y`,
    `changed_pixel`,
    `noise_level`,
    `group_id`,
    `created_at`,
    `human_classify`,
    `used_ai_id`,
    `ai_classify`

FROM
    `motion_images`

WHERE
    `from_photostand_id` = :from_photostand_id

ORDER BY
    `created_at`
    DESC

LIMIT
    1
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $row = $statement->fetch(PDO::FETCH_NUM);
        $statement->closeCursor();

        if ($row === false) {
            throw new RuntimeException('Image not found.');
        }


        $mdi = new MotionDetectedInfo(
            $row[8], // noiseLevel
            $row[7], // changedPixel
            $row[5], // areaCenterX
            $row[6], // areaCenterY
            $row[3], // areaWidth
            $row[4]  // areaHeight
        );

        return new MotionImage(
            $row[2],  // fromPhotostandId
            $row[0],  // fileName
            $row[1],  // thumbnailFileName
            $mdi,     // mdi
            $row[10], // createdAt
            $row[9],  // groupId
            $row[11], // humanClassify
            $row[12], // usedAiId
            $row[13]  // aiClassify
        );
    }

    /**
     * Regisitration new image
     *
     * @param PDO       $pdo                PDO object
     * @param int       $fromPhotostandId   From photostand ID
     * @param int       $gid                Image group ID
     * @param string    $fileName           Image file name
     * @param string    $thumbnailFileName  Thumbnail Image file name
     * @param MotionDetectedInfo $mdi       Motion detected infomation
     */
    public static function registrationNewImage(
        PDO                 $pdo,
        int                 $fromPhotostandId,
        int                 $gid,
        string              $fileName,
        string              $thumbnailFileName,
        MotionDetectedInfo  $mdi
    ) {
        $sql = <<<EOT
INSERT
INTO `motion_images` (
    `file_name`,
    `thumbnail_file_name`,
    `from_photostand_id`,
    `area_width`,
    `area_height`,
    `area_center_x`,
    `area_center_y`,
    `changed_pixel`,
    `noise_level`,
    `group_id`
)
VALUES
(
    :file_name,
    :thumbnail_file_name,
    :from_photostand_id,
    :area_width,
    :area_height,
    :area_center_x,
    :area_center_y,
    :changed_pixel,
    :noise_level,
    :group_id
)
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $mdiAreaWidth       = $mdi->getAreaWidth();
        $mdiAreaHeight      = $mdi->getAreaHeight();
        $mdiAreaCenterX     = $mdi->getAreaCenterX();
        $mdiAreaCenterY     = $mdi->getAreaCenterY();
        $mdiChangedPixel    = $mdi->getChangedPixel();
        $mdiNoiseLevel      = $mdi->getNoiseLevel();

        $ret = $statement->bindParam(
            ':file_name',
            $fileName,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':thumbnail_file_name',
            $thumbnailFileName,
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':area_width',
            $mdiAreaWidth,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':area_height',
            $mdiAreaHeight,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':area_center_x',
            $mdiAreaCenterX,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':area_center_y',
            $mdiAreaCenterY,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':changed_pixel',
            $mdiChangedPixel,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':noise_level',
            $mdiNoiseLevel,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':group_id',
            $gid,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
    }
}

class SensorValue
{
    private $_cdsLux;
    private $_temperatureCelsius;
    private $_infraredCentimetear;
    private $_ultrasonicCentimetear;
    private $_pyroelectric;
    private $_eventType;

    /**
     * Get cds lux
     *
     * @return float $cdsLux
     */
    public function getCdsLux() : float
    {
        return $this->_cdsLux;
    }

    /**
     * Get temperature celsius
     *
     * @return float $temperatureCelsius
     */
    public function getTemperatureCelsius() : float
    {
        return $this->_temperatureCelsius;
    }

    /**
     * Get infrared centimetear
     *
     * @return float $infraredCentimetear
     */
    public function getInfraredCentimetear() : float
    {
        return $this->_infraredCentimetear;
    }

    /**
     * Get ultrasonic centimetear
     *
     * @return float $ultrasonicCentimetear
     */
    public function getUltrasonicCentimetear() : float
    {
        return $this->_ultrasonicCentimetear;
    }

    /**
     * Get pyroelectric value
     *
     * @return float $pyroelectric
     */
    public function getPyroelectric() : float
    {
        return $this->_pyroelectric;
    }

    /**
     * Get event type
     *   NOTE: 0 interval, 1 sensor
     *
     * @return int $eventType
     */
    public function getEventType() : int
    {
        return $this->_eventType;
    }

    /**
     * Constructor
     *
     * @param float $cdsLux
     * @param float $temperatureCelsius
     * @param float $infraredCentimetear
     * @param float $ultrasonicCentimetear
     * @param float $pyroelectric
     * @param int   $eventType
     */
    public function __construct(
        float   $cdsLux,
        float   $temperatureCelsius,
        float   $infraredCentimetear,
        float   $ultrasonicCentimetear,
        float   $pyroelectric,
        int     $eventType
    ) {
        $this->_cdsLux                  = $cdsLux;
        $this->_temperatureCelsius      = $temperatureCelsius;
        $this->_infraredCentimetear     = $infraredCentimetear;
        $this->_ultrasonicCentimetear   = $ultrasonicCentimetear;
        $this->_pyroelectric            = $pyroelectric;
        $this->_eventType               = $eventType;
    }
}

class SensorData
{
    private $_sensorValues;
    private $_fromPhotostandId;
    private $_createdAt;

    /**
     * Get sensor values
     *
     * @return SensorValue $sensorValues
     */
    public function getSensorValue() : SensorValue
    {
        return $this->_sensorValue;
    }

    /**
     * Get from photostand id
     *
     * @return int $fromPhotostandId
     */
    public function getFromPhotostandId() : int
    {
        return $this->_fromPhotostandId;
    }

    /**
     * Get created at
     *
     * @return string $createdAt
     */
    public function getCreatedAt() : string
    {
        return $this->_createdAt;
    }

    /**
     * Constructor
     *
     * @param SensorValue   $sensorValues
     * @param string        $createdAt
     * @param int           $fromPhotostandId
     */
    public function __construct(
        SensorValue     $sensorValues,
        int             $fromPhotostandId,
        string          $createdAt
    ) {
        $this->_sensorValues        = $sensorValues;
        $this->_fromPhotostandId    = $fromPhotostandId;
        $this->_createdAt           = $createdAt;
    }
}

class DBCSensorData
{
    /**
     * Registration New Data
     *
     * @param PDO           $pdo                PDO object
     * @param SensorData    $sd                 SensorData object
     * @param int           $fromPhotostandId   From photostand ID
     *
     * @throws PDOException
     */
    public static function registrationNewData(
        PDO         $pdo,
        SensorValue $sensorValue,
        int         $fromPhotostandId
    ) : void {
        $sql = <<<EOT
INSERT
INTO `sensor_datas` (
    `cds_lux`,
    `temperature_celsius`,
    `infrared_centimetear`,
    `ultrasonic_centimetear`,
    `pyroelectric`,
    `event_type`,
    `from_photostand_id`
)
VALUES
(
    :cds_lux,
    :temperature_celsius,
    :infrared_centimetear,
    :ultrasonic_centimtear,
    :pyroelectric,
    :event_type,
    :from_photostand_id
);
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        // HOTFIX: PHP Notice: Only variables should be passed by reference
        $sensorValueEventType = $sensorValue->getEventType();

        $ret = $statement->bindParam(
            ':cds_lux',
            strval($sensorValue->getCdsLux()),
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':temperature_celsius',
            strval($sensorValue->getTemperatureCelsius()),
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':infrared_centimetear',
            strval($sensorValue->getInfraredCentimetear()),
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':ultrasonic_centimtear',
            strval($sensorValue->getUltrasonicCentimetear()),
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':pyroelectric',
            strval($sensorValue->getPyroelectric()),
            PDO::PARAM_STR
        ) && $statement->bindParam(
            ':event_type',
            $sensorValueEventType,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );
    }

    /**
     * Get sensor data object by range
     *
     * @param PDO       $pdo                PDO object
     * @param int       $fromPhotostandId   From photostand ID
     * @param int       $beginBackHour      Begin time (current_timestamp - $beginBackHour)
     * @param int       $endBackHour        End time (current_timestamp - $endBackHour)
     *
     * @throws RuntimeException
     * @throws PDOException
     */
    public static function getSensorDataArrayByRange(
        PDO     $pdo,
        int     $fromPhotostandId,
        int     $beginBackHour,
        int     $endBackHour
    ) : array {
        $sql = <<<EOT
SELECT
    `id`,
    `cds_lux`,
    `temperature_celsius`,
    `infrared_centimetear`,
    `ultrasonic_centimetear`,
    `pyroelectric`,
    `event_type`,
    `from_photostand_id`,
    `created_at`
FROM
    `sensor_datas`
WHERE
    `from_photostand_id` = :from_photostand_id
    AND
    `created_at` > ( CURRENT_TIMESTAMP - INTERVAL :begin_back_hour HOUR )
    AND
    `created_at` < ( CURRENT_TIMESTAMP - INTERVAL :end_back_hour HOUR )
ORDER BY
    `created_at` ASC
EOT;

        $statement = $pdo->prepare($sql);
        assert(!($statement === false), 'Failed to prepare sql.');

        $ret = $statement->bindParam(
            ':from_photostand_id',
            $fromPhotostandId,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':begin_back_hour',
            $beginBackHour,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':end_back_hour',
            $endBackHour,
            PDO::PARAM_INT
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type of argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $statement->closeCursor();

        return $rows;
    }
}

