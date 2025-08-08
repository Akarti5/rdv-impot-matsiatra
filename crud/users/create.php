<?php
declare(strict_types=1);

function users_create(array $payload): array {
  $nom = trim($payload['nom'] ?? '');
  $prenom = trim($payload['prenom'] ?? '');
  $email = strtolower(trim($payload['email'] ?? ''));
  $password = (string)($payload['password'] ?? '');
  $password2 = (string)($payload['password2'] ?? '');
  $phone = trim($payload['phone'] ?? '');
  $nif = trim($payload['nif'] ?? '');
  $type_user = $payload['type_user'] ?? 'client';

  if (!$nom || !$prenom || !$email || !$password) {
    return ['ok' => false, 'message' => 'Veuillez remplir tous les champs requis'];
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return ['ok' => false, 'message' => 'Email invalide'];
  }
  if (strlen($password) < 8) {
    return ['ok' => false, 'message' => 'Mot de passe trop court'];
  }
  if ($password !== $password2) {
    return ['ok' => false, 'message' => 'Les mots de passe ne correspondent pas'];
  }
  if (!in_array($type_user, ['client','agent'], true)) {
    $type_user = 'client';
  }

  $pdo = db();
  try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
      return ['ok' => false, 'message' => 'Email déjà utilisé'];
    }
    if ($nif) {
      $stmt = $pdo->prepare('SELECT id FROM users WHERE nif = ? LIMIT 1');
      $stmt->execute([$nif]);
      if ($stmt->fetchColumn()) {
        return ['ok' => false, 'message' => 'NIF déjà utilisé'];
      }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (nom, prenom, email, password_hash, phone, nif, type_user, status) VALUES (?,?,?,?,?,?,?, "active")');
    $stmt->execute([$nom, $prenom, $email, $hash, $phone, $nif ?: null, $type_user]);

    return ['ok' => true, 'message' => 'Compte créé avec succès'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur lors de la création du compte'];
  }
}
