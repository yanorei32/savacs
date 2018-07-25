<?php

declare(
    strict_types = 1
);

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
     * Get JPEG image by params
     *
     * @param   array   $parameters
     * @param   string  $parameterName
     *
     * @return  mixed   $parameter
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
            throw new UnexpectedValueException(
                'Apache upload failed.'
            );
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
            throw LogicException(
                'Oops. PASSWORD_REGEX is not valid regex pattern'
            );
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
            throw LogicException(
                'Oops. CPUSERIALNUMBER_REGEX is not valid regex pattern'
            );
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
     *
     * @throws PDOException
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
        // prepare statement
        $SQL = <<<EOT
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

        $statement = $pdo->prepare($SQL);

        if ($statement === false) {
            throw LogicException(
                'Oops. prepare() returned false.\n' .
                'If it was normal it is throw exception.'
            );
        }

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

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to bind photostand id.\n' .
                '$idB or $idA already argumemt type check.'
            );
        }

        // execute
        $ret = $statement->execute();

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to execute authorization sql.\n' .
                'I think SQL value has an error.' .
                '(in isActiveAssociationByPhotostandIds)'
            );
        }

        // fetch row
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
        // prepare statement
        $SQL = <<<EOT
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

        $statement = $pdo->prepare($SQL);

        if ($statement === false) {
            throw LogicException(
                'Oops. prepare() returned false.\n' .
                'If it was normal it is throw exception.'
            );
        }

        $ret = $statement->bindParam(
            ':photostand_a',
            $idA,
            PDO::PARAM_INT
        ) && $statement->bindParam(
            ':photostand_b',
            $idB,
            PDO::PARAM_INT
        );

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to bind photostand id.\n' .
                '$idA or $idB already argumemt type check.'
            );
        }

        // execute
        $ret = $statement->execute();

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to execute insert sql.\n' .
                'I think SQL value has an error.' .
                '(in createAssociationByPhotostandIds)'
            );
        }

        $statement->closeCursor();

        return;
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
        // prepare statement
        $SQL = <<<EOT
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

        $statement = $pdo->prepare($SQL);

        if ($statement === false) {
            throw LogicException(
                'Oops. prepare() returned false.\n' .
                'If it was normal it is throw exception.'
            );
        }

        $ret = $statement->bindParam(
            ':cpu_serial_number',
            $cpuSerialNumber,
            PDO::PARAM_STR
        );

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to bind $cpuSerialNumber.\n' .
                '$cpuSerial already argumemt type check.'
            );
        }

        // execute
        $ret = $statement->execute();

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to execute authorization sql.\n' .
                'I think SQL value has an error.' .
                '(in getIdByCpuSerialNumberAndPasswordHash)'
            );
        }

        // fetch row
        $row = $statement->fetch(PDO::FETCH_NUM);

        $statement->closeCursor();

        if ($row === false) {
            throw RangeException(
                '$cpuSerialNumber not found.'
            );
        }

        return $row;
    }

    /**
     * Get id by cpu_serial_number and password
     *
     *
     * @param PDO       $pdo                PDO object
     * @param string    $cpuSerialNumber    Raspberry Pi CPU Serial Number
     * @param string    $password           Password
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
        $row = self::getRowByCpuSerialNumber(
            $pdo,
            $cpuSerialNumber
        );

        if (!password_verify($password, $row[1])) {
            throw new RuntimeException(
                '$password is not match.'
            );
        }

        return $row[0];
    }

    /**
     * Registration photostand
     *
     * @param PDO       $pdo                PDO object
     * @param string    $cpuSerialNumber    Raspberry Pi CPU Serial Number
     * @param string    $password           Password
     *
     * @throws PDOException
     */
    public static function registrationByCpuSerialNumberAndPassword(
        PDO     $pdo,
        string  $cpuSerialNumber,
        string  $password
    ) {
        // prepare statement
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

        $statement = $pdo->prepare(
            $sql
        );

        if ($statement === false) {
            throw LogicException(
                'Oops. prepare() returned false.\n' .
                'If it was normal it is throw exception.'
            );
        }

        $ret = $statement->bindParam(
            ':cpu_serial_number',
            $cpuSerialNumber,
            PDO::PARAM_STR
        );

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to bind $cpuSerialNumber.\n' .
                '$cpuSerialNumber already argumemt type check.'
            );
        }

        $ret = $statement->bindParam(
            ':password_hash',
            password_hash(
                $password,
                PASSWORD_DEFAULT
            ),
            PDO::PARAM_STR
        );

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to bind password_hash.\n' .
                'I think $sql value has an error' .
                '(in registrationByCpuSerialNumberAndPassword)'
            );
        }

        // execute
        $ret = $statement->execute();

        if ($ret === false) {
            throw LogicException(
                'Oops. failed to execute insert.\n' .
                'I think $sql value has an error.' .
                '(in registrationByCpuSerialNumberAndPassword)'
            );
        }
    }

    /**
     * Get all value for debug
     *
     * @param PDO $pdo PDO object
     *
     * @return array cols
     *
     * @throws RuntimeException
     * @throws RangeException
     */
    public static function getAllValue(
        PDO     $pdo
    ) : array {
        // prepare statement
        $SQL = <<<EOT
SELECT
    `id`,
    `password_hash`,
    `cpu_serial_number`
FROM
    `photostands`
EOT;

        $statement = $pdo->prepare($SQL);
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
     *
     * @throws RuntimeException
     * @throws RangeException
     */
    public static function getAllAssociationValue(
        PDO     $pdo
    ) : array {
        // prepare statement
        $SQL = <<<EOT
SELECT
    `photostand_a`,
    `photostand_b`
FROM
    `photostands__photostands`
EOT;

        $statement = $pdo->prepare($SQL);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $rows;
    }
}

