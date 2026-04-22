<?php
// ============================================================
// GestPFE — Déconnexion (logout.php)
// ============================================================

require_once 'config.php';
setCORSHeaders();
header('Content-Type: application/json');

session_start();
session_unset();
session_destroy();

jsonResponse(true, 'Déconnexion réussie.');
