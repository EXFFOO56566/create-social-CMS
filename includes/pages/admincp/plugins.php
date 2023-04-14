<?php
function manage_pager($app) {
	get_menu("admin-menu", "settings")->setActive();
	$app->setTitle(lang("manage-plugins"));

	$action = input("action");
	$core = input('core', false);
	switch($action) {
		case 'activate':
			$id = input('id');
			$license = input('license');
			$message = '';
			if(!$id) redirect_to_pager('manage-plugins');
			$status = plugin_activate($id, $license);
			if(input('ajax')) {
				$plugins = get_all_plugins();
				$info = $plugins[$id];
				$html = view('plugins/load', array('plugin' => $id, 'info' => $info));
				$message = isset($license) && $status == 2 ? lang('invalid-license-key') : $message;
				$result = array('status' => $status, 'id' => $id, 'html' => $html, 'message' => $message);
				$response = json_encode($result);
				return $response;
			} else {
				return redirect_to_pager("manage-plugins", array(), admin_flash_message(lang("plugin-activated", array('name' => ucwords($id)))));
			}
		break;
		case 'deactivate':
			$id = input("id");
			if(!$id) redirect_to_pager('manage-plugins');
			plugin_deactivate($id);
			return redirect_to_pager("manage-plugins", array(), admin_flash_message(lang("plugin-deactivated", array('name' => ucwords($id)))));
		break;
		case 'update':
			$id = input("id");
			if(!$id) redirect_to_pager('manage-plugins');
			plugin_update($id, true);
			return redirect_to_pager("manage-plugins", array(), admin_flash_message(lang("plugin-updated", array('name' => ucwords($id)))));

		break;
		case 'delete':
			$id = input("id");
			if(!$id) redirect_to_pager('manage-plugins');
			plugin_delete($id);
			return redirect_to_pager("manage-plugins", array(), admin_flash_message(lang("plugin-removed", array('name' => ucwords($id)))));

		break;
	}

	return $app->render(view("plugins/manage", array("plugins" => get_all_plugins(), 'core' => $core)));
}

function plugin_settings_pager($app) {
	get_menu("admin-menu", "settings")->setActive();
	$app->setTitle(lang("manage-plugins"));

	$settings = plugin_get_settings(segment(3));
	if(empty($settings)) redirect_back();

	$val = input("val");
	if($val) {
		CSRFProtection::validate();
		//we are saving the settings
		save_admin_settings($val);
		admin_flash_message(lang("plugin-settings-saved", array('name' => ucwords(segment(3)))));
	}

	return $app->render(view("settings/content", array(
		'settings' => isset($settings['settings']) ? $settings['settings'] : array(),
		'title' => isset($settings['title']) ? $settings['title'] : '',
		'description' => isset($settings['description']) ? $settings['description'] : ''
	)));
}


function plugin_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));

	foreach($ids as $id) {
		switch($action) {
			case 'deactivate':
				plugin_deactivate($id);
			break;

//                case 'activate':
//                    plugin_activate($id, $force = true);
//                break;

			case 'update':
				plugin_update($id, $install = false);
			break;
			case 'delete':
				plugin_delete($id);
			break;
		}
	}
	return redirect_back();
}

