<?php
/*
Name: EPI Discount Engine
Description: Modular discount engine (percent, fixed, BOGO, member).
*/
if (!defined('IS_IN_SCRIPT')) { die(); }
require_once __DIR__ . '/engine.php';

// Bootstrap DB tables (idempotent)
function epi_discount_install(){
  if (!function_exists('db_query')) { @include_once dirname(__DIR__,2) . DIRECTORY_SEPARATOR . 'fungsi.php'; }
  @db_query("CREATE TABLE IF NOT EXISTS `sa_coupon` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(64) NOT NULL,
    `type` ENUM('percent','fixed') NOT NULL,
    `value` INT(11) NOT NULL,
    `priority` INT(11) NOT NULL DEFAULT 0,
    `min_purchase` INT(11) DEFAULT 0,
    `start_at` DATETIME DEFAULT NULL,
    `end_at` DATETIME DEFAULT NULL,
    `max_usage` INT(11) DEFAULT NULL,
    `used_count` INT(11) NOT NULL DEFAULT 0,
    `scope_all` TINYINT(1) NOT NULL DEFAULT 1,
    `product_ids` TEXT DEFAULT NULL,
    `category_ids` TEXT DEFAULT NULL,
    `member_role_min` INT(11) DEFAULT NULL,
    `member_status_min` INT(11) DEFAULT NULL,
    `allowed_user_ids` TEXT DEFAULT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_code` (`code`),
    KEY `idx_status` (`status`),
    KEY `idx_window` (`start_at`,`end_at`),
    KEY `idx_priority` (`priority`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  @db_query("CREATE TABLE IF NOT EXISTS `sa_coupon_usage` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `coupon_id` INT(11) NOT NULL,
    `code` VARCHAR(64) NOT NULL,
    `user_id` INT(11) DEFAULT NULL,
    `order_id` INT(11) DEFAULT NULL,
    `product_id` INT(11) DEFAULT NULL,
    `discount_amount` INT(11) NOT NULL DEFAULT 0,
    `applied_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_coupon` (`coupon_id`),
    KEY `idx_code` (`code`),
    KEY `idx_user` (`user_id`),
    KEY `idx_order` (`order_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  @db_query("CREATE TABLE IF NOT EXISTS `sa_coupon_change_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `coupon_id` INT(11) DEFAULT NULL,
    `admin_id` INT(11) DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL,
    `details` TEXT,
    `changed_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_coupon` (`coupon_id`),
    KEY `idx_admin` (`admin_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if (!function_exists('epi_discount_health')) {
  function epi_discount_health(){
    return !!db_var("SHOW TABLES LIKE 'sa_coupon'");
  }
}
if (!epi_discount_health()) { epi_discount_install(); }
// Ensure new column exists when upgrading
try {
  $has = db_var("SHOW COLUMNS FROM sa_coupon LIKE 'member_status_min'");
  if (!$has) { db_query("ALTER TABLE sa_coupon ADD COLUMN `member_status_min` INT(11) DEFAULT NULL AFTER `member_role_min`"); }
} catch (Throwable $e) {}

add_filter('menu', function($menu){
  $menu['settings']['submenu']['coupons'] = array('Coupons', __DIR__.'/admin_coupons.php', 9);
  return $menu;
}, 10);

add_filter('discount_effective_price', function($payload){
  $engine = new EpiDiscountEngine();
  $rules = $engine->loadRules();
  $userId = is_login(); $member = $userId ? getdatamember($userId) : null;
  $ctx = array(
    'product_id' => (int)($payload['product_id'] ?? 0),
    'qty'        => (int)($payload['qty'] ?? 1),
    'promo'      => (string)($payload['promo'] ?? ''),
    'member'     => $member,
    'base'       => (int)($payload['base'] ?? 0),
    'display'    => (int)($payload['display'] ?? 0)
  );
  $res = $engine->calculateSingle($rules, $ctx);
  $payload['result'] = $res;
  return $payload;
}, 10);

// Helper: register usage
function epi_coupon_register_usage($code, $orderId, $productId, $userId, $amount){
  if (empty($code)) { return; }
  $c = db_row("SELECT id, used_count FROM sa_coupon WHERE code='".cek($code)."' LIMIT 1");
  $now = date('Y-m-d H:i:s');
  if ($c) {
    db_query("INSERT INTO sa_coupon_usage (coupon_id,code,user_id,order_id,product_id,discount_amount,applied_at) VALUES (
      ".(int)$c['id'].",'".cek($code)."',".(int)$userId.",".(int)$orderId.",".(int)$productId.",".(int)$amount.",'".$now."')");
    db_query("UPDATE sa_coupon SET used_count=used_count+1, updated_at='".$now."' WHERE id=".(int)$c['id']);
  } else {
    db_query("INSERT INTO sa_coupon_usage (coupon_id,code,user_id,order_id,product_id,discount_amount,applied_at) VALUES (
      0,'".cek($code)."',".(int)$userId.",".(int)$orderId.",".(int)$productId.",".(int)$amount.",'".$now."')");
  }
}
