<?php
if (!defined('IS_IN_SCRIPT')) { die(); }

class EpiDiscountEngine {
  public function loadRules() {
    static $cache = null; static $cacheTime = 0; $ttl = 10; // seconds
    if ($cache && (time() - $cacheTime) < $ttl) { return $cache; }
    $rows = db_select("SELECT * FROM sa_coupon WHERE status=1 ORDER BY priority DESC, code ASC");
    $rules = [];
    foreach(($rows?:[]) as $r){
      $rules[] = [
        'id' => (int)$r['id'],
        'type' => $r['type'],
        'value' => (int)$r['value'],
        'code' => $r['code'],
        'priority' => (int)$r['priority'],
        'min_purchase' => (int)$r['min_purchase'],
        'start' => $r['start_at'],
        'end' => $r['end_at'],
        'max_usage' => $r['max_usage'] !== null ? (int)$r['max_usage'] : null,
        'used_count' => (int)$r['used_count'],
        'scope_all' => (int)$r['scope_all']===1,
        'product_ids' => !empty($r['product_ids']) ? array_map('intval', explode(',', $r['product_ids'])) : [],
        'category_ids' => !empty($r['category_ids']) ? array_map('intval', explode(',', $r['category_ids'])) : [],
        'member_status' => isset($r['member_status_min']) && $r['member_status_min'] !== null ? (int)$r['member_status_min'] : null,
        'allowed_user_ids' => !empty($r['allowed_user_ids']) ? array_map('intval', explode(',', $r['allowed_user_ids'])) : [],
      ];
    }
    $cache = $rules; $cacheTime = time(); return $rules;
  }

  public function calculateSingle($rules, $ctx) {
    $base = (int)($ctx['base'] ?? 0);
    $display = (int)($ctx['display'] ?? $base);
    $promo = trim((string)($ctx['promo'] ?? ''));
    $pid = (int)($ctx['product_id'] ?? 0);
    $qty = max(1, (int)($ctx['qty'] ?? 1));
    $member = $ctx['member'] ?? null;
    $baseForDiscount = ($display > 0 && $display < $base) ? $display : $base;

    $applicable = [];
    foreach ($rules as $r) {
      if (!$this->isEligible($r, $ctx)) { continue; }
      $calc = $this->applyRule($r, $baseForDiscount, $qty, $member);
      if ($calc['discount'] > 0) { $applicable[] = ['rule' => $r, 'discount' => $calc['discount']]; }
    }

    // Exclusivity handling by group; choose max in each exclusive_group
    $byGroup = [];
    foreach ($applicable as $a) {
      $g = isset($a['rule']['exclusive_group']) ? $a['rule']['exclusive_group'] : null;
      if ($g) { if (!isset($byGroup[$g]) || $a['discount'] > $byGroup[$g]['discount']) { $byGroup[$g] = $a; } }
    }
    $stackable = [];
    foreach ($applicable as $a) {
      $g = isset($a['rule']['exclusive_group']) ? $a['rule']['exclusive_group'] : null;
      $isStack = isset($a['rule']['stackable']) ? (bool)$a['rule']['stackable'] : true;
      if ($g) { $stackable[$g] = $byGroup[$g]; }
      elseif ($isStack) { $stackable[] = $a; }
    }
    // Sum discounts
    $totalDiscount = 0; $applied = [];
    foreach ($stackable as $k => $a) { $totalDiscount += (int)$a['discount']; $applied[] = $a['rule']; }
    $final = max(0, $baseForDiscount - $totalDiscount);
    return ['rule' => $applied, 'discount' => $totalDiscount, 'final' => $final];
  }

  public function calculateCart($rules, $items, $member = null) {
    $totalBefore = 0; $totalDiscount = 0; $applied = [];
    foreach ($items as $it) {
      $ctx = [
        'base' => (int)($it['base'] ?? 0),
        'display' => (int)($it['display'] ?? 0),
        'promo' => (string)($it['promo'] ?? ''),
        'product_id' => (int)($it['product_id'] ?? 0),
        'qty' => (int)($it['qty'] ?? 1),
        'member' => $member
      ];
      $totalBefore += ((int)$ctx['display'] ?: (int)$ctx['base']) * max(1,(int)$ctx['qty']);
      $res = $this->calculateSingle($rules, $ctx);
      $totalDiscount += (int)$res['discount']; $applied[] = $res['rule'];
    }
    $final = max(0, $totalBefore - $totalDiscount);
    return ['final' => $final, 'discount' => $totalDiscount, 'applied' => $applied];
  }

  private function isEligible($r, $ctx) {
    $now = time(); $promo = trim((string)($ctx['promo'] ?? ''));
    if (!empty($r['code'])) { if (strcasecmp($r['code'], $promo) !== 0) { return false; } }
    if (isset($r['max_usage']) && $r['max_usage'] !== null) { if ((int)$r['used_count'] >= (int)$r['max_usage']) { return false; } }
    if (!empty($r['product_ids'])) {
      $pids = array_map('intval', (array)$r['product_ids']);
      if (!in_array((int)$ctx['product_id'], $pids, true)) { return false; }
    }
    // Category check (if product has category mapping function)
    if (!empty($r['category_ids']) && function_exists('get_product_categories')) {
      $cats = array_map('intval', (array)get_product_categories((int)$ctx['product_id']));
      if (count(array_intersect($cats, array_map('intval',$r['category_ids'])))===0) { return false; }
    }
    if (!empty($r['start']) && strtotime($r['start']) && $now < strtotime($r['start'])) { return false; }
    if (!empty($r['end']) && strtotime($r['end']) && $now > strtotime($r['end'])) { return false; }
    if (isset($r['min_purchase']) && (int)$r['min_purchase'] > 0) {
      $total = ((int)$ctx['display'] ?: (int)$ctx['base']) * max(1, (int)$ctx['qty']);
      if ($total < (int)$r['min_purchase']) { return false; }
    }
    // Member status eligibility
    if (isset($r['member_status']) && $r['member_status'] !== null) {
      $mem = $ctx['member'] ?? null; if (!$mem) { return false; }
      $want = (int)$r['member_status']; $have = isset($mem['mem_status']) ? (int)$mem['mem_status'] : 0;
      if ($have < $want) { return false; }
    }
    if (!empty($r['allowed_user_ids'])) {
      $uid = isset($ctx['member']['mem_id']) ? (int)$ctx['member']['mem_id'] : 0;
      if ($uid<=0 || !in_array($uid, array_map('intval', $r['allowed_user_ids']), true)) { return false; }
    }
    return true;
  }

  private function applyRule($r, $baseForDiscount, $qty, $member) {
    $type = strtolower((string)($r['type'] ?? ''));
    $val = (int)($r['value'] ?? 0);
    $discount = 0;
    if ($type === 'percent' && $val > 0) { $discount = (int)floor(($baseForDiscount * $val) / 100); }
    elseif ($type === 'fixed' && $val > 0) { $discount = $val; }
    elseif ($type === 'bogo') {
      // Buy N get M free: params buy_qty, get_qty
      $buy = max(1, (int)($r['buy_qty'] ?? 0)); $get = max(0, (int)($r['get_qty'] ?? 0));
      if ($buy > 0 && $get > 0 && $qty >= $buy + $get) {
        // Value of free items based on unit price (baseForDiscount per 1)
        $unit = $baseForDiscount; $discount = $unit * $get;
      }
    }
    elseif ($type === 'member') {
      // Member-specific fixed or percent via nested rule
      $mode = strtolower((string)($r['mode'] ?? 'percent'));
      if ($mode === 'percent' && $val > 0) { $discount = (int)floor(($baseForDiscount * $val) / 100); }
      elseif ($mode === 'fixed' && $val > 0) { $discount = $val; }
    }
    if ($discount < 0) { $discount = 0; }
    if ($discount > $baseForDiscount) { $discount = $baseForDiscount; }
    return ['discount' => $discount];
  }
}
