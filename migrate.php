<?php
$json = file_get_contents(__DIR__ . '/renthouse_data.json');
$records = json_decode($json, true);
if (!$records) die("No JSON data\n");

$mysqli = new mysqli('localhost', 'march_2011', 'lohas2529time***', 'lohastime', 3306);
$mysqli->set_charset('utf8');

$fields = ['billMonth','startDate','endDate','totalBill','publicElec','baseFee','billingKwh',
  'meterAprev','meterAcurr','meterA','meterBprev','meterBcurr','meterB',
  'pricePerKwh','costA','costB','period','readingDate'];

foreach ($records as $r) {
  $id = (int)($r['id'] ?? 0);
  if (!$id) continue;
  $vals = [];
  foreach ($fields as $f) {
    $vals[] = "'" . $mysqli->real_escape_string($r[$f] ?? '') . "'";
  }
  $sql = "INSERT INTO renthouse_records (id," . implode(',', $fields) . ") VALUES ($id," . implode(',', $vals) . ") ON DUPLICATE KEY UPDATE ";
  $updates = [];
  foreach ($fields as $f) {
    $updates[] = "$f=VALUES($f)";
  }
  $sql .= implode(',', $updates);
  $mysqli->query($sql);
}
echo "Done: " . count($records) . " records\n";
