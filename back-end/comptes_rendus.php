<?php
// ============================================================
// GestPFE — API Comptes Rendus (comptes_rendus.php)
// GET:  Liste des CR de l'étudiant
// POST: Soumettre un nouveau CR
// ============================================================

require_once 'config.php';
setCORSHeaders();
header('Content-Type: application/json');

$session = requireAuth();
$etudiantId = $session['etudiant_id'];
$pdo = getDB();

// Récupérer le projet actif de l'étudiant
$stmt = $pdo->prepare(
    "SELECT id FROM projets WHERE etudiant_id = ? AND statut IN ('valide','en_cours') ORDER BY id DESC LIMIT 1"
);
$stmt->execute([$etudiantId]);
$projet = $stmt->fetch();

if (!$projet) {
    jsonResponse(false, 'Aucun projet actif trouvé. Votre projet doit être validé pour soumettre des comptes rendus.');
}
$projetId = $projet['id'];

// ============================================================
// GET — Liste des comptes rendus
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT * FROM comptes_rendus
        WHERE projet_id = ?
        ORDER BY numero ASC
    ");
    $stmt->execute([$projetId]);
    $crs = $stmt->fetchAll();

    jsonResponse(true, '', ['comptes_rendus' => $crs, 'projet_id' => $projetId]);
}

// ============================================================
// POST — Soumettre un compte rendu
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $numero           = intval($data['numero'] ?? 0);
    $travauxRealises  = trim($data['travaux_realises'] ?? '');
    $travauxAVenir    = trim($data['travaux_a_venir'] ?? '');
    $problemes        = trim($data['problemes'] ?? '');

    if (!$numero || !$travauxRealises) {
        jsonResponse(false, 'Le numéro et les travaux réalisés sont obligatoires.');
    }
    if ($numero < 1 || $numero > 10) {
        jsonResponse(false, 'Numéro de compte rendu invalide.');
    }

    // Vérifier si ce CR existe déjà
    $stmt = $pdo->prepare("SELECT id, statut FROM comptes_rendus WHERE projet_id = ? AND numero = ?");
    $stmt->execute([$projetId, $numero]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['statut'] === 'valide') {
            jsonResponse(false, "Le compte rendu #$numero a déjà été validé par votre tuteur.");
        }
        // Mettre à jour
        $stmt = $pdo->prepare("
            UPDATE comptes_rendus 
            SET travaux_realises = ?, travaux_a_venir = ?, problemes = ?, statut = 'soumis', date_soumission = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$travauxRealises, $travauxAVenir, $problemes, $existing['id']]);
        jsonResponse(true, "Compte rendu #$numero mis à jour avec succès.");
    }

    // Créer le CR
    $stmt = $pdo->prepare("
        INSERT INTO comptes_rendus (projet_id, numero, travaux_realises, travaux_a_venir, problemes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$projetId, $numero, $travauxRealises, $travauxAVenir, $problemes]);

    // Notification
    $pdo->prepare("INSERT INTO notifications (etudiant_id, titre, message, type) VALUES (?, ?, ?, ?)")
        ->execute([
            $etudiantId,
            "CR #$numero soumis",
            "Votre compte rendu #$numero a été soumis au tuteur. Vous serez notifié de sa validation.",
            'info'
        ]);

    jsonResponse(true, "Compte rendu #$numero soumis avec succès.");
}

jsonResponse(false, 'Méthode non autorisée.');
