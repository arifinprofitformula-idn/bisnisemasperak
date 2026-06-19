<?php
/**
 * EpichubSecurity Class
 * Handles security checks and logging for Epic Hub features.
 */

class EpichubSecurity {
    
    private $logFile;
    private $requiredProductIds = []; // Default empty, meaning no specific product required unless set

    public function __construct() {
        // Define log file path
        $this->logFile = __DIR__ . '/../logs/audit_access.log';
    }

    /**
     * Set required product IDs for access.
     * @param array $productIds List of product IDs (integers).
     */
    public function setRequiredProducts(array $productIds) {
        $this->requiredProductIds = $productIds;
    }

    /**
     * Validate if the user has access to Epic Hub AI Content.
     * 
     * @param array $member The member data array.
     * @return bool True if access is granted.
     * @throws Exception If access is denied with a specific reason.
     */
    public function validateAccess($member) {
        // 1. Check if member data is valid
        if (empty($member) || !isset($member['mem_id'])) {
            throw new Exception("Unauthorized: User not logged in.");
        }

        // 2. Check membership status (Active = 1)
        // mem_status: 0=Pending, 1=Active, 2=Suspended, etc.
        if (!isset($member['mem_status']) || (string)$member['mem_status'] === '0') {
            $this->logAccess($member, 'DENIED_INACTIVE_STATUS');
            throw new Exception("Access Denied: Your membership is not active.");
        }

        // 3. Check specific role/permission if needed
        // For now, we assume all active members (role >= 1) can access, 
        // unless restricted by epi-role-manager (which is handled in openpage usually).
        // But we add a double check here.
        if (isset($member['mem_role']) && (int)$member['mem_role'] < 1) {
             $this->logAccess($member, 'DENIED_INVALID_ROLE');
             throw new Exception("Access Denied: Insufficient role permissions.");
        }

        // BYPASS: If Super Admin or Admin Staff (role >= 5), skip product check
        // Assuming role 5 is Admin Staff and 9 is Super Admin based on epi-role-manager conventions
        if (isset($member['mem_role']) && (int)$member['mem_role'] >= 5) {
             $this->logAccess($member, 'GRANTED_ADMIN_BYPASS');
             return true;
        }

        // 4. Check Product Ownership (If requirement exists)
        if (!empty($this->requiredProductIds)) {
            $hasProduct = $this->checkProductOwnership($member['mem_id'], $this->requiredProductIds);
            if (!$hasProduct) {
                $this->logAccess($member, 'DENIED_NO_PRODUCT');
                throw new Exception("Access Denied: You have not purchased the required product.");
            }
        }

        // If all checks pass:
        $this->logAccess($member, 'GRANTED');
        return true;
    }

    /**
     * Check if member has purchased any of the required products.
     */
    private function checkProductOwnership($memberId, $productIds) {
        // Ensure $productIds is safe for SQL
        $ids = array_map('intval', $productIds);
        $idsStr = implode(',', $ids);
        
        // Check in sa_order for valid orders (status = 1)
        $sql = "SELECT COUNT(*) FROM `sa_order` 
                WHERE `order_idmember` = " . (int)$memberId . " 
                AND `order_idproduk` IN (" . $idsStr . ") 
                AND `order_status` = 1";
        
        $count = (int)db_var($sql);
        return $count > 0;
    }

    /**
     * Log access attempts for audit trails.
     * 
     * @param array $member
     * @param string $status
     */
    public function logAccess($member, $status) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getRealIp();
        $memId = $member['mem_id'] ?? 'GUEST';
        $memName = $member['mem_nama'] ?? 'Unknown';
        
        $logEntry = sprintf(
            "[%s] IP: %s | User: %s (%s) | Action: ACCESS_EPICHUB_AI | Status: %s\n",
            $timestamp,
            $ip,
            $memId,
            $memName,
            $status
        );

        // Ensure logs directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function getRealIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}
