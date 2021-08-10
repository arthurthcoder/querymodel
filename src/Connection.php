<?php
namespace BaseCode\QueryModel;

use PDO;
use PDOException;
use Exception;

/**
 * Class Connection
 * @package BaseCode\QueryModel
 */
Class Connection
{

    /** @var PDO */
    private static $conn;

    /** @var PDOException|Exception */
    private static $error;

    /**
     * @param string $dbConfig
     * @return PDO|null
     */
    public static function get(string $dbConfig = "DB_CONFIG"): ?PDO
    {
        try{

            if (self::$conn) {
                return self::$conn;
            }
            
            if (!defined($dbConfig)) {
                throw new Exception("The database configuration constant has not been defined!");
            }

            $dbConfig = constant($dbConfig);

            if (!isset($dbConfig["options"][PDO::ATTR_ERRMODE])) {
                $dbConfig["options"][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            }

            self::$conn = new PDO(
                $dbConfig["driver"].":host=".$dbConfig["host"].";dbname=".$dbConfig["name"],
                $dbConfig["user"],
                $dbConfig["password"],
                $dbConfig["options"]
            );

            return self::$conn;

        } catch (PDOException | Exception $e) {
            self::$error = $e;
            return null;
        }
    }

    public static function error()
    {
        return self::$error;
    }
}
?>