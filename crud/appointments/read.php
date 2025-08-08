<?php
declare(strict_types=1);

function appointments_read_by_user(int $userId): array {
  $pdo = db();
  $sql = 'SELECT a.*, CONCAT(u2.prenom, " ", u2.nom) AS agent_name
          FROM appointments a
          LEFT JOIN users u2 ON a.agent_id = u2.id
          WHERE a.user_id = ?
          ORDER BY a.date_rdv DESC, a.heure_rdv DESC';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$userId]);
  $rows = $stmt->fetchAll();

  $now = new DateTimeImmutable('now');
  foreach ($rows as &$r) {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $r['date_rdv'] . ' ' . $r['heure_rdv']);
    $r['cancellable'] = $r['status'] !== 'cancelled' && $dt && $dt > $now->modify('+24 hours');
  }
  return $rows;
}

function appointments_read_by_agent(int $agentId, string $filter = 'today'): array {
  $pdo = db();
  $where = 'agent_id = ?';
  $params = [$agentId];

  if ($filter === 'today') {
    $where .= ' AND date_rdv = ?';
    $params[] = date('Y-m-d');
    $order = 'heure_rdv ASC';
  } else if ($filter === 'upcoming') {
    $where .= ' AND date_rdv >= ?';
    $params[] = date('Y-m-d');
    $order = 'date_rdv ASC, heure_rdv ASC';
  } else {
    $order = 'date_rdv DESC, heure_rdv DESC';
  }

  $sql = "SELECT a.*, CONCAT(u.prenom, ' ', u.nom) AS client_name
          FROM appointments a
          LEFT JOIN users u ON a.user_id = u.id
          WHERE {$where}
          ORDER BY {$order}";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}
