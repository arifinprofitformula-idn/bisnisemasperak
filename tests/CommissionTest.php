<?php
function calcSponsorCommission(array $komisiSet, bool $isPremium, float $paidAmount): array {
    $type = (isset($komisiSet['type']) && in_array($komisiSet['type'], ['percent','fixed'])) ? $komisiSet['type'] : 'fixed';
    $levels = [];
    for ($lvl = 1; $lvl <= 3; $lvl++) {
        $val = $isPremium ? (float)($komisiSet['premium'][$lvl] ?? 0) : (float)($komisiSet['free'][$lvl] ?? 0);
        if ($val <= 0) { $levels[$lvl] = 0; continue; }
        if ($type === 'percent') { $levels[$lvl] = (int)floor($paidAmount * max(0.0, min(100.0, $val)) / 100.0); }
        else { $levels[$lvl] = (int)max(0, $val); }
    }
    return $levels;
}

function calcContribCommission(array $contribs, float $paidAmount): array {
    $out = [];
    foreach ($contribs as $c) {
        $id = (int)($c['member_id'] ?? 0);
        $type = (isset($c['type']) && in_array($c['type'], ['percent','fixed'])) ? $c['type'] : 'fixed';
        $val = (float)($c['value'] ?? 0);
        if ($id <= 0 || $val <= 0) { continue; }
        $nom = ($type === 'percent') ? (int)floor($paidAmount * max(0.0, min(100.0, $val)) / 100.0) : (int)max(0, $val);
        $out[$id] = $nom;
    }
    return $out;
}

function assertEqual($a, $b, $label) {
    if ($a !== $b) { echo "[FAIL] $label => expected ".var_export($b,true).", got ".var_export($a,true)."\n"; return false; }
    echo "[PASS] $label\n"; return true;
}

$komisiSet = [
    'type' => 'percent',
    'premium' => [1 => 30, 2 => 10, 3 => 5],
    'free' => [1 => 30, 2 => 0, 3 => 0],
];

$hargaProduk = 149000;
$hargaPromo = 49000;

// Promo: 30% dari Rp49.000 => Rp14.700 level-1
$sponsorPromo = calcSponsorCommission($komisiSet, true, $hargaPromo);
assertEqual($sponsorPromo[1], 14700, 'Sponsor L1 promo 30% dari 49.000');

// Normal: 30% dari Rp149.000 => Rp44.700 level-1
$sponsorNormal = calcSponsorCommission($komisiSet, true, $hargaProduk);
assertEqual($sponsorNormal[1], 44700, 'Sponsor L1 normal 30% dari 149.000');

// Kontributor: 2 orang, 5% dan fixed Rp3.000
$contribs = [
    ['member_id' => 71, 'type' => 'percent', 'value' => 5],
    ['member_id' => 72, 'type' => 'fixed', 'value' => 3000],
];
$cPromo = calcContribCommission($contribs, $hargaPromo);
assertEqual($cPromo[71], 2450, 'Kontributor 5% dari 49.000');
assertEqual($cPromo[72], 3000, 'Kontributor fixed 3.000');

$cNormal = calcContribCommission($contribs, $hargaProduk);
assertEqual($cNormal[71], 7450, 'Kontributor 5% dari 149.000');
assertEqual($cNormal[72], 3000, 'Kontributor fixed tetap 3.000');

$both = $sponsorPromo[1] + $cPromo[71];
assertEqual($both, 14700 + 2450, 'Sponsor+Kontributor jika orang yang sama');

echo "\nSemua pengujian selesai.\n";

