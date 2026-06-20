<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// DB 連線 — 從 .renthouse_db.cnf 讀(避免密碼硬編)
$_db_cnf = parse_ini_file('/share/CACHEDEV1_DATA/Web/.renthouse_db.cnf', true);
if ($_db_cnf === false || empty($_db_cnf['client'])) {
  // fallback 給舊環境(萬一 cnf 讀不到)
  $_db_host = 'localhost'; $_db_user = 'march_2011'; $_db_pass = 'lohas2529time***'; $_db_port = 3306;
} else {
  $_db_host = $_db_cnf['client']['host'] ?? 'localhost';
  $_db_user = $_db_cnf['client']['user'] ?? '';
  $_db_pass = $_db_cnf['client']['password'] ?? '';
  $_db_port = (int)($_db_cnf['client']['port'] ?? 3306);
}
$mysqli = new mysqli($_db_host, $_db_user, $_db_pass, 'lohastime', $_db_port);
if ($mysqli->connect_error) exit(json_encode(['error' => 'DB connection failed']));
$mysqli->set_charset(($_db_cnf['client']['default-character-set'] ?? 'utf8'));

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
    if (isset($row['paid'])) $row['paid'] = (int)$row['paid'];
    if (isset($row['paidA']) && $row['paidA'] !== null) $row['paidA'] = (float)$row['paidA'];
    if (isset($row['paidB']) && $row['paidB'] !== null) $row['paidB'] = (float)$row['paidB'];
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
        'costA', 'costB', 'period', 'readingDate', 'meterDate', 'meterDateNext',
        'paid', 'paidA', 'paidB'
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

  if ($action === 'electric_set_paid') {
    $id = (int)($input['id'] ?? 0);
    $paid = empty($input['paid']) ? 0 : 1;
    $mysqli->query('UPDATE renthouse_records SET paid = ' . $paid . ' WHERE id = ' . $id);
    echo json_encode(['ok' => true, 'paid' => $paid]);
    exit;
  }

  if ($action === 'set_paid_amount') {
    // table: 'electric' or 'water', id, unit: 'A' or 'B', amount
    $table = ($input['table'] ?? '') === 'water' ? 'renthouse_water_records' : 'renthouse_records';
    $id = (int)($input['id'] ?? 0);
    $unit = ($input['unit'] ?? '') === 'B' ? 'paidB' : 'paidA';
    $amount = $input['amount'] === null ? 'NULL' : (float)$input['amount'];
    $mysqli->query("UPDATE $table SET $unit = $amount WHERE id = $id");
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

  if ($action === 'water_save') {
    $r = $input;
    if (empty($r['id'])) $r['id'] = round(microtime(true) * 1000);
    $id = (int)$r['id'];
    $fields = [
      'billMonth','startDate','endDate','readDate','readDateNext',
      'totalBill','baseFee','wWaterFee','wExtraFee',
      'wMeterPrev','wMeterCurr','wSubMeter','billingKwh',
      'wAprev','wAcurr','wBprev','wBcurr',
      'mode','costA','costB','paid','paidA','paidB'
    ];
    $vals = [];
    foreach ($fields as $f) { $vals[] = '\'' . $mysqli->real_escape_string($r[$f] ?? '') . '\''; }
    $sql = 'INSERT INTO renthouse_water_records (id,' . implode(',', $fields) . ') VALUES (' . $id . ',' . implode(',', $vals) . ') ON DUPLICATE KEY UPDATE ';
    $ups = [];
    foreach ($fields as $f) { $ups[] = $f . '=VALUES(' . $f . ')'; }
    $sql .= implode(',', $ups);
    $mysqli->query($sql);
    if ($mysqli->error) { echo json_encode(['error' => $mysqli->error, 'sql' => $sql]); exit; }
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
  }

  if ($action === 'water_delete') {
    $id = (int)($input['id'] ?? 0);
    $mysqli->query('DELETE FROM renthouse_water_records WHERE id = ' . $id);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'water_set_paid') {
    $id = (int)($input['id'] ?? 0);
    $paid = empty($input['paid']) ? 0 : 1;
    $mysqli->query('UPDATE renthouse_water_records SET paid = ' . $paid . ' WHERE id = ' . $id);
    echo json_encode(['ok' => true, 'paid' => $paid]);
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

  if ($action === 'water_list') {
    $result = $mysqli->query('SELECT * FROM renthouse_water_records ORDER BY readDate DESC, id DESC');
    $rows = [];
    $strFields = ['billMonth','startDate','endDate','readDate','readDateNext','mode'];
    while ($row = $result->fetch_assoc()) {
      foreach ($row as $k => $v) {
        if (in_array($k, $strFields, true)) continue;
        if (is_numeric($v) && strpos($k,'id')===false) $row[$k] = (float)$v;
      }
      $row['id'] = (int)$row['id'];
      $rows[] = $row;
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // wallet history(預繳/提款/結清/結算)— GET list
  if ($action === 'wallet_list') {
    $result = $mysqli->query('SELECT * FROM renthouse_payment_history ORDER BY accountDate DESC, settledAt DESC');
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      // numeric fields
      foreach (['received','adjust','totalPaidDue','carryIn','carryOut'] as $f) {
        if (isset($row[$f]) && $row[$f] !== null) $row[$f] = (float)$row[$f];
      }
      // JSON fields decode
      foreach (['paidItems','pickerSelection'] as $f) {
        if (!empty($row[$f])) {
          $decoded = json_decode($row[$f], true);
          if ($decoded !== null) $row[$f] = $decoded;
        } else {
          $row[$f] = [];
        }
      }
      $rows[] = $row;
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// POST endpoints for wallet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($action === 'wallet_upsert') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['settledAt']) || empty($body['unit'])) {
      http_response_code(400);
      echo '{"error":"settledAt and unit required"}';
      exit;
    }
    $stmt = $mysqli->prepare(
      'INSERT INTO renthouse_payment_history
        (settledAt, unit, type, accountDate, payDate, received, adjust, totalPaidDue, carryIn, carryOut, closeType, closeAction, paidItems, pickerSelection, note, updatedAt)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
       ON DUPLICATE KEY UPDATE
        unit=VALUES(unit), type=VALUES(type), accountDate=VALUES(accountDate), payDate=VALUES(payDate),
        received=VALUES(received), adjust=VALUES(adjust), totalPaidDue=VALUES(totalPaidDue),
        carryIn=VALUES(carryIn), carryOut=VALUES(carryOut), closeType=VALUES(closeType), closeAction=VALUES(closeAction),
        paidItems=VALUES(paidItems), pickerSelection=VALUES(pickerSelection), note=VALUES(note), updatedAt=VALUES(updatedAt)');
    $settledAt = $body['settledAt'];
    $unit = $body['unit'];
    $type = $body['type'] ?? 'settle';
    $accountDate = $body['accountDate'] ?? '';
    $payDate = $body['payDate'] ?? '';
    $received = (float)($body['received'] ?? 0);
    $adjust = (float)($body['adjust'] ?? 0);
    $totalPaidDue = (float)($body['totalPaidDue'] ?? 0);
    $carryIn = (float)($body['carryIn'] ?? 0);
    $carryOut = (float)($body['carryOut'] ?? 0);
    $closeType = $body['closeType'] ?? null;
    $closeAction = $body['closeAction'] ?? null;
    $paidItems = isset($body['paidItems']) ? json_encode($body['paidItems'], JSON_UNESCAPED_UNICODE) : '[]';
    $pickerSelection = isset($body['pickerSelection']) ? json_encode($body['pickerSelection'], JSON_UNESCAPED_UNICODE) : '[]';
    $note = $body['note'] ?? '';
    $updatedAt = $body['updatedAt'] ?? date('c');
    $stmt->bind_param('sssssdddddssssss',
      $settledAt, $unit, $type, $accountDate, $payDate,
      $received, $adjust, $totalPaidDue, $carryIn, $carryOut,
      $closeType, $closeAction, $paidItems, $pickerSelection, $note, $updatedAt);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok, 'settledAt' => $settledAt]);
    exit;
  }

  if ($action === 'wallet_delete') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['settledAt'])) {
      http_response_code(400);
      echo '{"error":"settledAt required"}';
      exit;
    }
    $stmt = $mysqli->prepare('DELETE FROM renthouse_payment_history WHERE settledAt=?');
    $sa = $body['settledAt'];
    $stmt->bind_param('s', $sa);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok, 'deleted' => $stmt->affected_rows]);
    exit;
  }

  // 批次匯入(localStorage → DB migration)
  if ($action === 'wallet_bulk_import') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body) || empty($body['records'])) {
      http_response_code(400);
      echo '{"error":"records[] required"}';
      exit;
    }
    $imported = 0;
    $errors = [];
    $stmt = $mysqli->prepare(
      'INSERT IGNORE INTO renthouse_payment_history
        (settledAt, unit, type, accountDate, payDate, received, adjust, totalPaidDue, carryIn, carryOut, closeType, closeAction, paidItems, pickerSelection, note, updatedAt)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($body['records'] as $h) {
      if (empty($h['settledAt']) || empty($h['unit'])) { $errors[] = 'skip: missing settledAt/unit'; continue; }
      $settledAt = $h['settledAt'];
      $unit = $h['unit'];
      $type = $h['type'] ?? 'settle';
      $accountDate = $h['accountDate'] ?? '';
      $payDate = $h['payDate'] ?? '';
      $received = (float)($h['received'] ?? 0);
      $adjust = (float)($h['adjust'] ?? 0);
      $totalPaidDue = (float)($h['totalPaidDue'] ?? 0);
      $carryIn = (float)($h['carryIn'] ?? 0);
      $carryOut = (float)($h['carryOut'] ?? 0);
      $closeType = $h['closeType'] ?? null;
      $closeAction = $h['closeAction'] ?? null;
      $paidItems = isset($h['paidItems']) ? json_encode($h['paidItems'], JSON_UNESCAPED_UNICODE) : '[]';
      $pickerSelection = isset($h['pickerSelection']) ? json_encode($h['pickerSelection'], JSON_UNESCAPED_UNICODE) : '[]';
      $note = $h['note'] ?? '';
      $updatedAt = $h['updatedAt'] ?? date('c');
      $stmt->bind_param('sssssdddddssssss',
        $settledAt, $unit, $type, $accountDate, $payDate,
        $received, $adjust, $totalPaidDue, $carryIn, $carryOut,
        $closeType, $closeAction, $paidItems, $pickerSelection, $note, $updatedAt);
      if ($stmt->execute() && $stmt->affected_rows > 0) $imported++;
    }
    echo json_encode(['ok' => true, 'imported' => $imported, 'errors' => $errors]);
    exit;
  }
}

http_response_code(400);
echo '{"error":"Unknown action"}';
