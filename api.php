<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$dbFile = __DIR__ . '/renthouse_data.json';
$tenantFile = __DIR__ . '/renthouse_tenants.json';
$draftFile = __DIR__ . '/renthouse_draft.json';

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
  $raw = file_get_contents('php://input');
  $input = $raw ? json_decode($raw, true) : [];
  if ($input === null) { http_response_code(400); echo '{"error":"Invalid JSON"}'; exit; }

  if ($action === 'clear') {
    saveDB([]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if (!$input || empty($input)) { http_response_code(400); echo '{"error":"Invalid JSON"}'; exit; }

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

  if ($action === 'tenant_save') {
    file_put_contents($tenantFile, json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'draft_save') {
    file_put_contents($draftFile, json_encode($input, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if ($action === 'tenant_list') {
    if (file_exists($tenantFile)) {
      echo file_get_contents($tenantFile);
    } else {
      echo '{}';
    }
    exit;
  }

  if ($action === 'draft_load') {
    if (file_exists($draftFile)) {
      echo file_get_contents($draftFile);
    } else {
      echo '{}';
    }
    exit;
  }
}

http_response_code(400);
echo '{"error":"Unknown action"}';
