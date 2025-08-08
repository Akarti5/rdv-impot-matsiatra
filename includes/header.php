<?php
$user = currentUser();
$csrf = csrf_token();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= s($csrf) ?>">
  <title><?= s(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= s(BASE_URL) ?>assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="app-header">
  <div class="container header-inner">
    <div class="brand">
      <a href="<?= s(BASE_URL) ?>" class="logo">RIM</a>
      <span class="brand-text">RDV Impôts Matsiatra</span>
    </div>
    <nav class="nav">
      <?php if ($user): ?>
        <?php if ($user['type_user'] === 'client'): ?>
          <a href="<?= s(BASE_URL) ?>?page=dashboard-client" class="nav-link">Mon espace</a>
        <?php else: ?>
          <a href="<?= s(BASE_URL) ?>?page=dashboard-agent" class="nav-link">Tableau de bord</a>
        <?php endif; ?>
        <button id="btn-logout" class="btn btn-outline">Déconnexion</button>
      <?php else: ?>
        <a href="<?= s(BASE_URL) ?>?page=login" class="nav-link">Connexion</a>
        <a href="<?= s(BASE_URL) ?>?page=register" class="btn">Créer un compte</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container page">
