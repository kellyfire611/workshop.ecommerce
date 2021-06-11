<?php
// Hiển thị tất cả lỗi trong PHP
// Chỉ nên hiển thị lỗi khi đang trong môi trường Phát triển (Development)
// Không nên hiển thị lỗi trên môi trường Triển khai (Production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// error_reporting(E_ALL);

// Load các thư viện (packages) do Composer quản lý vào chương trình
require_once __DIR__.'/vendor/autoload.php';

// Load các dữ liệu cứng (StaticData) sử dụng cho toàn chương trình
require_once __DIR__.'/StaticData.php';
require_once __DIR__.'/config.php';

// Load thư viện Phân trang
include_once(__DIR__.'/Paginator.php');

// Start session
session_start();

// Chỉ định thư mục `templates` (nơi Twig sẽ biên dịch cú pháp Twig thành các đoạn code PHP)
$loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/templates');

// Khởi tạo Twig
$twig = new \Twig\Environment($loader, [
    //'cache' => __DIR__.'/templates/compilation_cache',
    'debug' => true,
    'auto_reload' => true
]);

// Tạo biến global để có thể sử dụng trong tất cả các view được render bởi TWIG
$twig->addGlobal('session', $_SESSION);
$twig->addGlobal('root_url', Config::$root_url);
