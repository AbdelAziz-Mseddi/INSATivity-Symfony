<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Database {
    private static $dotenv;

    # za bluetooth devize iz connected suczessfully
    public static function connect() {
        self::loadEnvironment();

        if (!function_exists('pg_connect')) {
            throw new Exception('PostgreSQL extension is not enabled in PHP. Install/enable pgsql and pdo_pgsql.');
        }

        $host = self::getRequiredEnv('DATABASE_HOST');
        $port = getenv('DATABASE_PORT') ?: '5432';
        $dbname = getenv('DATABASE_NAME') ?: 'postgres';
        $user = self::getRequiredEnv('DATABASE_USER');
        $password = self::getRequiredEnv('DATABASE_PASSWORD');
        $sslmode = getenv('SUPABASE_DB_SSLMODE') ?: 'require';

        $connectionString = sprintf(
            "host='%s' port='%s' dbname='%s' user='%s' password='%s' sslmode='%s'",
            self::escapeConnectionValue($host),
            self::escapeConnectionValue($port),
            self::escapeConnectionValue($dbname),
            self::escapeConnectionValue($user),
            self::escapeConnectionValue($password),
            self::escapeConnectionValue($sslmode)
        );

        $connection = pg_connect($connectionString);
        if (!$connection) {
            $error = pg_last_error();
            $errorMsg = $error ? $error : 'Unknown connection error';
            throw new Exception('Failed to connect to Supabase Postgres: ' . $errorMsg);
        }

        return $connection;
    }

    # t chargilek environment variable li hajtek biha
    private static function getRequiredEnv($key) {
        $value = getenv($key);
        if ($value === false || trim($value) === '') {
            throw new Exception("Missing required environment variable: {$key}");
        }
        return $value;
    }

    # str_replace(search, replace, subject)
    # finds every occurrence of search in subject and replaces it with replace
    # treating \ and ' as literal characters in the password, not syntax delimiters
    # every \ found → replaced with \\ & every ' found → replaced with \'
    private static function escapeConnectionValue($value) {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }

    # y chargi l environment variables mel .env w y injectihom fel superglobals
    # (bech n accediwelhom fel $_ENV w getenv())
    private static function loadEnvironment() {
        if (self::$dotenv !== null) {
            return;
        }

        try {
            self::$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
            self::$dotenv->load();
        } catch (\Exception $e) {
            throw new Exception('Failed to load environment variables: ' . $e->getMessage());
        }
    }
}
