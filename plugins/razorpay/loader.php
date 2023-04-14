<?php

use Razorpay\Api\Api;

register_asset('razorpay::css/razorpay.css');

register_pager('razorpay/payment/gateway', array(
		'filter' => 'admin-auth',
		'as' => 'razor-payment',
		'use' => 'razorpay::razorpay@razorpay_pager')
);

register_pager('razorpay/payment', array(
		'filter' => 'auth',
		'as' => 'razor-success',
		'use' => 'razorpay::razorpay@razor_payment_pager')
);

if(config('default-currency') == 'INR' && config('enable-razorpay')) {
	register_hook('payment.buttons.extend', function($type, $id, $name, $description, $price, $return_url, $cancel_url, $coupon = null) {
		require_once(path('plugins/razorpay/includes/razorpay/Razorpay.php'));
		$key_id = config('razor-api-key');
		$key_secret = config('razor-secret-key');
		$display_currency = config('default-currency');
		$api = new Api($key_id, $key_secret);
		$order_data = array(
			'receipt' => get_user_data('id'),
			'amount' => $price * 100,
			'currency' => config('default-currency'),
			'payment_capture' => 1
		);
		$razorpay_order = $api->order->create($order_data);
		$razorpay_order_id = $razorpay_order['id'];
		$_SESSION['razorpay_order_id'] = $razorpay_order_id;
		$amount = $order_data['amount'];
		$data = array(
			'key' => $key_id,
			'amount' => $amount,
			'name' => get_user_data('first_name'),
			'description' => $description,
			'image' => get_avatar(75),
			'prefill' => array(
				'name' => get_user_data('first_name'),
				'email' => get_user_data('email_address'),
				'contact' => get_user_data('email_address')
			),
			'notes' => array(
				'address' => config('razor-merchant-address'),
				'merchant_order_id' => $type.'-'.$id.'-'.get_userid().'-'.trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name)), '-'),
			),
			'theme' => array(
				'color' => '#F37254'
			),
			'order_id' => $razorpay_order_id,
			'return_url' => $return_url,
			'cancel_url' => $cancel_url,
		);

		if($display_currency !== 'INR') {
			$data['display_currency'] = $display_currency;
			$amount /= 100;
			//$url = 'http://www.floatrates.com/daily/inr.json';
			//$exchange = json_decode(file_get_contents($url), true);
			//$amount = $amount / $exchange[strtolower($display_currency)]['rate'];
			//$amount = (number_format((float) $amount, 2, '.', '')) * 100;
			$amount = (int) $amount;
			$data['display_amount'] = $amount;
		}
		if(config('promotion-coupon', 0)) {
			fire_hook('payment.coupon.completed', $coupon);
		}
		//$json = json_encode($data);
		echo '<li>'.view("razorpay::button", array('data' => $data, 'display_currency' => $display_currency)).'</li>';
	});
}