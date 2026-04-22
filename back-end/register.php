<?php
// ============================================================
// GestPFE — API Inscription (register.php)
// POST: { prenom, nom, email, cin, filiere, mot_de_passe }
// ============================================================

require_once 'config.php';
setCORSHeaders();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée.');
}

$data = json_decode(file_get_contents('php://input'), true);

$prenom       = trim($data['prenom']       ?? '');
$nom          = trim($data['nom']          ?? '');
$email        = trim($data['email']        ?? '');
$cin          = trim($data['cin']          ?? '');
$filiere      = trim($data['filiere']      ?? '');
$mot_de_passe = $data['mot_de_passe']      ?? '';

// ── Validation champs ──────────────────────────────────────
if (!$prenom || !$nom || !$email || !$cin || !$filiere || !$mot_de_passe) {
    jsonResponse(false, 'Tous les champs obligatoires doivent être remplis.');
}

// ── Validation email FST uniquement ───────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Adresse email invalide.');
}


// ── Validation CIN (8 chiffres) ───────────────────────────
if (!preg_match('/^\d{8}$/', $cin)) {
    jsonResponse(false, 'Le numéro CIN doit contenir exactement 8 chiffres.');
}

// ── Validation mot de passe ───────────────────────────────
if (strlen($mot_de_passe) < 8) {
    jsonResponse(false, 'Le mot de passe doit contenir au moins 8 caractères.');
}

// ── Filières autorisées ───────────────────────────────────
$filieres_autorisees = [
    'sc_informatique'  => 'Licence Sciences Informatique',
    'tic'              => 'Licence TIC (Technologies de l\'Information et de la Communication)',
    'agro_alimentaire' => 'Licence Agro-alimentaire',
    'autre'            => 'Autre',
];
if (!array_key_exists($filiere, $filieres_autorisees)) {
    jsonResponse(false, 'Filière invalide.');
}
$filiere_label = $filieres_autorisees[$filiere];

// ── Vérification doublons ──────────────────────────────────
$pdo = getDB();

$stmt = $pdo->prepare("SELECT id FROM etudiants WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(false, 'Cette adresse email est déjà utilisée.');
}

$stmt = $pdo->prepare("SELECT id FROM etudiants WHERE cin = ?");
$stmt->execute([$cin]);
if ($stmt->fetch()) {
    jsonResponse(false, 'Ce numéro CIN est déjà enregistré.');
}

// ── Insertion ─────────────────────────────────────────────
$hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO etudiants (prenom, nom, email, cin, filiere, mot_de_passe)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$prenom, $nom, $email, $cin, $filiere_label, $hash]);
$newId = $pdo->lastInsertId();

// ── Session ───────────────────────────────────────────────
session_start();
session_regenerate_id(true);
$_SESSION['etudiant_id']     = $newId;
$_SESSION['etudiant_email']  = $email;
$_SESSION['etudiant_prenom'] = $prenom;
$_SESSION['etudiant_nom']    = $nom;

// ── Notification de bienvenue ─────────────────────────────
$pdo->prepare(
    "INSERT INTO notifications (etudiant_id, titre, message, type)
     VALUES (?, ?, ?, ?)"
)->execute([
    $newId,
    'Bienvenue sur GestPFE !',
    "Bienvenue $prenom ! Votre compte a été créé avec succès. Vous pouvez maintenant soumettre votre proposition de PFE.",
    'success'
]);

jsonResponse(true, 'Compte créé avec succès.', [
    'etudiant' => [
        'id'      => $newId,
        'prenom'  => $prenom,
        'nom'     => $nom,
        'email'   => $email,
        'filiere' => $filiere_label,
    ]
]);
