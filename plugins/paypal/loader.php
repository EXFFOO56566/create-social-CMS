<?php

register_pager("paypal/payment/verification", array('use' => "paypal::paypal@paypal_payment_verification_pager", 'as' => 'paypal-verification'));
register_pager("paypal/payment", array('use' => "paypal::paypal@paypal_payment_pager", 'filter' => 'user-auth', 'as' => 'paypal-process'));
if(config('enable-paypal', false)) {
	register_hook('payment.buttons.extend', function($type, $id, $name, $description, $price, $return_url, $cancel_url, $coupon = null) {
		echo '  <li>'.view("paypal::button", array('id' => $id, 'price' => $price, 'name' => $name, 'description' => $description, 'type' => $type, 'return_url' => $return_url, 'cancel_url' => $cancel_url, 'coupon' => $coupon)).'</li>';
	});
}
register_hook("membership.segment.allowed", function($allowed_segments) {
	$allowed_segments[] = 'paypal';
	return $allowed_segments;
});