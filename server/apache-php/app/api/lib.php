<?php

declare(
    strict_types = 1
);

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
        $webServerPath = '/' .
            self::$_contentsDirName . $this->_directoryName;

        return $webServerPath;
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
     * Get AAC Audio by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  string  $tempFilePath
     *
     * @throws  OutOfBoundsException
     * @throws  UnexpectedValueException
     */
    public static function getAACAudioByFilesParams(
        array   $parameters,
        string  $parameterName
    ) {
        $file = self::_getAnyValueByParams(
            $parameters,
            $parameterName
        );

        $tempFilePath = $file['tmp_name'];

        if (!is_uploaded_file($tempFilePath)) {
            throw new UnexpectedValueException('Apache upload failed.');
        }

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
    ) {
        $file = self::_getAnyValueByParams(
            $parameters,
            $parameterName
        );

        $tempFilePath = $file['tmp_name'];

        if (!is_uploaded_file($tempFilePath)) {
            throw new UnexpectedValueException('Apache upload failed.');
        }

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
        $ret = preg_match('/^[\w#?!@$%^&*-+=]+$/', $password);

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
}

class BasicTools
{
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
    ) {
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
        'host=mariadb;' .
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
     * Is active association by photostand IDs
     *
     * @param PDO   $pdo    PDO object
     * @param int   $idA    Photostand A ID (range checked)
     * @param int   $idB    Photostand B ID (range checked)
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
            'Failed to bind param. Failed to check type argument?'
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
            'Failed to bind param. Failed to check type argument?'
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
    ) {
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
            'Failed to bind param. Failed to check type argument?'
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
            'Failed to bind param. Failed to check type argument?'
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
     * Registration photostand
     *
     * @param PDO       $pdo                PDO object
     * @param string    $cpuSerialNumber    Validated Raspberry Pi CPU Serial Number
     * @param string    $password           Validated Password
     */
    public static function registrationByCpuSerialNumberAndPassword(
        PDO     $pdo,
        string  $cpuSerialNumber,
        string  $password
    ) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        assert(!($passwordHash === false), 'Failed to calc hash.');

        $sql = <<<EOT
INSERT
INTO `photostands` (
    `cpu_serial_number`,
    `password_hash`
)
VALUES
(
    :cpu_serial_number,
    :password_hash
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
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type argument?'
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
    `password_hash`,
    `cpu_serial_number`
FROM
    `photostands`
EOT;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
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
    ) {
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
            'Failed to bind param. Failed to check type argument?'
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
     * @param PDO   $pdo                PDO object
     * @param int   $toPhotostandId     To photostand ID
     * @param int   $limit              Limit
     *
     * @throws RuntimeException
     *
     * @return array $recordVoices (type RecordVoice)
     */
    public static function getResentryRecordVoices(
        PDO     $pdo,
        int     $toPhotostandId,
        int     $limit
    ) : array {
        // TODO: This sql has performance issue.
        $sql = <<<EOT
SELECT
    `record_voices`.`file_name`,
    `record_voices`.`duration`,
    `record_voices`.`created_at`,
    `record_voices`.`from_photostand_id`

FROM
    `record_voices`

    INNER JOIN `record_voices__photostands`
        ON `record_voices`.`id` =
            `record_voices__photostands`.`record_voices_id`

WHERE
    `record_voices__photostands`.`to_photostand_id` = :to_photostand_id

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
        );
        assert(
            !($ret === false),
            'Failed to bind param. Failed to check type argument?'
        );

        $ret = $statement->execute();
        assert(
            !($ret === false),
            'Failed to execute statement. Propably SQL syntax error.'
        );

        $rows = $statement->fetchAll(PDO::FETCH_NUM);

        $statement->closeCursor();

        if ($rows === false) {
            throw new RuntimeException('Record voice not found.');
        }

        $recordVoices = array();

        foreach ($rows as $row) {
            $recordVoices[] = new RecordVoice(
                $row[0],
                $row[1],
                $row[2],
                $row[3]
            );
        }

        return $recordVoices;
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
    ) {
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
            'Failed to bind param. Failed to check type argument?'
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
            'Failed to bind param. Failed to check type argument?'
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

