<?php
declare(strict_types=1);

function appointments_delete(int $id, array $actor): array {
  // Soft delete through status in this app; explicit delete not used.
  return ['ok' => false, 'message' => 'Suppression non supportée'];
}
