<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/email_service.php';

/**
 * Create appointment ensuring slot availability.
 */
function appointments_create(array $payload): array {
  // Debug: Log the incoming payload
  error_log("Appointment creation payload: " . json_encode($payload));
  
  $user_id = (int)($payload['user_id'] ?? 0);
  $agent_id = (int)($payload['agent_id'] ?? 0);
  $date = (string)($payload['date_rdv'] ?? '');
  $heure = (string)($payload['heure_rdv'] ?? '');
  $motif = trim((string)($payload['motif'] ?? ''));
  $notes = trim((string)($payload['notes_client'] ?? ''));

  // Debug: Log the extracted values
  error_log("Extracted values - user_id: $user_id, agent_id: $agent_id, date: $date, heure: $heure, motif: $motif");

  if (!$user_id || !$agent_id || !$date || !$heure || !$motif) {
    $missing = [];
    if (!$user_id) $missing[] = 'user_id';
    if (!$agent_id) $missing[] = 'agent_id';
    if (!$date) $missing[] = 'date_rdv';
    if (!$heure) $missing[] = 'heure_rdv';
    if (!$motif) $missing[] = 'motif';
    error_log("Missing required fields: " . implode(', ', $missing));
    return ['ok' => false, 'message' => 'Champs requis manquants: ' . implode(', ', $missing)];
  }

  $pdo = db();
  try {
    // Debug: Check if time slots exist for this agent and date
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM time_slots WHERE agent_id = ? AND date = ?');
    $stmt->execute([$agent_id, $date]);
    $slotCount = (int)$stmt->fetchColumn();
    error_log("Found $slotCount time slots for agent $agent_id on date $date");

    // Verify the time falls into an available slot and capacity not exceeded
    $stmt = $pdo->prepare('SELECT id, max_appointments FROM time_slots WHERE agent_id = ? AND date = ? AND heure_debut <= ? AND heure_fin > ? AND is_available = 1');
    $stmt->execute([$agent_id, $date, $heure, $heure]);
    $slot = $stmt->fetch();
    
    if (!$slot) {
      error_log("No available time slot found for agent $agent_id, date $date, time $heure");
      return ['ok' => false, 'message' => 'Créneau indisponible. Veuillez vérifier la date et l\'heure sélectionnées.'];
    }

    error_log("Found time slot: " . json_encode($slot));

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE agent_id = ? AND date_rdv = ? AND heure_rdv >= (SELECT heure_debut FROM time_slots WHERE id = ?) AND heure_rdv < (SELECT heure_fin FROM time_slots WHERE id = ?) AND status <> "cancelled"');
    $stmt->execute([$agent_id, $date, (int)$slot['id'], (int)$slot['id']]);
    $count = (int)$stmt->fetchColumn();
    error_log("Current appointments in slot: $count, max allowed: " . $slot['max_appointments']);
    
    if ($count >= (int)$slot['max_appointments']) {
      error_log("Slot capacity exceeded");
      return ['ok' => false, 'message' => 'Capacité atteinte pour ce créneau'];
    }

    // Prevent duplicate appointment for same user+time
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE user_id = ? AND date_rdv = ? AND heure_rdv = ? AND status <> "cancelled"');
    $stmt->execute([$user_id, $date, $heure]);
    if ((int)$stmt->fetchColumn() > 0) {
      error_log("Duplicate appointment detected");
      return ['ok' => false, 'message' => 'Vous avez déjà un rendez-vous sur ce créneau'];
    }

    // Insert the appointment
    $stmt = $pdo->prepare('INSERT INTO appointments (user_id, agent_id, date_rdv, heure_rdv, motif, status, notes_client) VALUES (?,?,?,?,?,"confirmed",?)');
    $result = $stmt->execute([$user_id, $agent_id, $date, $heure, $motif, $notes]);
    
    if (!$result) {
      error_log("Failed to insert appointment: " . json_encode($stmt->errorInfo()));
      return ['ok' => false, 'message' => 'Erreur lors de l\'insertion du rendez-vous'];
    }
    
    $appointment_id = $pdo->lastInsertId();
    error_log("Appointment created successfully with ID: $appointment_id");

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
    error_log("Exception in appointment creation: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    return ['ok' => false, 'message' => 'Erreur lors de la création du rendez-vous: ' . $e->getMessage()];
  }
}
