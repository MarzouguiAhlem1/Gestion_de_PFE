<?php
// ============================================================
// GestPFE — API Tableau de Bord (dashboard.php)
// GET: Toutes les données du tableau de bord étudiant
// ============================================================

require_once 'config.php';
setCORSHeaders();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Méthode non autorisée.');
}

$session = requireAuth();
$etudiantId = $session['etudiant_id'];
$pdo = getDB();

// Infos étudiant
$stmt = $pdo->prepare("SELECT id, prenom, nom, email, cin, filiere, date_inscription FROM etudiants WHERE id = ?");
$stmt->execute([$etudiantId]);
$etudiant = $stmt->fetch();

// Projet + tuteur
$stmt = $pdo->prepare("
    SELECT p.*,
           e.prenom AS tuteur_prenom, e.nom AS tuteur_nom, e.email AS tuteur_email,
           e.departement, e.telephone, e.bureau, e.disponibilites
    FROM projets p
    LEFT JOIN enseignants e ON e.id = p.tuteur_id
    WHERE p.etudiant_id = ?
    ORDER BY p.date_soumission DESC LIMIT 1
");
$stmt->execute([$etudiantId]);
$projet = $stmt->fetch();

// Comptes rendus
$comptes_rendus = [];
if ($projet) {
    $stmt = $pdo->prepare("SELECT * FROM comptes_rendus WHERE projet_id = ? ORDER BY numero ASC");
    $stmt->execute([$projet['id']]);
    $comptes_rendus = $stmt->fetchAll();
}

// Évaluation
$evaluation = null;
if ($projet) {
    $stmt = $pdo->prepare("SELECT * FROM evaluations WHERE projet_id = ?");
    $stmt->execute([$projet['id']]);
    $evaluation = $stmt->fetch();
}

// Notifications non lues
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE etudiant_id = ? 
    ORDER BY date_creation DESC 
    LIMIT 10
");
$stmt->execute([$etudiantId]);
$notifications = $stmt->fetchAll();

// Statistiques
$stats = [
    'total_cr'     => count($comptes_rendus),
    'cr_valides'   => count(array_filter($comptes_rendus, fn($c) => $c['statut'] === 'valide')),
    'cr_en_attente'=> count(array_filter($comptes_rendus, fn($c) => $c['statut'] === 'soumis')),
    'non_lues'     => count(array_filter($notifications, fn($n) => !$n['est_lu'])),
];

// Calcul avancement (%)
$avancement = 0;
if ($projet) {
    $steps = [
        'soumis'          => 15,
        'en_revision'     => 25,
        'valide'          => 35,
        'en_cours'        => 50,
        'rapport_depose'  => 75,
        'soutenu'         => 90,
        'archive'         => 100,
    ];
    $baseProgress = $steps[$projet['statut']] ?? 0;
    $crProgress = $stats['total_cr'] > 0 ? ($stats['cr_valides'] / max($stats['total_cr'], 5)) * 15 : 0;
    $avancement = min(100, intval($baseProgress + $crProgress));
}
$stats['avancement'] = $avancement;

jsonResponse(true, '', [
    'etudiant'       => $etudiant,
    'projet'         => $projet,
    'comptes_rendus' => $comptes_rendus,
    'evaluation'     => $evaluation,
    'notifications'  => $notifications,
    'stats'          => $stats,
]);
