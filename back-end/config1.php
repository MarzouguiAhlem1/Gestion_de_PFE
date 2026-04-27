<?php
// ============================================================
// GestPFE — Configuration Base de Données
// Fichier : backend/config.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'gestpfe');
define('DB_USER', 'root');        // Modifier selon votre config XAMPP
define('DB_PASS', '');            // Modifier selon votre config XAMPP
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR', '../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx']);

define('SESSION_DURATION', 3600 * 24); // 24h

// Connexion PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données.']);
            exit;
        }
    }
    return $pdo;
}

// Helper: JSON response
function jsonResponse(bool $success, string $message = '', array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Helper: Vérifier session
function requireAuth(): array {
    session_start();
    if (empty($_SESSION['etudiant_id'])) {
        http_response_code(401);
        jsonResponse(false, 'Non authentifié. Veuillez vous connecter.');
    }
    return $_SESSION;
}

// Helper: CORS headers (pour développement)
function setCORSHeaders(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
}
