<?php
// includes/functions.php
// បណ្តុំ Helper Functions សម្រាប់ប្រើប្រាស់ទូទាំងប្រព័ន្ធ

// បើក Output Buffering ការពារកំហុស Headers already sent
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ១. អនុគមន៍បញ្ជូនទំព័រ (Redirect) - ធានាថាមិនចេញ Warning ១០០%
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        // Fallback តាម JavaScript បើ Header ចេញរួច
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'></noscript>";
        exit;
    }
}

// ២. អនុគមន៍គ្រប់គ្រង Flash Messages
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function display_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>
                " . htmlspecialchars($flash['message']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// ៣. អនុគមន៍ Escaping ការពារ XSS
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ៤. អនុគមន៍ទម្រង់លុយជាដុល្លារ ($)
function format_money($amount) {
    return '$' . number_format((float)$amount, 2);
}

// ៥. អនុគមន៍ Execute Query ខ្លី និងមានសុវត្ថិភាព
function db_query($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// ៦. អនុគមន៍កត់ត្រាចលនាស្តុក
function record_stock_movement($pdo, $product_id, $type, $reference_id, $qty_change, $balance_after, $note = '') {
    $sql = "INSERT INTO stock_movements (product_id, movement_type, reference_id, quantity_change, balance_after, note)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id, $type, $reference_id, $qty_change, $balance_after, $note]);
}
