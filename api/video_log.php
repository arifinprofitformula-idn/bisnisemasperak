<?php
chdir(__DIR__ . '/../');
require_once 'config.php';
require_once 'fungsi.php';
header('Content-Type: application/json');
$id_member = is_login();
if (!$id_member) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = isset($data['action']) ? substr(preg_replace('/[^A-Za-z0-9_:-]/','',$data['action']),0,32) : '';
$videoId = isset($data['video_id']) ? substr(preg_replace('/[^A-Za-z0-9_-]/','',$data['video_id']),0,32) : '';
$articleId = isset($data['article_id']) && is_numeric($data['article_id']) ? (int)$data['article_id'] : 0;
$productId = isset($data['product_id']) && is_numeric($data['product_id']) ? (int)$data['product_id'] : 0;
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (!db_var("SHOW TABLES LIKE 'epi_video_access_log'")) {
    db_query("CREATE TABLE IF NOT EXISTS `epi_video_access_log` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `mem_id` INT NOT NULL,
      `product_id` INT NULL,
      `article_id` INT NULL,
      `video_id` VARCHAR(32) NULL,
      `action` VARCHAR(32) NULL,
      `ip` VARCHAR(45) NULL,
      `user_agent` VARCHAR(255) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}
$safeAction = cek($action);
$safeVideo = cek($videoId);
$safeUa = substr(cek($ua),0,255);
$safeIp = substr(cek($ip),0,45);
db_query("INSERT INTO `epi_video_access_log` (`mem_id`,`product_id`,`article_id`,`video_id`,`action`,`ip`,`user_agent`) VALUES (".(int)$id_member.",".(int)$productId.",".(int)$articleId.",'".$safeVideo."','".$safeAction."','".$safeIp."','".$safeUa."')");
echo json_encode(['ok'=>true]);
