<?php
load_functions("ads::ads");


register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {


	}
	register_asset("ads::js/ads.js");
	//   register_asset("ads::js/adstype.js");
	register_asset("ads::css/ads.css");
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => 'Ads Permissions',
		'description' => '',
		'roles' => array(
			'can-create-ads' => array('title' => lang('ads::can-create-ads'), 'value' => 1),
		)
	);
	return $roles;
});


register_hook('system.started', function($app) {
	//  if ($app->themeType == 'frontend' or $app->themeType == 'mobile') {


	//  }
	register_asset("ads::js/ads.js");
	register_asset("ads::js/adstype.js");
	register_asset("ads::css/ads.css");
});

//fire_hook('survey.taken', null, array($owner_id, $survey_id));


register_hook("admin-started", function() {
	add_menu("admin-menu", array("id" => "ads-manager", "title" => lang("ads::ads-manager"), "link" => "#", "icon" => "ion-social-usd-outline"));
	get_menu("admin-menu", "monetization")->addMenu(lang("ads::ads-manager"), '#', 'ads-manager');
	get_menu("admin-menu", "monetization")->findMenu("ads-manager")->addMenu(lang("ads::ads"), url_to_pager('admin-ads-list'), 'ads');
	get_menu("admin-menu", "monetization")->findMenu("ads-manager")->addMenu(lang("ads::create-ads"), url_to_pager('admin-ads-create'), "create");
	get_menu("admin-menu", "monetization")->findMenu("ads-manager")->addMenu(lang("ads::ads-plan"), url_to_pager('admin-ads-plan'), "plans");
	get_menu("admin-menu", "monetization")->findMenu("ads-manager")->addMenu(lang("settings"), url('admincp/plugin/settings/ads'), "settings");

	get_menu('admin-menu', 'monetization')->addMenu(lang('ads::ads-service'), url_to_pager('admin-ads-service-list'), 'ads-service');

	//quick links
	add_menu("admincp-quick-link", array('id' => 'create-ads-plan', 'title' => lang('ads::create-ads-plan'), 'link' => url_to_pager('admin-ads-plan')));

	register_hook('admin.statistics', function($stats) {
		$stats['ads'] = array(
			'count' => count_total_ads(),
			'title' => lang('ads::ads'),
			'icon' => 'ion-social-usd-outline',
			'link' => url_to_pager('admin-ads-list'),
		);
		return $stats;
	});

	register_hook('admin.statistics', function($stats) {
		$stats['running-ads'] = array(
			'count' => count_total_running_ads(),
			'title' => lang('ads::running-ads'),
			'icon' => 'ion-social-usd-outline',
			'link' => url_to_pager('admin-ads-list'),
		);
		return $stats;
	});
	register_hook('admin.charts', function($result, $months, $year) {
		$c = array(
			'name' => lang('ads::ads'),
			'points' => array()
		);


		foreach($months as $name => $n) {
			$c['points'][$name] = count_ads_in_month($n, $year);

		}

		$result['charts']['members'][] = $c;


		return $result;
	});
});


register_pager("admincp/ads/", array('use' => "ads::admincp@ads_lists_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-list'));
register_pager("admincp/ads/create", array('use' => "ads::admincp@ads_create_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-create'));
register_pager("admincp/ads/process/create", array('use' => "ads::admincp@ads_process_create_pager", 'filter' => 'admin-auth'));
register_pager("admincp/ads/process/save", array('use' => "ads::admincp@ads_process_save_pager", 'filter' => 'admin-auth'));
register_pager("admincp/ads/edit/{id}", array('use' => "ads::admincp@edit_ads_pager", 'filter' => 'admin-auth', 'as' => 'admincp-ads-edit'))->where(array('id' => '[0-9]+'));

register_pager("admincp/ads/plans", array('use' => "ads::admincp@plans_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-plan'));
register_pager("admincp/ads/manage/plan", array('use' => "ads::admincp@manage_plans_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-manage-plan'));
register_pager("admincp/ads/add/plan", array('use' => "ads::admincp@add_plan_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-add-plan'));
register_pager("admincp/ads/action/batch", array('use' => "ads::admincp@ads_action_batch_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-batch-action'));
register_pager("admincp/ads/plan/action/batch", array('use' => "ads::admincp@ads_plan_action_batch_pager", 'filter' => 'admin-auth', 'as' => 'admin-ads-plan-batch-action'));
register_pager("admincp/pages", array('use' => "page::admincp@lists_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-lists'));
register_pager("admincp/pages/categories", array('use' => "page::admincp@categories_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-categories'));
register_pager("admincp/pages/category/add", array('use' => "page::admincp@add_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-category-add'));
register_pager("admincp/pages/category/manage", array('use' => "page::admincp@manage_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-manage-category'));

register_pager('admincp/ads/service/list', array('use' => 'ads::admincp@ads_service_list_pager', 'filter' => 'admin-auth', 'as' => 'admin-ads-service-list'));
register_pager('admincp/ads/service/add', array('use' => 'ads::admincp@ads_service_add_pager', 'filter' => 'admin-auth', 'as' => 'admin-ads-service-add'));
register_pager('admincp/ads/service/edit', array('use' => 'ads::admincp@ads_service_edit_pager', 'filter' => 'admin-auth', 'as' => 'admin-ads-service-edit'));
register_pager('admincp/ads/service/delete', array('use' => 'ads::admincp@ads_service_delete_pager', 'filter' => 'admin-auth', 'as' => 'admin-ads-service-delete'));

register_pager("ads/activate/{id}", array('use' => "ads::ads@activate_ads_pager", 'filter' => 'user-auth', 'as' => 'ads-activate'))->where(array('id' => '[0-9]+'));
register_pager("ads/activate/stripe/{id}", array('use' => "ads::ads@ads_stripe_process_pager", 'filter' => 'user-auth', 'as' => 'ads-stripe-process'))->where(array('id' => '[0-9]+'));
register_pager("ads/activate/paypal/{id}", array('use' => "ads::ads@ads_paypal_process_pager", 'filter' => 'user-auth', 'as' => 'ads-paypal-notify'))->where(array('id' => '[0-9]+'));
register_pager("ads/edit/{id}", array('use' => "ads::ads@edit_ads_pager", 'filter' => 'user-auth', 'as' => 'ads-edit'))->where(array('id' => '[0-9]+'));
register_pager("ads/delete/{id}", array('use' => "ads::ads@delete_ads_pager", 'filter' => 'user-auth', 'as' => 'ads-delete'))->where(array('id' => '[0-9]+'));
register_pager("ads/video/get/overlay", array('use' => "ads::ads@get_overlay_ads_pager", 'filter' => 'user-auth', 'as' => 'ads-overlay-video-view'))->where(array('id' => '[0-9]+'));
register_pager("ads/load/plan", array('use' => "ads::ads@load_plans_pager", 'filter' => 'user-auth'));
register_pager("ads/load/description", array('use' => "ads::ads@load_plans_description_pager", 'filter' => 'user-auth'));
register_pager("ads/load/page", array('use' => "ads::ads@load_plans_page_pager", 'filter' => 'user-auth'));
register_pager("ads/process/create", array('use' => "ads::ads@process_create_ads_pager", 'filter' => 'user-auth'));
register_pager("ads/process/save", array('use' => "ads::ads@process_save_ads_pager", 'filter' => 'user-auth'));
register_pager("ads/clicked", array('use' => "ads::ads@ads_clicked_pager"));
register_pager("ads/get/video-ads", array('use' => "ads::ads@get_video_ads_pager"));
register_pager("ads/payment/success", array('use' => "ads::ads@ads_payment_success_pager", 'filter' => 'auth', 'as' => 'ads-payment-success'));
register_pager("ads/payment/cancel", array('use' => "ads::ads@ads_payment_cancel_pager", 'filter' => 'auth', 'as' => 'ads-payment-cancel'));


if(is_loggedIn()) {
	if(user_has_permission('can-create-ads')) {
		register_pager("ads", array('use' => "ads::ads@ads_pager", 'filter' => 'user-auth', 'as' => 'ads-manage'));
		register_pager("ads/create", array('use' => "ads::ads@create_ads_pager", 'filter' => 'user-auth', 'as' => 'ads-create'));

		//add_menu('header-account-menu', array('id' => 'create-ads', 'title' => lang('ads::create-ads'), 'link' => url_to_pager('ads-create')));
		//add_menu('header-account-menu', array('id' => 'manage-ads', 'title' => lang('ads::manage-ads'), 'link' => url_to_pager('ads-manage')));

	}

}

add_available_menu('ads::create-ads', 'ads/create', null, 'header-account-menu');
add_available_menu('ads::manage-ads', 'ads', null, 'header-account-menu');
add_available_menu('ads::ads', 'ads', null);


register_hook('feed.lists.inline', function($index) {
	if(fire_hook('server.privacy', 'ads-display')) {
		if(config('enable-post-inline-ads', true)) {
			$index = $index + 1;
			if($index == config('render-ads-after-post-number', 2)) {
				$array = array(1, 2);
				$key = array_rand($array, 1);
				$limit = 1;
				$ads = get_render_ads('all', $limit);
				echo view('ads::block/post', array('ads' => $ads));
			}
		}
	}
});

register_block("ads::block/render", lang('ads::side-ads-block'), null, array(
		'type' => array(
			'title' => lang('ads::ads-type'),
			'description' => lang('ads::ads-type-description'),
			'type' => 'selection',
			'value' => 'all',
			'data' => array(
				'all' => lang('all'),
				'page' => lang('ads::page'),
				'website' => lang('ads::website')
			)
		),
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 2
		),)
);

register_hook('user.delete', function($userid) {
	db()->query("DELETE FROM ads WHERE user_id='{$userid}'");
});

register_hook('payment.aff', function($type, $id, $user = null) {
	if($type == "ads-plan") {
		$ads = find_ads($id);
		activate_ads($ads);
	}
});

register_hook('layout.column.top.before', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('top.before', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.top.after', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('top.after', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.right.before', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('right.before', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.right.after', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('right.after', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.bottom.before', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('bottom.before', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.bottom.after', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('bottom.after', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.left.before', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('left.before', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.left.after', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('left.after', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.middle.before', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('middle.before', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('layout.column.middle.after', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('middle.after', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});

register_hook('feed.lists.inline', function() {
	$services = ads_service_list();
	foreach($services as $service) {
		if(in_array('feed.inline', explode(',', $service['positions']))) {
			echo $service['code'];
		}
	}
});
register_hook('off.menu.item', function($result, $location, $details) {
    if($details['link'] == 'ads/create' || $details['link'] == 'ads') {
        if(user_has_permission('can-create-ads')) {
            $result[0] = false;
        } else{
            $result[0] = true;
        }
    }
    return $result;
});