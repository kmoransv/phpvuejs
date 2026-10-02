<?php

class Database
{
    private static $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $dbPath = __DIR__ . '/../../database/database.sqlite';
            self::$instance = new PDO('sqlite:' . $dbPath);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$instance->exec('PRAGMA foreign_keys = ON');
        }
        return self::$instance;
    }
}