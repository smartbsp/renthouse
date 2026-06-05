<?php
$mysqli = new mysqli('localhost', 'march_2011', 'lohas2529time***', 'lohastime');
$result = $mysqli->query('SELECT data FROM renthouse_tenants WHERE id = 1');
$row = $result->fetch_assoc();
$data = json_decode($row['data'], true);
$month = date('Y-m');
foreach (['A','B'] as $unit) {
  foreach ($data[$unit] ?? [] as $i => $t) {
    if (!empty($t['name'])) {
      $u = $mysqli->real_escape_string($unit);
      $n = $mysqli->real_escape_string($t['name']);
      $dj = $mysqli->real_escape_string(json_encode($t, JSON_UNESCAPED_UNICODE));
      $mysqli->query("INSERT INTO renthouse_tenant_log (unit, tenant_index, tenant_name, data, log_month) VALUES ('$u', $i, '$n', '$dj', '$month')");
    }
  }
}
echo "OK\n";
