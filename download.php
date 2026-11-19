<?php
// 接收前端传来的文件名
$filename = isset($_GET['file']) ? $_GET['file'] : '';

// 安全校验：防止目录穿越攻击 (很重要！)
if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    die("非法请求");
}

// 拼接待下载的真实路径
$filepath = __DIR__ . '/downloads/' . $filename;

// 检查文件是否存在
if (!file_exists($filepath)) {
    die("文件不存在");
}

// 强制浏览器触发下载的关键 HTTP 头
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream'); // 告诉浏览器这是个未知二进制流，必须下载
header('Content-Disposition: attachment; filename="' . basename($filename) . '"'); // attachment 就是“作为附件下载”的意思
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// 清除输出缓冲，读取文件并输出给用户
ob_clean();
flush();
readfile($filepath);
exit;
?>