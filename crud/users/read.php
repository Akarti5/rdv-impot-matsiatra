<?php
declare(strict_types=1);

function users_find_by_email(string $email): ?array {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function users_find_by_id(int $id): ?array {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function users_list(?string $type = null): array {
  $pdo = db();
  if ($type) {
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, phone, nif, status, type_user FROM users WHERE type_user = ? AND status = 'active' ORDER BY prenom, nom");
    $stmt->execute([$type]);
  } else {
    $stmt = $pdo->query("SELECT id, nom, prenom, email, phone, nif, status, type_user FROM users ORDER BY created_at DESC");
  }
  return $stmt->fetchAll();
}
