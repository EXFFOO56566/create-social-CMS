<?php

register_pager("stripe/payment/{id}/{price}/{type}", array('use' => "stripe::stripe@stripe_process_pager", 'filter' => 'user-auth', 'as' => 'stripe-process'))->where(array('id' => '[0-9]+', 'price' => '[0-9]+', 'name' => '[a-zA-Z0-9\_\-]+', 'description' => '[a-zA-Z0-9\_\-]+', 'type' => '[a-zA-Z0-9\_\-]+', 'return_url' => '[a-zA-Z0-9\_\-]+', 'cancel_url' => '[a-zA-Z0-9\_\-]+', 'coupon' => '[a-zA-Z0-9\_\-]+'));
register_pager("stripe/payment/verification", array('use' => "stripe::stripe@stripe_web_hook_pager", 'as' => 'stripe-verification'));
if(config('enable-stripe', false)) {
	register_hook('payment.buttons.extend', function($type, $id, $name, $description, $price, $return_url, $cancel_url, $coupon = null) {
		echo '  <li>'.view("stripe::button", array('id' => $id, 'price' => $price, 'name' => $name, 'description' => $description, 'type' => $type, 'return_url' => $return_url, 'cancel_url' => $cancel_url, 'coupon' => $coupon)).'</li>';
	});
}

register_hook("membership.segment.allowed", function($allowed_segments) {
    $allowed_segments[] = 'stripe';
    return $allowed_segments;
});

