<?php
// ============================================================
// GestPFE — API Soumission de Proposition (proposition.php)
// POST: Soumettre une nouvelle proposition
// GET:  Récupérer la proposition de l'étudiant connecté
// ============================================================

require_once 'config.php';
setCORSHeaders();
header('Content-Type: application/json');

$session = requireAuth();
$etudiantId = $session['etudiant_id'];
$pdo = getDB();

// ============================================================
// GET — Récupérer la proposition
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               e.prenom AS tuteur_prenom, e.nom AS tuteur_nom, e.email AS tuteur_email,
               e.departement AS tuteur_dept, e.telephone AS tuteur_tel
        FROM projets p
        LEFT JOIN enseignants e ON e.id = p.tuteur_id
        WHERE p.etudiant_id = ?
        ORDER BY p.date_soumission DESC
        LIMIT 1
    ");
    $stmt->execute([$etudiantId]);
    $projet = $stmt->fetch();

    jsonResponse(true, '', ['projet' => $projet]);
}

// ============================================================
// POST — Soumettre une proposition
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si une proposition active existe déjà
    $stmt = $pdo->prepare(
        "SELECT id FROM projets WHERE etudiant_id = ? AND statut NOT IN ('refuse') LIMIT 1"
    );
    $stmt->execute([$etudiantId]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Vous avez déjà une proposition active. Attendez sa résolution pour en soumettre une nouvelle.');
    }

    // Récupérer les données (multipart ou JSON)
    $titre      = trim($_POST['titre'] ?? '');
    $entreprise = trim($_POST['entreprise'] ?? '');
    $theme      = trim($_POST['theme'] ?? '');
    $objectifs  = trim($_POST['objectifs'] ?? '');
    $technologies = trim($_POST['technologies'] ?? '');

    if (!$titre || !$theme || !$objectifs) {
        jsonResponse(false, 'Les champs titre, thème et objectifs sont obligatoires.');
    }

    $themesValides = ['ia','web','mobile','reseau','data','autre'];
    if (!in_array($theme, $themesValides)) {
        jsonResponse(false, 'Thème invalide.');
    }

    // Gestion du fichier joint (optionnel)
    $fichierProposition = null;
    if (!empty($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            jsonResponse(false, 'Format de fichier non autorisé. Utilisez PDF, DOC ou DOCX.');
        }
        if ($_FILES['fichier']['size'] > MAX_FILE_SIZE) {
            jsonResponse(false, 'Fichier trop volumineux. Maximum 10 MB.');
        }

        // Créer le dossier uploads si nécessaire
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $nomFichier = 'prop_' . $etudiantId . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_DIR . $nomFichier)) {
            $fichierProposition = $nomFichier;
        }
    }

    // Insérer la proposition
    $stmt = $pdo->prepare("
        INSERT INTO projets (titre, entreprise, theme, objectifs, technologies, fichier_proposition, etudiant_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$titre, $entreprise, $theme, $objectifs, $technologies, $fichierProposition, $etudiantId]);
    $projetId = $pdo->lastInsertId();

    // Notification
    $pdo->prepare("INSERT INTO notifications (etudiant_id, titre, message, type) VALUES (?, ?, ?, ?)")
        ->execute([
            $etudiantId,
            'Proposition soumise',
            "Votre proposition \"$titre\" a été soumise avec succès. Elle sera examinée par le coordinateur.",
            'success'
        ]);

    jsonResponse(true, 'Proposition soumise avec succès.', ['projet_id' => $projetId]);
}

jsonResponse(false, 'Méthode non autorisée.');
