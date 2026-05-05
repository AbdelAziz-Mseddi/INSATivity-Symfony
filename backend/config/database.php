<?php
declare(strict_types=1);

function loadEnv(string $path): void
{
    // Vérifie si le fichier .env existe
    if (!file_exists($path)) {
        throw new RuntimeException(".env file not found");
    }

    // Lit toutes les lignes du fichier .env
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignore les lignes vides et les commentaires
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Sépare la ligne en deux parties : clé et valeur
        
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

        $key = trim($key);
        $value = trim($value);

        // Supprime les guillemets éventuels autour de la valeur
        $value = trim($value, "\"'");

        // Stocke la variable dans $_ENV et dans l'environnement PHP
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

//connexion à la base de données
function getPDO(): PDO
{
    // Charge le fichier .env situé à la racine du projet
    loadEnv(dirname(__DIR__, 2) . '/.env');

    // Si DATABASE_URL existe, on l'utilise directement
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: null;

    if ($databaseUrl) {
       
        $parts = parse_url($databaseUrl);

        if ($parts === false) {
            throw new RuntimeException("Invalid DATABASE_URL");
        }

        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? 5432;
        $user = isset($parts['user']) ? urldecode($parts['user']) : '';
        $password = isset($parts['pass']) ? urldecode($parts['pass']) : '';
        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : 'postgres';

    } else {
        //Si DATABASE_URL n'existe pas, on utilise les variables séparées.
       
        $host = $_ENV['DATABASE_HOST'] ?? getenv('DATABASE_HOST');
        $port = $_ENV['DATABASE_PORT'] ?? getenv('DATABASE_PORT') ?: 5432;
        $user = $_ENV['DATABASE_USER'] ?? getenv('DATABASE_USER');
        $password = $_ENV['DATABASE_PASSWORD'] ?? getenv('DATABASE_PASSWORD');
        $dbname = $_ENV['DATABASE_NAME'] ?? getenv('DATABASE_NAME') ?: 'postgres';
    }

    // DSN PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=prefer";

    // Création de la connexion PDO
    return new PDO($dsn, $user, $password, [
        // Active les exceptions en cas d'erreur SQL
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Désactive l'émulation des requêtes préparées
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}