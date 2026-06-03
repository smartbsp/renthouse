<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$dbFile = __DIR__ . '/renthouse_data.json';

function loadDB() {
  global $dbFile;
  if (!file_exists($dbFile)) {
    file_put_contents($dbFile, '[]');
    return [];
  }
  return json_decode(file_get_contents($dbFile), true) ?: [];
}

function saveDB($data) {
  global $dbFile;
  file_put_contents($dbFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  // backup
  $backupDir = __DIR__ . '/renthouse_backups';
  if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
  copy($dbFile, $backupDir . '/backup_' . date('Ymd_His') . '.json');
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
  $records = loadDB();
  echo json_encode($records, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  if (!$input) { http_response_code(400); echo '{"error":"Invalid JSON"}'; exit; }

  if ($action === 'save') {
    $records = loadDB();
    $newRecords = $input['records'] ?? [$input];

    foreach ($newRecords as $new) {
      if (empty($new['id'])) $new['id'] = round(microtime(true) * 1000);
      // replace existing or add new
      $found = false;
      foreach ($records as $i => $r) {
        if ($r['id'] === $new['id']) { $records[$i] = $new; $found = true; break; }
      }
      if (!$found) array_unshift($records, $new);
    }

    saveDB($records);
    echo json_encode(['ok' => true, 'count' => count($records)]);
    exit;
  }

  if ($action === 'delete') {
    $id = $input['id'] ?? 0;
    $records = loadDB();
    $records = array_values(array_filter($records, fn($r) => $r['id'] != $id));
    saveDB($records);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'clear') {
    saveDB([]);
    echo json_encode(['ok' => true]);
    exit;
  }
}

http_response_code(400);
echo '{"error":"Unknown action"}';
