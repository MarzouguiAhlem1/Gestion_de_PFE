<?php
// ============================================
//  save_etudiant.php  —  Enregistrement étudiant
// ============================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Autoriser uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

require_once 'config.php';

// ── 1. Récupération et nettoyage des données ──────────────────────────────────
$prenom   = trim($_POST['prenom']   ?? '');
$nom      = trim($_POST['nom']      ?? '');
$email    = trim($_POST['email']    ?? '');
$matricule= trim($_POST['matricule']?? '');
$niveau   = trim($_POST['niveau']   ?? '');
$filiere  = trim($_POST['filiere']  ?? '');
$mdp      = $_POST['mdp']           ?? '';
$mdp2     = $_POST['mdp2']          ?? '';

// ── 2. Validation serveur ─────────────────────────────────────────────────────
$errors = [];

if (empty($prenom))                          $errors['prenom']    = 'Le prénom est requis.';
if (empty($nom))                             $errors['nom']       = 'Le nom est requis.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Adresse e-mail invalide.';
if (empty($matricule))                       $errors['matricule'] = 'Le matricule est requis.';
if (empty($niveau))                          $errors['niveau']    = 'Le niveau est requis.';
if (empty($filiere))                         $errors['filiere']   = 'La filière est requise.';
if (strlen($mdp) < 8)                        $errors['mdp']       = 'Mot de passe : 8 caractères minimum.';
if ($mdp !== $mdp2)                          $errors['mdp2']      = 'Les mots de passe ne correspondent pas.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── 3. Vérification unicité email / matricule ─────────────────────────────────
$pdo = getDB();

$stmt = $pdo->prepare('SELECT id FROM etudiants WHERE email = ? OR matricule = ? LIMIT 1');
$stmt->execute([$email, $matricule]);
$existing = $stmt->fetch();

if ($existing) {
    // Distinguer quel champ est en doublon
    $stmtE = $pdo->prepare('SELECT id FROM etudiants WHERE email = ? LIMIT 1');
    $stmtE->execute([$email]);
    $stmtM = $pdo->prepare('SELECT id FROM etudiants WHERE matricule = ? LIMIT 1');
    $stmtM->execute([$matricule]);

    $dup = [];
    if ($stmtE->fetch()) $dup['email']     = 'Cette adresse e-mail est déjà utilisée.';
    if ($stmtM->fetch()) $dup['matricule'] = 'Ce matricule est déjà enregistré.';

    http_response_code(409);
    echo json_encode(['success' => false, 'errors' => $dup]);
    exit;
}

// ── 4. Hashage du mot de passe ────────────────────────────────────────────────
$hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 5. Insertion en base ──────────────────────────────────────────────────────
try {
    $insert = $pdo->prepare('
        INSERT INTO etudiants (prenom, nom, email, matricule, niveau, filiere, mot_de_passe)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $insert->execute([$prenom, $nom, $email, $matricule, $niveau, $filiere, $hash]);
    $newId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Compte étudiant créé avec succès.',
        'id'      => (int) $newId,
        'etudiant'=> [
            'prenom'    => $prenom,
            'nom'       => $nom,
            'email'     => $email,
            'matricule' => $matricule,
            'niveau'    => $niveau,
            'filiere'   => $filiere,
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement.']);
}
