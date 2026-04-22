<?php
// ============================================================
// GestPFE — API Connexion (login.php)
// POST: { email, mot_de_passe }
// ============================================================

require_once 'config.php';
setCORSHeaders();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée.');
}

$data = json_decode(file_get_contents('php://input'), true);

$email        = trim($data['email']        ?? '');
$mot_de_passe = $data['mot_de_passe']      ?? '';

// ── Validation ────────────────────────────────────────────
if (empty($email) || empty($mot_de_passe)) {
    jsonResponse(false, 'Email et mot de passe sont requis.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Adresse email invalide.');
}

// ── Recherche étudiant ────────────────────────────────────
$pdo  = getDB();
$stmt = $pdo->prepare(
    "SELECT * FROM etudiants WHERE email = ? AND est_actif = 1 LIMIT 1"
);
$stmt->execute([$email]);
$etudiant = $stmt->fetch();

if (!$etudiant || !password_verify($mot_de_passe, $etudiant['mot_de_passe'])) {
    http_response_code(401);
    jsonResponse(false, 'Email ou mot de passe incorrect.');
}

// ── Session ───────────────────────────────────────────────
session_start();
session_regenerate_id(true);
$_SESSION['etudiant_id']     = $etudiant['id'];
$_SESSION['etudiant_email']  = $etudiant['email'];
$_SESSION['etudiant_prenom'] = $etudiant['prenom'];
$_SESSION['etudiant_nom']    = $etudiant['nom'];

// Retourner les données sans le mot de passe
unset($etudiant['mot_de_passe']);
jsonResponse(true, 'Connexion réussie.', ['etudiant' => $etudiant]);
