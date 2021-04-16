<?php
// Include file cấu hình ban đầu của `Twig`
require_once __DIR__ . '/../../bootstrap.php';

// Truy vấn database để lấy danh sách
// 1. Include file cấu hình kết nối đến database, khởi tạo kết nối $conn
include_once(__DIR__ . '/../../dbconnect.php');

// ************* Tích hợp cổng thông tin STRIPE thanh toán **************
// 2. Set khóa bí mật (secret key) để gởi yêu cầu Thanh toán đến Cổng thanh toán Stripe
// See your keys here: https://dashboard.stripe.com/apikeys
\Stripe\Stripe::setApiKey('sk_test_51IgWCYG9O8Lzt9jNiH4XoJRQak3UnduavjtfhKzPOZxQCwdprwKWsEm4yDwRP28UBzDPkFWqcifs6QcGTn2nXdrG00Ndyoew2h');

// 3. Gởi yêu cầu (request) đến Stripe
$stripeData = [
  'payment_method_types' => ['card'],
  'line_items' => [],
  'mode' => 'payment',
  'success_url' => 'http://workshop.ecommerce.nentang.vn/frontend/checkout/onepage-checkout-finish',
  'cancel_url' => 'http://workshop.ecommerce.nentang.vn/frontend/checkout/onepage-checkout-cancel',
  ];

foreach($_POST['sanphamgiohang'] as $sp) {
  $stripeData['line_items'][] = [
    'price_data' => [
      'currency' => 'vnd',
      'unit_amount' => round($sp['list_price_after_discount'], 0),
      'product_data' => [
        'name' => $sp['product_name'],
      ],
    ],
    'quantity' => round($sp['quantity']),
  ];
}

$session = \Stripe\Checkout\Session::create($stripeData);
echo json_encode([ 'id' => $session->id ]);
?>