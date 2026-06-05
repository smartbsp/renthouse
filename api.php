<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$mysqli = new mysqli('localhost', 'march_2011', 'lohas2529time***', 'lohastime', 3306);
if ($mysqli->connect_error) exit(json_encode(['error' => 'DB connection failed']));
$mysqli->set_charset('utf8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
  $result = $mysqli->query('SELECT * FROM renthouse_records ORDER BY endDate DESC');
  $records = [];
  while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['totalBill'] = (float)$row['totalBill'];
    $row['publicElec'] = (float)$row['publicElec'];
    $row['baseFee'] = (float)$row['baseFee'];
    $row['billingKwh'] = (float)$row['billingKwh'];
    $row['meterAprev'] = (float)$row['meterAprev'];
    $row['meterAcurr'] = (float)$row['meterAcurr'];
    $row['meterA'] = (float)$row['meterA'];
    $row['meterBprev'] = (float)$row['meterBprev'];
    $row['meterBcurr'] = (float)$row['meterBcurr'];
    $row['meterB'] = (float)$row['meterB'];
    $row['pricePerKwh'] = (float)$row['pricePerKwh'];
    $row['costA'] = (float)$row['costA'];
    $row['costB'] = (float)$row['costB'];
    $records[] = $row;
  }
  echo json_encode($records, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = file_get_contents('php://input');
  $input = $raw ? json_decode($raw, true) : [];
  if ($input === null) { http_response_code(400); echo '{"error":"Invalid JSON"}'; exit; }

  if ($action === 'clear') {
    $mysqli->query('DELETE FROM renthouse_records');
    echo json_encode(['ok' => true]);
    exit;
  }

  if (!$input || empty($input)) { http_response_code(400); echo '{"error":"Invalid JSON"}'; exit; }

  if ($action === 'save') {
    $newRecords = $input['records'] ?? [$input];
    $count = 0;
    foreach ($newRecords as $new) {
      if (empty($new['id'])) $new['id'] = round(microtime(true) * 1000);
      $id = (int)$new['id'];
      $fields = [
        'billMonth', 'startDate', 'endDate', 'totalBill', 'publicElec',
        'baseFee', 'billingKwh', 'meterAprev', 'meterAcurr', 'meterA',
        'meterBprev', 'meterBcurr', 'meterB', 'pricePerKwh',
        'costA', 'costB', 'period', 'readingDate', 'meterDate', 'meterDateNext'
      ];
      $vals = [];
      foreach ($fields as $f) {
        $vals[] = $mysqli->real_escape_string($new[$f] ?? '');
      }
      $sql = 'INSERT INTO renthouse_records (id,' . implode(',', $fields) . ') VALUES (' . $id . ',\'' . implode('\',\'', $vals) . '\') ON DUPLICATE KEY UPDATE ';
      $updates = [];
      foreach ($fields as $f) {
        $updates[] = $f . '=VALUES(' . $f . ')';
      }
      $sql .= implode(',', $updates);
      $mysqli->query($sql);
      $count++;
    }
    $result = $mysqli->query('SELECT COUNT(*) as cnt FROM renthouse_records');
    $cnt = $result->fetch_assoc()['cnt'];
    echo json_encode(['ok' => true, 'count' => (int)$cnt]);
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    $mysqli->query('DELETE FROM renthouse_records WHERE id = ' . $id);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'tenant_save') {
    $json = json_encode($input, JSON_UNESCAPED_UNICODE);
    $esc = $mysqli->real_escape_string($json);
    $mysqli->query('INSERT INTO renthouse_tenants (id, data) VALUES (1, \'' . $esc . '\') ON DUPLICATE KEY UPDATE data = \'' . $esc . '\'');
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'draft_save') {
    $json = json_encode($input, JSON_UNESCAPED_UNICODE);
    $esc = $mysqli->real_escape_string($json);
    $mysqli->query('INSERT INTO renthouse_drafts (id, data) VALUES (1, \'' . $esc . '\') ON DUPLICATE KEY UPDATE data = \'' . $esc . '\'');
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'tenant_log_save') {
    $unit = $mysqli->real_escape_string($input['unit'] ?? '');
    $name = $mysqli->real_escape_string($input['name'] ?? '');
    $idx = (int)($input['idx'] ?? 0);
    $month = $mysqli->real_escape_string($input['month'] ?? '');
    $json = json_encode($input['data'] ?? [], JSON_UNESCAPED_UNICODE);
    $esc = $mysqli->real_escape_string($json);
    $mysqli->query('INSERT INTO renthouse_tenant_log (unit, tenant_index, tenant_name, data, log_month) VALUES (\'' . $unit . '\', ' . $idx . ', \'' . $name . '\', \'' . $esc . '\', \'' . $month . '\')');
    echo json_encode(['ok' => true]);
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if ($action === 'tenant_list') {
    $result = $mysqli->query('SELECT data FROM renthouse_tenants WHERE id = 1');
    if ($row = $result->fetch_assoc()) {
      echo $row['data'];
    } else {
      echo '{}';
    }
    exit;
  }

  if ($action === 'draft_load') {
    $result = $mysqli->query('SELECT data FROM renthouse_drafts WHERE id = 1');
    if ($row = $result->fetch_assoc()) {
      echo $row['data'];
    } else {
      echo '{}';
    }
    exit;
  }

  if ($action === 'tenant_log_list') {
    $month = $_GET['month'] ?? '';
    $name = $_GET['name'] ?? '';
    $unit = $_GET['unit'] ?? '';
    $sql = 'SELECT * FROM renthouse_tenant_log WHERE 1=1';
    if ($unit) $sql .= ' AND unit = \'' . $mysqli->real_escape_string($unit) . '\'';
    if ($month) $sql .= ' AND log_month = \'' . $mysqli->real_escape_string($month) . '\'';
    if ($name) $sql .= ' AND tenant_name LIKE \'%' . $mysqli->real_escape_string($name) . '%\'';
    $sql .= ' ORDER BY created_at DESC LIMIT 200';
    $result = $mysqli->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $row['id'] = (int)$row['id'];
      $row['tenant_index'] = (int)$row['tenant_index'];
      $row['data'] = json_decode($row['data'], true);
      $rows[] = $row;
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

http_response_code(400);
echo '{"error":"Unknown action"}';
