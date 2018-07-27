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
        $webServerPath = getenv('SAVACS_ALIAS') . self::$_contentsDirName .
            $this->_directoryName;

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


class DBCSelfyImage
{
    /**
     * Regisitration new image
     *
     * @param PDO       $pdo                PDO object
     * @param int       $fromPhotostandId   From photostand ID
     * @param array     $toPhotostandIds    To photostand IDs (int array) (association checked)
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

    public static function debugGetSelfyImages(
        PDO     $pdo
    ) : array {
        $sql= <<<EOT
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

