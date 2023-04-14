<?php
require_once(path('includes/libraries/newstripe/init.php'));

function stripe_process_pager($app) {
    $id = segment(2);
    $price = segment(3);
    $name = input('name');
    $description = input('description');
    $type = segment(4);
    $return_url = input('return_url');
    $cancel_url = input('cancel_url');
    if (input('webview')) {
        if (preg_match("#\?#", $return_url)) {
            $return_url .= "&webview=true&api_userid=".get_userid();
        } else {
            $return_url .= "?webview=true&api_userid=".get_userid();
        }

        if (preg_match("#\?#", $cancel_url)) {
            $cancel_url .= "&webview=true&api_userid=".get_userid();
        } else {
            $cancel_url .= "?webview=true&api_userid=".get_userid();
        }
    }
    $coupon = input('coupon');
    $plan = array('type' => '');
    if($type == 'membership-plan') {
        $membership_plan = get_membership_plan($id);
        $plan = $membership_plan;
    }
    $token = input('stripeToken');

    if(!$token) return redirect_back();
    $options = array(
        'amount' => (int) $price * 100, // this is in cents: $20
        'currency' => config('default-currency'),
        'card' => $token,
        'description' => $description,
        'metadata' => array(
            'name' => $name,
            'item_number' => $id,
            'item_type' => $type,
            'user_id' => get_userid(),
        )
    );
    try {

        \Stripe\Stripe::setApiKey(config('stripe-secret-key'));
        if($plan['type'] == "recurring") {
            $options["interval"] = $plan['expire_type'];
            $options['id'] = $id;
            unset($options['card']);
            unset($options['description']);

            $product = \Stripe\Product::create(array(
                'type' => 'service',
                'name' =>  $type.'-'.$id
            ));

            $plan = \Stripe\Plan::create(array(
                'product' => $product['id'],
                'nickname' => $type.' - Recurring payment',
                'interval' => $plan['expire_type'],
                'currency' => config('default-currency'),
                'amount' => (int) $price * 100,
                'metadata' => array(
                    'name' => $name,
                    'item_number' => $id,
                    'item_type' => $type,
                    'user_id' => get_userid(),
                )
            ));

            $customer = \Stripe\Customer::create(array(
                "email" => get_user_data('email_address'),
                "source" => $token, // The token submitted from Checkout
            ));

            \Stripe\Subscription::create(array(
                "customer" => $customer->id,
                "items" => array(
                    array(
                        "plan" => $plan['id'],
                    ),
                ),
            ));

        } else {
            \Stripe\Charge::create($options);
        }

        //fire_hook("payment.aff", null, array($type, $id));
        if(config('promotion-coupon', 0)) {
            fire_hook('payment.coupon.completed', $coupon);
        }
        return redirect($return_url);

    } catch(\Exception $e) {
        // Declined. Don't process their purchase.
        // Go back, and tell the user to try a new card
        exit($e->getMessage());
        return redirect($cancel_url);
    } catch(\Stripe_RateLimitError $e) {
        $error = "Sorry, we weren't able to authorize your card. You have not been charged.";
    } catch(\Stripe_InvalidRequestError $e) {
        $error = "Sorry, we weren't able to authorize your card. You have not been charged.";
    } catch(\Stripe_AuthenticationError $e) {
        $error = "Sorry, we weren't able to authorize your card. You have not been charged.";
    } catch(\Stripe_ApiConnectionError $e) {
        $error = "Sorry, we weren't able to authorize your card. You have not been charged.";
    } catch(\Stripe_CardError $e) {
        $error = "Sorry, we weren't able to authorize your card. You have not been charged.";
    } catch(Exception $e) {
        $error = "Sorry, we weren't able to authorize your card. You have not been charged.";
    }
}

function stripe_web_hook_pager() {
    try {
        require_once(path('includes/libraries/newstripe/init.php'));
        \Stripe\Stripe::setApiKey(config('stripe-secret-key'));

        $stripe_web_hook = @file_get_contents('php://input');
        $event_json = json_decode($stripe_web_hook);
        $event_id = $event_json->id;
        $event = \Stripe\Event::retrieve($event_id);

        if($event->type == 'invoice.payment_succeeded') {
            $plan = $event->data->object->lines->data[0]->plan;
            $id = $plan->metadata->item_number;
            $type = $plan->metadata->item_type;
            $user_id = $plan->metadata->user_id;
            fire_hook("payment.aff", null, array($type, $id, $user_id));
            email_invoice_stripe_receipt($event->data->object);
        } else {
            $plan = $event->data->object->lines->plan;
            $id = $plan->metadata->item_number;
            $type = $plan->metadata->item_type;
            $user_id = $plan->metadata->user_id;
            fire_hook("payment.aff.failed", null, array($type, $id, $user_id));
        }
    } catch(Exception $e) {

    }
}

function email_invoice_stripe_receipt($invoice) {
    $customer = \Stripe\Customer::retrieve($invoice->customer);

    $headers = 'From:'.config('site-name')."Support".lang("site-email");
    $subject = lang("stripe::mail-subject");
    mail($customer->email, $subject, payment_received_body($invoice, $customer), $headers);
}

function payment_received_body($invoice, $customer) {
    $subscription = $invoice->lines->subscriptions[0];
    $body = lang('Dear').$customer->email.", <br>";
    $body .= "This is a receipt for your subscription. This is only a receipt,<br>";
    $body .= "Thanks for your continued support! <br>";
    $body .= "-------------------------------------------------<br>";
    $body .= "SUBSCRIPTION RECEIPT <br>";
    $body .= "Email: ".$customer->email."<br>";
    $body .= "Plan: ".$subscription->plan->name."<br>";
    $body .= "Amount: ".format_stripe_amount($invoice->total).lang('default-currency')."<br>";
    $body .= "For service between".format_stripe_timestamp($subscription->period->start)." and ".format_stripe_timestamp($subscription->period->end);
    echo $body;
}

function format_stripe_amount($amount) {
    return sprintf('$%0.2f', $amount / 100.0);
}

function format_stripe_timestamp($timestamp) {
    return strftime("%m/%d/%Y", $timestamp);
}