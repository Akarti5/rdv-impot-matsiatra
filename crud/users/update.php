<?php
declare(strict_types=1);

function users_update(int $id, array $payload, array $actor): array {
  // Only owner can update their profile; agents can only update their own base info in this minimal implementation.
  if ($actor['id'] !== $id) return ['ok' => false, 'message' => 'Non autorisé'];

  $fields = [];
  $params = [];
  foreach (['nom','prenom','phone'] as $key) {
    if (isset($payload[$key]) && $payload[$key] !== '') {
      $fields[] = "$key = ?";
      $params[] = trim((string)$payload[$key]);
    }
  }
  if (isset($payload['password']) && $payload['password']) {
    if (strlen((string)$payload['password']) < 8) return ['ok' => false, 'message' => 'Mot de passe trop court'];
    $fields[] = "password_hash = ?";
    $params[] = password_hash((string)$payload['password'], PASSWORD_DEFAULT);
  }
  if (!$fields) return ['ok' => false, 'message' => 'Aucune modification'];

  $params[] = $id;
  $pdo = db();
  try {
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['ok' => true, 'message' => 'Profil mis à jour'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur lors de la mise à jour'];
  }
}
