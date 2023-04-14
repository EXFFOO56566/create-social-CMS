<?php
function tag_suggestion_pager($app) {
	CSRFProtection::validate(false);
	$term = input('term');
	$friend = input('friend', false);
	$users = search_users($term, 5, $friend);
	return view("user/tag-suggestion", array('users' => $users));
}

function saved_pager($app) {
	$app->setTitle(lang('saved'));

	$type = segment(1, 'posts');
	Pager::setCurrentPage('saved');
	add_menu('saved', array('title' => lang('posts').' <span style="color:lightgrey;font-size:12px">'.count(get_user_saved('feed')).'</span>', 'link' => url('saved'), 'id' => 'posts'));

	$content = '<span></span>';

	if($type == 'posts') {
		$content = view('saved/posts', array('feeds' => get_feeds('saved')));
	}

	$content = fire_hook('saved.content', $content, array($type));
	get_menu('saved', $type)->setActive();
	return $app->render(view('saved/layout', array('content' => $content, 'type' => $type)));
}

function save_pager($app) {
	CSRFProtection::validate(false);
	$type = input('type');
	$type_id = input('id');
	$status = input('status');

	if($status == 1) {
		remove_user_saving($type, $type_id);
		return json_encode(array(
			'status' => 0,
			'text' => lang('save-post'),
			'message' => lang('successfully-unsaved')
		));
	} else {
		add_user_saving($type, $type_id);
		return json_encode(array(
			'status' => 1,
			'text' => lang('unsave-post'),
			'message' => lang('successfully-saved')
		));
	}
}

function save_design_pager($app) {
	$val = input('val');
	if(!$val) redirect_back();
	$expected = array(
		'position' => '',
		'color' => '',
		'link' => '',
		'repeat' => ''
	);
	$val = array_merge($expected, $val);
	/**
	 * @var $url
	 * @var $type
	 * @var $type_id
	 */
	extract($val);
	if(preg_match('#_=#', $url)) {
		list($url, $hash) = explode('_=', $url);
	}
	if(!config('design-profile', true)) redirect($url);
	if($type == 'user') {
		if($type_id != get_userid()) redirect($url);
	}
	foreach($val as $key => $value) {
		if(strpos($value, ';') !== false || strpos($value, '}') !== false || strpos($value, '{') !== false || strpos($value, '@') !== false) {
			return redirect($url);
		}
	}
	$file = input_file('image');
	if($file) {
		$uploader = new Uploader($file, 'file');
		if($uploader->passed()) {
			$uploader->setPath("design/background/");
			$image = $uploader->uploadFile()->result();
			$val['image'] = url_img($image);
		}
	}

	if($type == 'user') {
		$details = serialize($val);
		$userid = get_userid();
		db()->query("UPDATE users SET design_details='{$details}' WHERE id='{$userid}'");
	} else {
		fire_hook('design.save', null, array($type, $type_id, $val));
	}

	redirect($url);
}

function verify_request_pager($app) {
	$app->setTitle(lang('submit-answer'));
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		submit_answer($val);
	}
	redirect_back();
}

function people_pager($app) {
	$app->setTitle(lang("people"));
	$val = input('val');
	if($val) {
		header('location: '.url_to_pager('people').'?'.http_build_query($val));
	}
	$filter = $_GET;
	$limit = config('max-page-result', 20);
	$appends = $_GET;
	unset($appends['page']);
	$people = people_get_users($filter, $limit)->append($appends);
	$message = null;
	return $app->render(view('user/people', array('people' => $people, 'filter' => $filter, 'message' => $message)));
}
function choose_plan_pager($app) {
    $app->setLayout("layouts/blank")->setTitle(lang('choose-plan'))->onHeaderContent = false;

    return $app->render(view("user/membership/choose-plan"));
}

function payment_pager($app) {
    $id = input('plan');
    $plan = get_membership_plan($id);
    if(!$plan) redirect(url("membership/choose-plan"));

    if($plan['type'] == 'free') {
        $userid = get_userid();
        $role = $plan['user_role'];

        db()->query("UPDATE users SET membership_type='free',membership_plan='{$id}',role='{$role}' WHERE id='{$userid}'");
        redirect(go_to_user_home(null));
    } else {
        $plan = fire_hook('membership.payment.pass', $plan);
    }
    $app->setLayout("layouts/blank")->setTitle(lang('select-payment-method'))->onHeaderContent = false;

    return $app->render(view("user/membership/payment", array("plan" => $plan)));
}

function membership_payment_success_pager($app) {
    CSRFProtection::validate();
    $app->setTitle(lang('membership-purchase'));
    $id = input('id');

    //membership_activate($id);
    return $app->render(view('user/membership/payment/success'));
}

function membership_payment_cancel_pager($app) {
    $app->setTitle(lang('membership-canceled'));
    return $app->render(view('user/membership/payment/cancel'));
}

function payment_coupon_pager() {
    $url = input('link');
    $new_url = "";
    $result = array('status' => 0, 'url' => '');
    $coupon = input('coupon');
    $couponStatus = coupon::get_coupon($coupon);
    if($couponStatus) {
        if($couponStatus['status'] != 0 AND $couponStatus['no_of_use'] != 0) {
            if(strpos($url, '?') !== false) {
                $getUrl = explode("&coupon", $url);
                $new_url = $getUrl['0']."&coupon=".$coupon;
            } elseif(strpos($url, '?') == false) {
                $getUrl = explode("?coupon", $url);
                $new_url = $getUrl['0']."?coupon=".$coupon;
            }
            $result['status'] = 1;
            $result['url'] = $new_url;
            return json_encode($result);
        }
    }
    return json_encode($result);
}