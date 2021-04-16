<?php
// Include file cấu hình ban đầu của `Twig`
require_once __DIR__.'/../../bootstrap.php';
// Truy vấn database để lấy danh sách
// 1. Include file cấu hình kết nối đến database, khởi tạo kết nối $conn
include_once(__DIR__.'/../../dbconnect.php');
include_once(__DIR__ . '/../../Paginator.php');

// 2. Chuẩn bị câu truy vấn $sql
// Sử dụng HEREDOC của PHP để tạo câu truy vấn SQL với dạng dễ đọc, thân thiện với việc bảo trì code
$sql = <<<EOT
    SELECT sp.*
        , lsp.lproduct_name
        , nsx.nsx_ten
        , km.km_ten, km.km_noidung, km.km_tungay, km.km_denngay
    FROM `sanpham` sp
    JOIN `loaisanpham` lsp ON sp.lsp_ma = lsp.lsp_ma
    JOIN `nhasanxuat` nsx ON sp.nsx_ma = nsx.nsx_ma
    LEFT JOIN `khuyenmai` km ON sp.km_ma = km.km_ma
    ORDER BY sp.sp_ma DESC
EOT;

// 3. Thực thi câu truy vấn SQL để lấy về dữ liệu
$limit      = (isset($_GET['limit'])) ? $_GET['limit'] : 5;
$page       = (isset($_GET['page'])) ? $_GET['page'] : 1;
$paginator  = new Paginator($twig, $conn, $sql);
$data       = $paginator->getData($limit, $page);
$data = $data->data;
//dd($data);

//$result = mysqli_query($conn, $sql);
// 4. Khi thực thi các truy vấn dạng SELECT, dữ liệu lấy về cần phải phân tích để sử dụng
// Thông thường, chúng ta sẽ sử dụng vòng lặp while để duyệt danh sách các dòng dữ liệu được SELECT
// Ta sẽ tạo 1 mảng array để chứa các dữ liệu được trả về
// $data = [];
// while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
$formatedData = [];
foreach($data as $row)
{
    $km_tomtat = '';
    if(!empty($row['km_ten'])) {
        // Sử dụng hàm sprintf() để chuẩn bị mẫu câu với các giá trị truyền vào tương ứng từng vị trí placeholder
        $km_tomtat = sprintf("Khuyến mãi %s, nội dung: %s, thời gian: %s-%s", 
            $row['km_ten'],
            $row['km_noidung'],
            // Sử dụng hàm date($format, $timestamp) để chuyển đổi ngày thành định dạng Việt Nam (ngày/tháng/năm)
            // Do hàm date() nhận vào là đối tượng thời gian, chúng ta cần sử dụng hàm strtotime() để chuyển đổi từ chuỗi có định dạng 'yyyy-mm-dd' trong MYSQL thành đối tượng ngày tháng
            date('d/m/Y', strtotime($row['km_tungay'])),    //vd: '2019-04-25'
            date('d/m/Y', strtotime($row['km_denngay'])));  //vd: '2019-05-10'
    }
    $formatedData[] = array(
        'sp_ma' => $row['sp_ma'],
        'product_name' => $row['product_name'],
        // Sử dụng hàm number_format(số tiền, số lẻ thập phân, dấu phân cách số lẻ, dấu phân cách hàng nghìn) để định dạng số khi hiển thị trên giao diện. Vd: 15800000 -> format thành 15,800,000.66 vnđ
        'sp_gia' => number_format($row['sp_gia'], 2, ".", ",") . ' vnđ',
        'sp_giacu' => number_format($row['sp_giacu'], 2, ".", ",") . ' vnđ',
        'sp_mota_ngan' => $row['sp_mota_ngan'],
        'sp_mota_chitiet' => $row['sp_mota_chitiet'],
        'sp_ngaycapnhat' => date('d/m/Y H:i:s', strtotime($row['sp_ngaycapnhat'])),
        'sp_quantity' => number_format($row['sp_quantity'], 0, ".", ","),
        'lsp_ma' => $row['lsp_ma'],
        'nsx_ma' => $row['nsx_ma'],
        'km_ma' => $row['km_ma'],
        // Các cột dữ liệu lấy từ liên kết khóa ngoại
        'lproduct_name' => $row['lproduct_name'],
        'nsx_ten' => $row['nsx_ten'],
        'km_tomtat' => $km_tomtat,
    );
}
// dd($formatedData);
// Yêu cầu `Twig` vẽ giao diện được viết trong file `backend/sanpham/index.html.twig`
// với dữ liệu truyền vào file giao diện được đặt tên là `ds_sanpham`
echo $twig->render('backend/sanpham/index.html.twig', [
    'ds_sanpham' => $formatedData,
    'paginator' => $paginator
] );