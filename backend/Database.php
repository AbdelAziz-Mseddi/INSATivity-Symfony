<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Database {
    private static $dotenv;

    public static function connect() {
        self::loadEnvironment();

        if (!function_exists('pg_connect')) {
            throw new Exception('PostgreSQL extension is not enabled in PHP. Install/enable pgsql and pdo_pgsql.');
        }

        $host = self::getRequiredEnv('SUPABASE_DB_HOST');
        $port = getenv('SUPABASE_DB_PORT') ?: '5432';
        $dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
        $user = self::getRequiredEnv('SUPABASE_DB_USER');
        $password = self::getRequiredEnv('SUPABASE_DB_PASSWORD');
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

        $connection = @pg_connect($connectionString);
        if (!$connection) {
            throw new Exception('Failed to connect to Supabase Postgres. Check DB credentials and SSL settings.');
        }

        return $connection;
    }

    private static function getRequiredEnv($key) {
        $value = getenv($key);
        if ($value === false || trim($value) === '') {
            throw new Exception("Missing required environment variable: {$key}");
        }
        return $value;
    }

    private static function escapeConnectionValue($value) {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }

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
