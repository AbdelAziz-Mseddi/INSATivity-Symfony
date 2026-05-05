<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

class Database {
    private static $dotenv;
    private static $connection = null;

    public static function connect() {
        if (self::$connection !== null) {
            return self::$connection;
        }

        self::loadEnvironment();

        if (!class_exists('PDO') || !in_array('pgsql', PDO::getAvailableDrivers(), true)) {
            throw new Exception('PDO PostgreSQL driver is not enabled in PHP. Install/enable pdo_pgsql.');
        }

        $host = self::getRequiredEnv('DATABASE_HOST');
        $port = self::getOptionalEnv('DATABASE_PORT', '5432');
        $dbname = self::getOptionalEnv('DATABASE_NAME', 'postgres');
        $user = self::getRequiredEnv('DATABASE_USER');
        $password = self::getRequiredEnv('DATABASE_PASSWORD');
        $sslmode = self::getOptionalEnv('SUPABASE_DB_SSLMODE', 'require');

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s;application_name=INSATivity',
            $host,
            $port,
            $dbname,
            $sslmode
        );

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new Exception('Failed to connect to Supabase Postgres: ' . $e->getMessage());
        }

        return self::$connection;
    }

    private static function getRequiredEnv($key) {
        $value = getenv($key);
        if ($value === false || trim((string)$value) === '') {
            throw new Exception("Missing required environment variable: {$key}");
        }
        return $value;
    }

    private static function getOptionalEnv($key, $default = '') {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    public static function loadEnvironment() {
        if (self::$dotenv !== null) {
            return;
        }

        try {
            $projectRoot = __DIR__ . '/../..';
            $envFile = $projectRoot . '/.env';
            
            if (!file_exists($envFile)) {
                throw new Exception("Environment file not found at: {$envFile}");
            }

            self::$dotenv = Dotenv::createImmutable($projectRoot);
            self::$dotenv->load();
            
            foreach ($_ENV as $key => $value) {
                putenv("{$key}={$value}");
            }
        } catch (\Exception $e) {
            throw new Exception('Failed to load environment variables: ' . $e->getMessage());
        }
    }
}