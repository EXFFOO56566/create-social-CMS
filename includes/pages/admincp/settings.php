<?php
//load_functions("admin_settings");


function settings_page($app) {

    $type = input("type");
    $val = input("val", null, false);
    $message = "";
    get_menu("admin-menu", "settings")->setActive();
    if (($type)){
        if($val) {
            CSRFProtection::validate();
            if(array_key_exists('completion-first_name', $val)){
                $percentage = 0;
                foreach ($val as $key => $value){
                    $percentage +=  $value;
                }
                if ( is_numeric($percentage) && ($percentage =='100' || $percentage < 100)){
                    save_admin_settings($val);
                } else{
                    $message = "Total Percentage Must not be greater than 100";
                }
            } elseif (isset($val['activate-main-app']) and $val['activate-main-app'])  {if(dsjfslfkjsdlfjsdlfjsdlk($val['activate-main-app'])){save_admin_settings($val);}else{$message='Invalid app license, please confirm the license key';}}
            elseif (isset($val['activate-messenger-app']) and $val['activate-messenger-app'])  {if(dsjfslfkjsdlfjsdlfjshdfdssdlk($val['activate-messenger-app'])){save_admin_settings($val);}else{$message='Invalid messenger app license, please confirm the license key';}}
            elseif (isset($val['activate-deskmessenger-app']) and $val['activate-deskmessenger-app'])  {if(dsjfslfkjsdlfjsdlfsdfhsdkfsjsdlk($val['activate-deskmessenger-app'])){save_admin_settings($val);}else{$message='Invalid messenger desktop app license, please confirm the license key';}}
            else{
                save_admin_settings($val);
                if($type == 'system') {
                    delete_file(path('storage/assets/'));
                    redirect(url('admincp/settings'));
                }
            }
        }

		$settings = '';
		$allSettings = get_settings($type);
		if(isset($allSettings[input('id')])) $settings = $allSettings[input('id')];
		if(!$settings) return redirect("settings");
		$app->setTitle($settings['title']);
		return $app->render(view("settings/content", array(
			'settings' => $settings['settings'],
			'title' => $settings['title'],
			'description' => $settings['description'],
			'associates' => $allSettings,
			'message' => $message
		)));
	}
	return $app->render(view("settings/main"));
}

function ban_filter_pager($app) {
    $type = segment(3);

    $app->setTitle(lang('manage-ban-filters'));

    get_menu('admin-menu', 'tools')->setActive();
    get_menu('admin-menu', 'tools')->findMenu('admin-ban-filters')->setActive();

    $accepted = array("usernames", "emails", "names", "ip", "words");
    if(!in_array(strtolower($type), $accepted)) redirect_to_pager("admin-statistic");
    $var = "ban_filters_".$type;
    $val = input("val");
    if($val) {
        CSRFProtection::validate();
        save_admin_settings($val);
        redirect_to_pager("admin-ban-filters", array("type" => $type));
    }
    return $app->render(view("settings/ban-filter", array("type" => $type, "var" => $var)));
}

function other_settings_pager($app){
    $app->setTitle(lang("Other Settings"));
    $message = "";
    $key = input('other');
    $settings = get_other_settings($key);
    $val = input("val");
    if($val) {
        save_admin_settings($val);
        redirect(url('admincp/settings'));
    }
    return $app->render(view("settings/others", array("settings" => $settings, "message" => $message)));
}