<?php
declare(strict_types=1);

// Handle session properly
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = db();
    $stmt = $pdo->query('SELECT id, nom_motif, description, duree_estimee FROM appointment_types ORDER BY nom_motif');
    $types = $stmt->fetchAll();
    
    jsonResponse(['ok' => true, 'types' => $types]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Erreur lors du chargement des motifs'], 500);
}
?>
