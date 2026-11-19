<?php
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// 1. 基础配置：真实账号密码放在同目录 config.php（不入版本库）
$config_file = __DIR__ . '/config.php';
if (is_file($config_file)) {
    require $config_file;   // 定义 $db_host / $db_user / $db_pass / $db_name / $ADMIN_TOKEN
}
// 兜底占位值，避免本地缺 config.php 时直接白屏
$db_host     = isset($db_host)     ? $db_host     : '127.0.0.1';
$db_user     = isset($db_user)     ? $db_user     : 'DB_USER';
$db_pass     = isset($db_pass)     ? $db_pass     : 'DB_PASSWORD';
$db_name     = isset($db_name)     ? $db_name     : 'DB_NAME';
$ADMIN_TOKEN = isset($ADMIN_TOKEN) ? $ADMIN_TOKEN : 'CHANGE_ME_ADMIN_TOKEN';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => '数据库连不上']));
}
$conn->set_charset("utf8mb4");

$action = isset($_GET['action']) ? $_GET['action'] : '';
$ip = $_SERVER['REMOTE_ADDR'];
$client_token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
$is_admin = ($client_token === $ADMIN_TOKEN);

// --- 动作 1：获取留言（关键：这里必须 SELECT is_top） ---
if ($action === 'get') {
    $sql = "SELECT id, user_name as name, message_content as content, post_time as time, visitor_ip, is_top 
            FROM user_messages 
            ORDER BY is_top DESC, post_time DESC";
    $result = $conn->query($sql);
    $comments = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $row['can_manage'] = ($is_admin || $row['visitor_ip'] === $ip);
            $row['is_admin'] = $is_admin;
            unset($row['visitor_ip']); // 隐藏IP
            $comments[] = $row;
        }
    }
    echo json_encode($comments);
} 

// --- 动作 2：添加留言 ---
elseif ($action === 'add') {
    $name = trim($_POST['name']);
    $content = trim($_POST['content']);
    $today = date('Y-m-d');
    
    $check = $conn->query("SELECT id FROM user_messages WHERE visitor_ip = '$ip' AND DATE(post_time) = '$today'");
    if (!$is_admin && $check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => '每天只能留一条哦']);
    } else {
        $sql = "INSERT INTO user_messages (user_name, message_content, visitor_ip) VALUES ('$name', '$content', '$ip')";
        echo json_encode(['success' => $conn->query($sql)]);
    }
}

// --- 动作 3：删除留言 ---
elseif ($action === 'delete') {
    $id = intval($_POST['id']);
    if ($is_admin) {
        $sql = "DELETE FROM user_messages WHERE id = $id";
    } else {
        $sql = "DELETE FROM user_messages WHERE id = $id AND visitor_ip = '$ip'";
    }
    echo json_encode(['success' => $conn->query($sql)]);
}

// --- 动作 4：切换置顶 ---
elseif ($action === 'toggle_top') {
    if (!$is_admin) die(json_encode(['success' => false, 'message' => '你不是管理员！']));
    $id = intval($_POST['id']);
    // 使用 IFNULL 防止数据库里本来是 NULL 的情况
    $sql = "UPDATE user_messages SET is_top = 1 - IFNULL(is_top, 0) WHERE id = $id";
    echo json_encode(['success' => $conn->query($sql)]);
}

$conn->close();
?>