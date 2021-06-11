<?php
// Include file cấu hình ban đầu của `Twig`
require_once __DIR__.'/../../bootstrap.php';

// Truy vấn database để lấy danh sách
// 1. Include file cấu hình kết nối đến database, khởi tạo kết nối $conn
include_once(__DIR__.'/../../dbconnect.php');

// 2. Chuẩn bị câu truy vấn $sql
$sqlDanhSachSanPham = <<<EOT
    SELECT sp.sp_ma, sp.product_name, sp.sp_gia, sp.sp_giacu, sp.sp_mota_ngan, sp.sp_quantity, lsp.lproduct_name, MAX(hsp.hproduct_nametaptin) AS hproduct_nametaptin
    FROM `sanpham` sp
    JOIN `loaisanpham` lsp ON sp.lsp_ma = lsp.lsp_ma
    LEFT JOIN `hinhsanpham` hsp ON sp.sp_ma = hsp.sp_ma
    GROUP BY sp.sp_ma, sp.product_name, sp.sp_gia, sp.sp_giacu, sp.sp_mota_ngan, sp.sp_quantity, lsp.lproduct_name
EOT;

// 3. Thực thi câu truy vấn SQL để lấy về dữ liệu
$result = mysqli_query($conn, $sqlDanhSachSanPham);

// 4. Khi thực thi các truy vấn dạng SELECT, dữ liệu lấy về cần phải phân tích để sử dụng
// Thông thường, chúng ta sẽ sử dụng vòng lặp while để duyệt danh sách các dòng dữ liệu được SELECT
// Ta sẽ tạo 1 mảng array để chứa các dữ liệu được trả về
$dataDanhSachSanPham = [];
while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
{
    $dataDanhSachSanPham[] = array(
        'sp_ma' => $row['sp_ma'],
        'product_name' => $row['product_name'],
        'sp_gia' => number_format($row['sp_gia'], 2, ".", ",") . ' vnđ',
        'sp_giacu' => number_format($row['sp_giacu'], 2, ".", ","),
        'sp_mota_ngan' => $row['sp_mota_ngan'],
        'sp_quantity' => $row['sp_quantity'],
        'lproduct_name' => $row['lproduct_name'],
        'hproduct_nametaptin' => $row['hproduct_nametaptin'],
    );
}

// Yêu cầu `Twig` vẽ giao diện được viết trong file `frontend/pages/danhsach.html.twig`
// với dữ liệu truyền vào file giao diện được đặt tên
echo $twig->render('frontend/sanpham/list.html.twig', [
    'danhsachsanpham' => $dataDanhSachSanPham
]);