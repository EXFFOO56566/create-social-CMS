<?php
get_menu('admin-menu', 'monetization')->setActive()->findMenu("ads-manager")->setActive();

function ads_lists_pager($app) {
	$app->setTitle(lang('ads::manage-ads'));
	return $app->render(view('ads::lists'));
}

function ads_create_pager($app) {
	$app->setTitle(lang('ads::create-ads'));
	$view = view('ads::create');
	$view = fire_hook('replace.admin.create.ads.view', $view, array(null));
	return $app->render($view);
}

function ads_process_create_pager($app) {
	$result = array(
		'status' => 0,
		'message' => lang('ads::ads-create-default-error')
	);
	$val = input('val');
	if(!$val) return json_encode($result);

	return ads_create($val, $result, true);
}

function edit_ads_pager($app) {
	$id = segment(3);
	$ads = find_ads($id);
	if(!$ads) return redirect_to_pager('admin-ads-list');

	$app->setTitle(lang('ads::edit-ads'));
	$view = view('ads::edit', array('ads' => $ads));
	$view = fire_hook('replace.admin.edit.ads.view', $view, array($ads));
	return $app->render($view);
}

function ads_process_save_pager($app) {
	CSRFProtection::validate(false);
	$id = input('id');
	$result = array(
		'status' => 0,
		'message' => lang('ads::ads-create-default-error')
	);
	$ads = find_ads($id);
	if(!$ads) return json_encode($result);

	$val = input('val');
	if(!$val) return json_encode($result);

	return ads_save($val, $result, $ads, true);
}

function plans_pager($app) {
	$app->setTitle(lang('ads::ads-plan'));
	return $app->render(view('ads::plans/list', array('plans' => get_ads_plans())));
}

function add_plan_pager($app) {
	$app->setTitle(lang('ads::add-plan'));
	$val = input('val');
	$message = null;
	if($val) {
		CSRFProtection::validate();
		$validator = validator($val, array(
			'price' => 'required',
			'quantity' => 'required'
		));
		if(validation_passes()) {
			add_ads_plan($val);
			redirect_to_pager('admin-ads-plan');
		} else {
			$message = validation_first();
		}

	}
	return $app->render(view('ads::plans/add', array('message' => $message)));
}

function manage_plans_pager($app) {
	$action = input('action', 'order');

	switch($action) {
		case 'order':
			$ids = input('data');
			for($i = 0; $i < count($ids); $i++) {
				update_ads_plan_order($ids[$i], $i);
			}
		break;
		case 'edit':
			$plan = get_plan(input('id'));
			if(!$plan) return redirect_to_pager('admin-ads-plan');
			$app->setTitle(lang('ads::edit-plan'));
			$message = null;
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				save_ads_plan($val, $plan);
				redirect_to_pager('admin-ads-plan');
			}
			return $app->render(view('ads::plans/edit', array('plan' => $plan, 'message' => $message)));
		break;
		case 'delete':
			delete_ads_plan(input('id'));
			redirect_to_pager('admin-ads-plan');
		break;
	}
}

function ads_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));

	foreach($ids as $id) {
		switch($action) {
			case 'activate':
				db()->query("UPDATE ads SET status='1' WHERE ads_id='{$id}'");
			break;

			case 'deactivate':
				db()->query("UPDATE ads SET status='0' WHERE ads_id='{$id}'");

			break;

			case 'paid':
				db()->query("UPDATE ads SET paid='1' WHERE ads_id='{$id}'");
			break;

			case 'unpaid':
				db()->query("UPDATE ads SET paid='0' WHERE ads_id='{$id}'");
			break;
			case 'delete':
				delete_ads($id);
			break;
		}
	}
	return redirect_back();
}

function ads_plan_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));

	foreach($ids as $id) {
		delete_ads_plan($id);
	}
	return redirect_back();
}

function ads_service_list_pager($app) {
	$app->setTitle(lang('ads::ads-service'));
	$services = ads_service_list();
	return $app->render(view('ads::service/list', array('services' => $services)));
}

function ads_service_add_pager($app) {
	header('X-XSS-Protection: 0;');
	$app->setTitle(lang('ads::add-ads-service'));
	$message = '';
	$val = input('val', null, array('code'));
	if($val) {
		$val['code'] = mysqli_real_escape_string(db(), str_replace('&#39;', '\'', input('val.code', '', false)));
		$service_id = ads_service_add($val);
		if($service_id) {
			redirect_to_pager('admin-ads-service-list');
		}
	}
	return $app->render(view('ads::service/add', array('message' => $message)));
}

function ads_service_edit_pager($app) {
	header('X-XSS-Protection: 0;');
	$app->setTitle(lang('ads::edit-ads-service'));
	$message = '';
	$id = input('id');
	$service = ads_service_get($id);
	$val = input('val', null, array('code'));
	if($val) {
		$val['code'] = mysqli_real_escape_string(db(), str_replace('&#39;', '\'', input('val.code', '', false)));
		$update = ads_service_update($val);
		if($update) {
			redirect_to_pager('admin-ads-service-list');
		}
	}
	return $app->render(view('ads::service/edit', array('service' => $service, 'message' => $message)));
}

function ads_service_delete_pager($app) {
	$id = input('id');
	ads_service_delete($id);
	return redirect_back();
}