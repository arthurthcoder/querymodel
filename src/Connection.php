<?php
namespace BaseCode\QueryModel;

use PDO;
use PDOException;
use Exception;

Class Connection
{

    private static $conn;
    private static $error;

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

            self::$conn = new PDO(
                $dbConfig["driver"].":host=".$dbConfig["host"].";dbname=".$dbConfig["name"],
                $dbConfig["user"],
                $dbConfig["password"],
                (isset($dbConfig["options"]) ? ($dbConfig["options"] ?: null) : null)
            );

            return self::$conn;

        } catch (PDOException | Exception $e) {
            self::$error = $e;
            return null;
        }
    }

    public static function error(): ?object
    {
        return self::$error;
    }
}
?>