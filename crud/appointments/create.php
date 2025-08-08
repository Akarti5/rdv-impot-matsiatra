<?php
declare(strict_types=1);

require_once __DIR__ . '/../time_slots/read.php'; // to reuse availability logic if needed (we already compute in SQL below)
require_once __DIR__ . '/../../includes/email_service.php';

/**
 * Create appointment ensuring slot availability.
 */
function appointments_create(array $payload): array {
  $user_id = (int)($payload['user_id'] ?? 0);
  $agent_id = (int)($payload['agent_id'] ?? 0);
  $date = (string)($payload['date_rdv'] ?? '');
  $heure = (string)($payload['heure_rdv'] ?? '');
  $motif = trim((string)($payload['motif'] ?? ''));
  $notes = trim((string)($payload['notes_client'] ?? ''));

  if (!$user_id || !$agent_id || !$date || !$heure || !$motif) {
    return ['ok' => false, 'message' => 'Champs requis manquants'];
  }

  $pdo = db();
  try {
    // Verify the time falls into an available slot and capacity not exceeded
    $stmt = $pdo->prepare('SELECT id, max_appointments FROM time_slots WHERE agent_id = ? AND date = ? AND heure_debut <= ? AND heure_fin > ? AND is_available = 1');
    $stmt->execute([$agent_id, $date, $heure, $heure]);
    $slot = $stmt->fetch();
    if (!$slot) return ['ok' => false, 'message' => 'Créneau indisponible'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE agent_id = ? AND date_rdv = ? AND heure_rdv >= (SELECT heure_debut FROM time_slots WHERE id = ?) AND heure_rdv < (SELECT heure_fin FROM time_slots WHERE id = ?) AND status <> "cancelled"');
    $stmt->execute([$agent_id, $date, (int)$slot['id'], (int)$slot['id']]);
    $count = (int)$stmt->fetchColumn();
    if ($count >= (int)$slot['max_appointments']) return ['ok' => false, 'message' => 'Capacité atteinte pour ce créneau'];

    // Prevent duplicate appointment for same user+time
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE user_id = ? AND date_rdv = ? AND heure_rdv = ? AND status <> "cancelled"');
    $stmt->execute([$user_id, $date, $heure]);
    if ((int)$stmt->fetchColumn() > 0) return ['ok' => false, 'message' => 'Vous avez déjà un rendez-vous sur ce créneau'];

    $stmt = $pdo->prepare('INSERT INTO appointments (user_id, agent_id, date_rdv, heure_rdv, motif, status, notes_client) VALUES (?,?,?,?,?,"confirmed",?)');
    $stmt->execute([$user_id, $agent_id, $date, $heure, $motif, $notes]);
    
    $appointment_id = $pdo->lastInsertId();

    // Get client and agent details for email
    $stmt = $pdo->prepare('SELECT id, nom, prenom, email FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $client = $stmt->fetch();
    
    $stmt = $pdo->prepare('SELECT id, nom, prenom, email FROM users WHERE id = ?');
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();

    // Create appointment data for email
    $appointment = [
        'id' => $appointment_id,
        'date_rdv' => $date,
        'heure_rdv' => $heure,
        'motif' => $motif,
        'status' => 'confirmed',
        'notes_client' => $notes
    ];

    // Send confirmation emails
    try {
        $emailService = new EmailService();
        $emailSent = $emailService->sendAppointmentConfirmation($appointment, $client, $agent);
        
        if ($emailSent) {
            // Create notification
            $n = $pdo->prepare('INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?, "system", 0)');
            $n->execute([$user_id, "Rendez-vous confirmé pour le {$date} à {$heure}. Email de confirmation envoyé."]);
        }
    } catch (Throwable $e) {
        error_log("Email sending failed: " . $e->getMessage());
        // Don't fail the appointment creation if email fails
    }

    return ['ok' => true, 'message' => 'Rendez-vous créé et confirmé. Email de confirmation envoyé.'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur lors de la création du rendez-vous'];
  }
}
