<?php
// Include file cấu hình ban đầu của `Twig`
require_once __DIR__ . '/../../bootstrap.php';

// Truy vấn database để lấy danh sách
// 1. Include file cấu hình kết nối đến database, khởi tạo kết nối $conn
include_once(__DIR__ . '/../../dbconnect.php');

// Lấy thông tin khách hàng
// Lấy dữ liệu người dùng đã đăng nhập từ SESSION
$email = $_SESSION['frontend']['email'];

// Câu lệnh SELECT
$sqlSelect = <<<EOT
    SELECT *
    FROM shop_customers
    WHERE email = '$email'
    LIMIT 1;
EOT;

// Thực thi SELECT
$result = mysqli_query($conn, $sqlSelect);
$dataCustomer;
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $dataCustomer = array(
        'id' => $row['id'],
        'username' => $row['username'],
        'last_name' => $row['last_name'],
        'first_name' => $row['first_name'],
        'email' => $row['email'],
        'avatar' => $row['avatar'],
        'billing_address' => $row['billing_address'],
        'shipping_address' => $row['shipping_address'],
        'phone' => $row['phone'],
        'avatar' => $row['avatar'],
        // Sử dụng hàm date($format, $timestamp) để chuyển đổi ngày thành định dạng Việt Nam (ngày/tháng/năm)
        // Do hàm date() nhận vào là đối tượng thời gian, chúng ta cần sử dụng hàm strtotime() để chuyển đổi từ chuỗi có định dạng 'yyyy-mm-dd' trong MYSQL thành đối tượng ngày tháng
        'birthday' => date('d/m/Y', strtotime($row['birthday'])),
        'code' => $row['code'],
        'created_at_formatted' => date('d/m/Y H:i:s', strtotime($row['created_at'])),
    );
}

if (!isset($_POST['btnDatHang'])) {
    // Nếu trong SESSION có giá trị của key 'email' <-> người dùng đã đăng nhập thành công
    // Nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
    if (!isset($_SESSION['frontend']['email'])) {
        header('location:../auth/login.php');
        return;
    }

    // Hiển thị trang thanh toán
    // Lấy thông tin Hình thức thanh toán
    // Câu lệnh SELECT
    $sqlSelectHinhThucThanhToan = <<<EOT
        SELECT * 
        FROM shop_payment_types
    EOT;

    // Thực thi SELECT
    $resultSelectHinhThucThanhToan = mysqli_query($conn, $sqlSelectHinhThucThanhToan);
    $dataPaymentTypes = [];
    while ($row = mysqli_fetch_array($resultSelectHinhThucThanhToan, MYSQLI_ASSOC)) {
        $dataPaymentTypes[] = array(
            'id' => $row['id'],
            'payment_code' => $row['payment_code'],
            'payment_name' => $row['payment_name'],
            'description' => $row['description'],
            'image' => $row['image'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        );
    }

    // Kiểm tra dữ liệu trong session
    $data = [];
    if (isset($_SESSION['cartdata'])) {
        $data = $_SESSION['cartdata'];
    } else {
        $data = [];
    }

    if(empty($data)) {
        echo '<h1>Giỏ hàng trống. Vui lòng chọn sản phẩm muốn mua trước khi tiến hành Thanh toán</h1>';
        echo '<a href="/">Click vào đây để Quay về trang chủ</a>';
        return;
    }
    else {
        // Yêu cầu `Twig` vẽ giao diện được viết trong file `frontend/checkout/onepage-checkout.html.twig`
        // với dữ liệu truyền vào file giao diện được đặt tên là `cartdata`
        echo $twig->render('frontend/checkout/onepage-checkout.html.twig', [
            'cartdata' => $data,
            'payment_types' => $dataPaymentTypes,
            'customer' => $dataCustomer
        ]);
        return;
    }
}

// Lưu đơn hàng
// dd($_POST);
// dd($dataCustomer['id']);
// Lấy dữ liệu từ POST
$employee_id = 1; // mặc định xử lý là Admin (for DEMO only)
$customer_id = $dataCustomer['id'];
$order_date = date('Y-m-d H:m:s'); // lấy ngày hiện tại
$shipped_date = 'NULL';
$ship_name = 'Nguyễn Văn Shipper';
$ship_address1 = $_POST['billing_address'];
$ship_address2 = $_POST['shipping_address'];
$ship_city = '';
$ship_state = '';
$ship_postal_code = '94000';
$ship_country = 'Việt Nam';
$shipping_fee = 0;
$payment_type_id = $_POST['payment_type_id'];
$paid_date = date('Y-m-d H:m:s'); // lấy ngày hiện tại
$order_status = 'New';
$created_at = date('Y-m-d H:m:s'); // lấy ngày hiện tại

// Insert Đơn hàng
// Câu lệnh INSERT
$sqlDonHang = "INSERT INTO `shop_orders`(`employee_id`, `customer_id`, `order_date`, `shipped_date`, `ship_name`, `ship_address1`, `ship_address2`, `ship_city`, `ship_state`, `ship_postal_code`, `ship_country`, `shipping_fee`, `payment_type_id`, `paid_date`, `order_status`, `created_at`) VALUES ($employee_id, $customer_id, '$order_date', $shipped_date, '$ship_name', '$ship_address1', '$ship_address2', '$ship_city', '$ship_state', '$ship_postal_code', '$ship_country', $shipping_fee, $payment_type_id, '$paid_date', '$order_status', '$created_at');";
// dd($sqlDonHang);

// Thực thi INSERT
mysqli_query($conn, $sqlDonHang) or die("<b>Có lỗi khi thực thi câu lệnh SQL: </b>" . mysqli_error($conn) . "<br /><b>Câu lệnh vừa thực thi:</b></br>$sql");

// Lấy ID đơn hàng vừa được lưu
$last_donhang_id = mysqli_insert_id($conn);
// dd($last_donhang_id);

// Duyệt vòng lặp sản phẩm trong giỏ hàng để thực thi câu lệnh INSERT vào table `sanpham_donhang`
$subtotal = 0;
foreach ($_POST['sanphamgiohang'] as $sanpham) {
    $order_id = $last_donhang_id;
    $product_id = $sanpham['id'];
    $quantity = $sanpham['quantity'];
    $unit_price = $sanpham['list_price_after_discount'];
    $discount_percentage = 0;
    $discount_amout = 0;
    $order_detail_status = 'Allocated';
    $date_allocated = 'NULL';
    $subtotal += ($quantity * $unit_price); // Tính tổng tiền phải trả

    // Insert Sản phẩm Đơn hàng
    // Câu lệnh INSERT
    $sqlSanPhamDonHang = "INSERT INTO `shop_order_details`(`order_id`, `product_id`, `quantity`, `unit_price`, `discount_percentage`, `discount_amout`, `order_detail_status`, `date_allocated`) VALUES ($order_id, $product_id, $quantity, $unit_price, $discount_percentage, $discount_amout, '$order_detail_status', $date_allocated)";
    // dd($sqlSanPhamDonHang);

    // Thực thi INSERT
    mysqli_query($conn, $sqlSanPhamDonHang) or die("<b>Có lỗi khi thực thi câu lệnh SQL: </b>" . mysqli_error($conn) . "<br /><b>Câu lệnh vừa thực thi:</b></br>$sql");
}

// Thanh toán thành công, xóa Giỏ hàng trong SESSION
// lưu dữ liệu giỏ hàng vào session
$_SESSION['cartdata'] = [];

echo $twig->render('frontend/checkout/onepage-checkout-finish.html.twig');
