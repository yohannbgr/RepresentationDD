<?php
// Charger les variables d'environnement depuis .env
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignorer les commentaires
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Supprimer les guillemets si présents
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
    }
}

// Charger le fichier .env
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);

// Définir les constantes depuis l'environnement ou valeurs par défaut
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'surveillance_maladies');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHAR', $_ENV['DB_CHAR'] ?? 'utf8mb4');

header("Content-Type: text/html; charset=UTF-8");

function getConnexion(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            $pdo->exec("SET CHARACTER SET utf8mb4");
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;color:#c00;padding:2rem;">
                <strong>Erreur de connexion</strong><br>'
                . htmlspecialchars($e->getMessage()) .
            '</div>');
        }
    }
    return $pdo;
}