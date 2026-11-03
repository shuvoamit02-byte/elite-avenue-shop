<?php
header('Content-Type: application/json; charset=utf-8');
$PIN = '1234'; // CHANGE THIS and use the same passcode in Admin > Settings
$f = __DIR__ . '/eab_orders.php';
$h = "<?php http_response_code(403);exit;?>\n";
$s = @file_get_contents($f);
$o = $s ? (json_decode(substr($s, strlen($h)), true) ?: []) : [];
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$a = $_GET['a'] ?? '';
$ST = ['Pending', 'Accepted', 'Confirmed', 'Dispatched', 'Declined'];
function sv($f, $h, $o) { file_put_contents($f, $h . json_encode($o, JSON_UNESCAPED_UNICODE), LOCK_EX); }
function need($PIN, $in) { if (($in['pin'] ?? '') !== $PIN) { http_response_code(403); exit('{"error":"pin"}'); } }
function cl($v) { return mb_substr(strip_tags((string)$v), 0, 300); }

if ($a === 'place') {
  if (empty($in['n']) || empty($in['p']) || empty($in['a']) || empty($in['items']) || !is_array($in['items'])) { http_response_code(400); exit('{}'); }
  $items = [];
  foreach (array_slice($in['items'], 0, 50) as $i) {
    $items[] = ['n' => cl($i['n'] ?? ''), 'size' => cl($i['size'] ?? ''), 'color' => cl($i['color'] ?? ''), 'pr' => (float)($i['pr'] ?? 0), 'qty' => max(1, (int)($i['qty'] ?? 1))];
  }
  $d = ['id' => 'EA' . date('ymd') . random_int(1000, 9999), 'date' => date('d M Y, h:i A'), 'n' => cl($in['n']), 'p' => cl($in['p']), 'a' => cl($in['a']), 'area' => cl($in['area'] ?? ''), 'items' => $items, 'sub' => (float)($in['sub'] ?? 0), 'ship' => (float)($in['ship'] ?? 0), 'total' => (float)($in['total'] ?? 0), 'st' => 'Pending'];
  array_unshift($o, $d);
  sv($f, $h, $o);
  echo json_encode(['id' => $d['id']]);
} elseif ($a === 'track') {
  $id = strtoupper(trim($in['id'] ?? ''));
  $p = preg_replace('/\D/', '', $in['p'] ?? '');
  foreach ($o as $x) {
    if ($x['id'] === $id && preg_replace('/\D/', '', $x['p']) === $p) { echo json_encode($x, JSON_UNESCAPED_UNICODE); exit; }
  }
  echo '{}';
} elseif ($a === 'list') {
  need($PIN, $in);
  echo json_encode($o, JSON_UNESCAPED_UNICODE);
} elseif ($a === 'status') {
  need($PIN, $in);
  $st = in_array($in['st'] ?? '', $ST, true) ? $in['st'] : 'Pending';
  foreach ($o as &$x) { if ($x['id'] === ($in['id'] ?? '')) $x['st'] = $st; }
  unset($x);
  sv($f, $h, $o);
  echo '{"ok":1}';
} elseif ($a === 'del') {
  need($PIN, $in);
  $o = array_values(array_filter($o, fn($x) => $x['id'] !== ($in['id'] ?? '')));
  sv($f, $h, $o);
  echo '{"ok":1}';
} else {
  echo '{}';
}
