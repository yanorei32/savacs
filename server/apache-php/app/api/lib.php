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
        $row = $statement->fetchAll(PDO::FETCH_NUM);
        $statement->closeCursor();
        return $row;
    }
}

