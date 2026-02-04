<?php
// Minimal unit test for PPh21 net calculation logic
// Usage: php tests/pph21_net_calc.php

$cases = [
  ['gross'=>0,     'pph'=>0.00,  'expected_net'=>0],
  ['gross'=>10000, 'pph'=>0.00,  'expected_net'=>10000],
  ['gross'=>10000, 'pph'=>2.50,  'expected_net'=>10000 - (int)round(10000*0.025)],
  ['gross'=>12345, 'pph'=>2.50,  'expected_net'=>12345 - (int)round(12345*0.025)],
  ['gross'=>50000, 'pph'=>10.00, 'expected_net'=>50000 - (int)round(50000*0.10)],
];

function calc_net($gross, $pph){
  if ($pph < 0) $pph = 0.0; if ($pph > 100) $pph = 100.0;
  $tax = (int)round($gross * ($pph/100.0));
  $net = max(0, $gross - $tax);
  return [$tax,$net];
}

$ok = 0; $fail = 0; $out = [];
foreach ($cases as $i=>$c){
  list($tax,$net) = calc_net($c['gross'], $c['pph']);
  $pass = ($net === $c['expected_net']);
  $out[] = sprintf("Case %d: gross=%d pph=%.2f tax=%d net=%d => %s", $i+1, $c['gross'], $c['pph'], $tax, $net, $pass? 'PASS':'FAIL');
  if ($pass) $ok++; else $fail++;
}

echo implode("\n", $out), "\n";
echo sprintf("Summary: PASS=%d FAIL=%d\n", $ok, $fail);
if ($fail>0) { exit(1); }
exit(0);
?>
