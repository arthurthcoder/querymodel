<?php
    define("DB_CONFIG", [
        "driver" => "mysql",
        "host" => "localhost",
        "name" => "querymodel",
        "user" => "root",
        "password" => "",
        "options" => [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]
    ]);
?>