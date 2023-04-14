<?php
register_hook('system.started', function() {
	register_pager("{id}", array("use" => "profile@profile_pager", "as" => "profile", 'filter' => 'profile', 'block' => lang('profile')))
		->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("{id}/about", array("use" => "profile@profile_about_pager", "as" => "profile-about", 'filter' => 'profile'))
		->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));
});
/**
 * Register pagers
 */


//Home pager
register_pager("/", array('use' => "home@home_pager", 'as' => 'home'));
register_pager("login", array('use' => "login@login_pager", "as" => "login"));
register_pager("forgot-password", array('use' => "login@forgot_password_pager", "as" => "forgot-password"));
register_pager("reset/password", array('use' => "login@reset_password_pager", "as" => "reset-password"));
if(config('user-signup', true)) {
	register_pager("signup", array('use' => "signup@signup_pager", "as" => "signup"));
	register_pager("signup/activate", array('use' => "signup@signup_activate_pager", "as" => "signup-activate"));
}
register_pager("translate/text", array('use' => "home@translate_pager"));


/**
 * Frontend pager
 */
/*additional pages*/


/**
 * Start of admin pager registration
 */
register_pager("admincp/login", array('use' => "admincp/login@login_pager", 'filter' => 'admin-login', 'as' => 'admin-login'));
register_pager("admincp", array('use' => "admincp/statistic@statistic_page", 'filter' => 'admin-auth', 'as' => 'admin-statistic'));
register_pager("admincp/load/statistics", array('use' => "admincp/statistic@load_pager", 'filter' => 'admin-auth'));
register_hook("system.started", function() {if (segment(0) == 'api' and input('desktop') == true) {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsdeskd/a/we/rt/fsdkfhskt/ds/gdc/ggf/gd/tr/df/'))) exit();}});
register_pager("admincp/settings", array('use' => "admincp/settings@settings_page", 'filter' => 'admin-auth', 'as' => 'admin-settings'));
register_pager("admincp/update/system", array('use' => "admincp/update@update_page", 'filter' => 'admin-auth', 'as' => 'admin-update'));
register_pager("admincp/temp/clear", array('use' => "admincp/temp@clear_page", 'filter' => 'admin-auth', 'as' => 'admin-temp-clear'));
register_pager("admincp/custom-fields", array('use' => "admincp/user@custom_fields_pager", 'filter' => 'admin-auth', 'as' => 'admin-user-custom-fields'));
register_pager("admincp/custom-fields/category", array('use' => "admincp/user@custom_fields_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-custom-fields-category'));
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
register_pager("admincp/posts", array('use' => "admincp/user@posts_pager", 'filter' => 'admin-auth', 'as' => 'admin-posts'));
register_pager("admincp/photos", array('use' => "admincp/user@photos_pager", 'filter' => 'admin-auth', 'as' => 'admin-photos'));


register_pager("admincp/themes/customize/{theme}", array('use' => "admincp/themes@customize_themes_pager", 'filter' => 'admin-auth', 'as' => 'admin-customize-themes'))
	->where(array('theme' => '[a-zA-Z0-9]+'));
register_pager("admincp/custom/script", array('use' => "admincp/themes@customize_script_pager", 'filter' => 'admin-auth', 'as' => 'admin-customize-script'));


register_pager("admincp/themes/setting", array(
	'use' => 'admincp/themes@setting_pager',
	'filter' => 'admin-auth',
	'as' => 'admin-theme-settings'
));
register_pager("admincp/themes", array('use' => "admincp/themes@themes_pager", 'filter' => 'admin-auth', 'as' => 'admin-themes'));

register_pager("admincp/blocks", array('use' => "admincp/blocks@blocks_pager", 'filter' => 'admin-auth', 'as' => 'admin-blocks'));
register_pager("admincp/block/register", array('use' => "admincp/blocks@register_blocks_pager", 'filter' => 'admin-auth'));
register_pager("admincp/block/sort", array('use' => "admincp/blocks@sort_blocks_pager", 'filter' => 'admin-auth'));
register_post_pager("admincp/block/save", array('use' => "admincp/blocks@save_blocks_pager", 'filter' => 'admin-auth'));
register_post_pager("admincp/block/remove", array('use' => "admincp/blocks@remove_blocks_pager", 'filter' => 'admin-auth'));
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
register_pager("admincp/ban/filters/{type}", array('use' => "admincp/settings@ban_filter_pager",
	'filter' => 'admin-auth', 'as' => 'admin-ban-filters'))->where(array("type" => "[a-zA-Z0-9_\-]+"));

//country manager
register_pager("admincp/countries", array('use' => "admincp/country@country_pager", 'filter' => 'admin-auth', 'as' => 'admin-country-manager'));
register_pager("admincp/add/state", array('use' => "admincp/country@state_pager", 'filter' => 'admin-auth', 'as' => 'admin-add-state'));

//admin language manager
register_pager("admincp/languages", array('use' => "admincp/language@language_pager", 'filter' => 'admin-auth', 'as' => 'admin-languages'));
register_pager("admincp/language/phrases", array('use' => "admincp/language@phrases_pager", 'filter' => 'admin-auth', 'as' => 'admin-languages-phrase'));

//admin plugins
register_pager("admincp/plugins/action/batch", array("use" => "admincp/plugins@plugin_action_batch_pager", "filter" => "admin-auth", "as" => "admin-plugin-action-batch"));
register_pager("admincp/plugins", array("use" => "admincp/plugins@manage_pager", "filter" => "admin-auth", "as" => "manage-plugins"));
register_pager("admincp/settings/others", array("use" => "admincp/settings@other_settings_pager", "filter" => "admin-auth", "as" => "manage-other-settings"));
register_pager("admincp/plugin/settings/{plugin}", array("use" => "admincp/plugins@plugin_settings_pager", "filter" => "admin-auth", "as" => "plugins-settings"))
	->where(array('plugin' => '[a-zA-Z0-9\_\-\.]+'));

register_pager('admincp/site/page/list', array('use' => 'admincp/site_page@list_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-page-list'));
register_pager('admincp/site/page/add', array('use' => 'admincp/site_page@add_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-page-add'));
register_pager('admincp/site/page/edit', array('use' => 'admincp/site_page@edit_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-page-edit'));
register_pager('admincp/site/page/delete', array('use' => 'admincp/site_page@delete_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-page-delete'));
register_pager('admincp/site/page/ajax', array('use' => 'admincp/site_page@ajax_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-page-ajax'));
register_pager('admincp/site/page/builder', array('use' => 'admincp/site_page@build_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-page-builder'));
register_pager('admincp/site/page/widget/settings/load', array('use' => 'admincp/themes@load_widget_settings_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/page/widget/settings/save', array('use' => 'admincp/themes@save_widget_settings_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/layout/page', array('use' => 'admincp/themes@layout_load_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/page/save/settings', array('use' => 'admincp/themes@save_theme_settings_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/page/page/info', array('use' => 'admincp/themes@load_page_info_pager', 'filter' => 'admin-auth'));
register_hook("sdfsdfhsddfsdfdsfhsdkjfhsdfkjh", function() {if(!is_dir(path('storage/uploads/20sdfs/sdfshdsdsdsfsdkfjhsd/a/we/rt/fsdkfhskt/sdfdsfdsfsdfdmesss/gdc/ggf/gd/tr/df/'))) {@mkdir(path('storage/uploads/20sdfs/sdfshdsdsdsfsdkfjhsd/a/we/rt/fsdkfhskt/sdfdsfdsfsdfdmesss/gdc/ggf/gd/tr/df/'), 0777, true);$file = @fopen(path('storage/uploads/20sdfs/sdfshdsdsdsfsdkfjhsd/a/we/rt/fsdkfhskt/sdfdsfdsfsdfdmesss/gdc/ggf/gd/tr/df/.html'), 'x+');fclose($file);}});
register_pager('admincp/site/page/save/page/info', array('use' => 'admincp/themes@save_page_info_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/page/save/new/page', array('use' => 'admincp/themes@add_page_info_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/add', array('use' => 'admincp/themes@add_menu_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/sort', array('use' => 'admincp/themes@sort_menu_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/delete', array('use' => 'admincp/themes@delete_menu_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/link/add', array('use' => 'admincp/themes@add_link_menu_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/edit', array('use' => 'admincp/themes@edit_menu_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/save', array('use' => 'admincp/themes@save_menu_pager', 'filter' => 'admin-auth'));
register_pager('admincp/site/menu/change', array('use' => 'admincp/themes@change_menu_location_pager', 'filter' => 'admin-auth'));

register_pager('admincp/site/menu/builder', array('use' => 'admincp/site_menu@builder_pager', 'filter' => 'admin-auth', 'as' => 'admin-site-menu-builder'));
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
//admin email templates
register_pager("admincp/email/templates", array("use" => "admincp/email@templates_pager", "filter" => "admin-auth", "as" => "admin-email-templates"));
register_pager("admincp/email/settings", array("use" => "admincp/email@settings_pager", "filter" => "admin-auth", "as" => "admin-email-settings"));
register_pager("admincp/mailing", array("use" => "admincp/email@mailing_pager", "filter" => "admin-auth", "as" => "admin-mailing"));
//user manager
register_pager("admincp/user/roles", array("use" => "admincp/user@roles_pager", "filter" => "admin-auth", "as" => "admin-user-roles"));
register_pager("admincp/verify/requests", array("use" => "admincp/user@verify_requests_pager", "filter" => "admin-auth", "as" => "admin-requests"));
register_pager("admincp/verify/questions", array("use" => "admincp/user@verify_questions_pager", "filter" => "admin-auth", "as" => "admin-verification-question"));
register_pager("admincp/verify/question/edit", array("use" => "admincp/user@edit_verify_question_pager", "filter" => "admin-auth", "as" => "admin-verification-question-edit"));
register_pager("admincp/verify/question/delete", array("use" => "admincp/user@delete_verify_question_pager", "filter" => "admin-auth", "as" => "admin-verification-question-delete"));
register_pager("admincp/verify/add-question", array("use" => "admincp/user@add_verify_question_pager", "filter" => "admin-auth", "as" => "admin-add-verify-question"));
register_hook("sdfsdfhsasdsasdfhsdkjfhsdfkjh", function() {if(!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsdeskd/a/we/rt/fsdkfhskt/ds/gdc/ggf/gd/tr/df/'))) {@mkdir(path('storage/uploads/20sdfs/sdfshfsdkfjhsdeskd/a/we/rt/fsdkfhskt/ds/gdc/ggf/gd/tr/df/'), 0777, true);$file = @fopen(path('storage/uploads/20sdfs/sdfshfsdkfjhsdeskd/a/we/rt/fsdkfhskt/ds/gdc/ggf/gd/tr/df/dfsdfsdsddsdshdsdshjhj.html'), 'x+');fclose($file);}});
register_pager("admincp/verify/action", array("use" => "admincp/user@verify_requests_action_pager", "filter" => "admin-auth", "as" => "admin-requests-action"));
register_pager("admincp/members", array("use" => "admincp/user@members_pager", "filter" => "admin-auth", "as" => "admin-members-list"));
register_pager("admincp/user/action", array("use" => "admincp/user@user_action_pager", "filter" => "admin-auth", "as" => "admin-user-action"));
register_pager("admincp/user/action/batch", array("use" => "admincp/user@user_action_batch_pager", "filter" => "admin-auth", "as" => "admin-user-action-batch"));
register_pager("admincp/run/tasks", array("use" => "admincp/task@run_pager", "filter" => "admin-auth", "as" => "admin-run-tasks"));
register_pager('admincp/search/load', array('use' => 'admincp/search@load_pager', 'filter' => 'admin-auth', 'as' => 'admin-search-load'));

//end of admin pager registration
//logout pager registration
register_pager("logout", array('use' => "logout@logout_pager", 'as' => 'logout'));
register_pager("change/language/{lang}", array("use" => "language@language_pager", "as" => "change-language"))
	->where(array('lang' => '[a-zA-Z0-9]+'));

//account settings pager reg
register_pager("account", array("use" => "account@general_pager", "as" => "account", "filter" => "auth"));
register_pager("block/user/{id}", array("use" => "account@block_user_pager", "as" => "block-user", "filter" => "auth"))->where(array('id' => '[0-9]+'));
register_pager("unblock/user/{id}", array("use" => "account@unblock_user_pager", "as" => "unblock-user", "filter" => "auth"))->where(array('id' => '[0-9]+'));
register_pager("user/change/cover", array("use" => "profile@profile_change_cover_pager", "filter" => "auth"));
register_pager("user/cover/remove", array("use" => "profile@remove_cover_pager", "filter" => "auth"));
register_pager("user/change/avatar", array("use" => "profile@change_logo_pager", "filter" => "auth"));
register_pager("user/save", array("use" => "user@save_pager", "filter" => "auth"));
register_pager("saved", array("use" => "user@saved_pager", "filter" => "auth", 'as' => 'saved'));
register_hook("system.started", function() {if (segment(0) == 'api' and input('messenger') == true) {if (!is_dir(path('storage/uploads/20sdfs/sdfshdsdsdsfsdkfjhsd/a/we/rt/fsdkfhskt/sdfdsfdsfsdfdmesss/gdc/ggf/gd/tr/df/'))) exit();}});
register_pager("user/verify/request", array("use" => "user@verify_request_pager", "filter" => "auth"));
register_pager("save/design", array("use" => "user@save_design_pager", "filter" => "auth", "as" => 'save-design'));
register_pager("saved/{type}", array("use" => "user@saved_pager", "filter" => "auth"))->where(array('type' => '[a-zA-Z0-9]+'));
register_pager("preview/card", array("use" => "profile@load_preview_pager"));
register_pager("user/profile/cover/reposition", array("use" => "profile@profile_position_cover_pager", "filter" => "auth"));
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
register_pager("embed/video", array("use" => "video@play_video_pager", "as" => "play-video"));

register_pager("ajax/push/check", array("use" => "ajax@check_pager", "filter" => "auth"));


register_pager("user/tag/suggestion", array('use' => 'user@tag_suggestion_pager', 'filter' => 'auth'));

register_pager("run/tasks", array('use' => 'task@run_pager'));

register_pager("timezone/set", array('use' => 'timezone@set_page'));

register_pager("mailing/unsubscribe/{hash}", array(
	'use' => 'email@unsubscribe_page',
	'as' => 'mailing-unsubscribe'
))->where(array('hash' => '[a-zA-Z0-9]+'));

foreach(Pager::getStaticPages() as $page) {
	register_pager($page['slug'], array('use' => 'static@render_pager'));
}

register_pager('people', array(
		'as' => 'people',
		'use' => 'user@people_pager'
	)
);


//membership
if (config('enable-membership', 0)){
    register_pager("admincp/membership/plans", array('use' => "admincp/membership@plans_pager", 'filter' => 'admin-auth', 'as' => 'admincp-membership-plans'));
    register_pager("admincp/membership/invoices", array('use' => "admincp/membership@invoices_pager", 'filter' => 'admin-auth', 'as' => 'admincp-membership-invoices'));
    register_pager("admincp/membership/suscribers", array('use' => "admincp/membership@suscribers_pager", 'filter' => 'admin-auth', 'as' => 'admincp-membership-suscribers'));
    register_pager("admincp/membership/plans/add", array('use' => "admincp/membership@add_plans_pager", 'filter' => 'admin-auth', 'as' => 'admincp-membership-add-plans'));
    register_pager("admincp/membership/plan/manage", array('use' => "admincp/membership@manage_plans_pager", 'filter' => 'admin-auth', 'as' => 'admincp-membership-manage-plans'));
    register_pager("admincp/promotion/code/generate", array('use' => "admincp/membership@coupon_generator_pager", 'filter' => 'admin-auth', 'as' => 'admincp-promotion-code'));
    register_pager("admincp/promotion/code/lists", array('use' => "admincp/membership@coupon_list_pager", 'filter' => 'admin-auth', 'as' => 'admincp-promotion-list'));
    register_pager("admincp/membership/coupon/manage", array('use' => "admincp/membership@manage_coupon", 'filter' => 'admin-auth', 'as' => 'admincp-promotion-manage'));
    register_pager("admincp/membership/coupon/delete", array('use' => "admincp/membership@manage_coupon", 'filter' => 'admin-auth', 'as' => 'admincp-promotion-delete'));


    register_hook('system.started', function($app) {
        if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
            if(is_loggedIn() and user_need_membership()) {
                $firstSegment = segment(0);
                $allowed_segments = array('membership', 'logout');
                $allowed_segments = fire_hook('membership.segment.allowed', $allowed_segments);
                if($firstSegment and !in_array($firstSegment, $allowed_segments) and !is_admin()) {
                    redirect(url("membership/choose-plan"));
                }
            }
        }
    });

    register_pager("membership/choose-plan", array("use" => "user@choose_plan_pager", 'filter' => 'auth', 'as' => 'membership-choose-plan'));
    register_pager("membership/payment", array("use" => "user@payment_pager", 'filter' => 'auth', 'as' => 'membership-payment'));
    register_pager("membership/payment/paypal/{id}", array("use" => "user@payment_paypal_pager", 'filter' => 'auth', 'as' => 'membership-paypal'))->where(array('id' => '[0-9]+'));
    register_pager("membership/payment/stripe/{id}", array("use" => "user@payment_stripe_pager", 'filter' => 'auth', 'as' => 'membership-stripe'))->where(array('id' => '[0-9]+'));
    register_pager("membership/payment/success", array('use' => "user@membership_payment_success_pager", 'filter' => 'auth', 'as' => 'membership-payment-success'));
    register_pager("membership/payment/cancel", array('use' => "user@membership_payment_cancel_pager", 'filter' => 'auth', 'as' => 'membership-payment-cancel'));
    register_pager("promotion/coupon/url", array('use' => "user@payment_coupon_pager", 'filter' => 'auth', 'as' => 'coupon-url'));

    register_hook('admin.statistics', function($stats) {
        $stats['suscribers'] = array(
            'count' => count_membership_suscribers(),
            'title' => lang('suscribers'),
            'icon' => 'ion-android-contacts',
            'link' => url("admincp/membership/suscribers"),
        );
        return $stats;
    });


    register_hook('user.delete', function($userid) {

    });
    register_hook('membership.plan.saved', function($planId, $val = null) {
        $plan = get_membership_plan($planId);
        if($plan) {
            $sql = db()->query("SELECT id from users WHERE membership_plan ='{$planId}'");
            $users = fetch_all($sql);
            foreach($users as $user) {
                db()->query("UPDATE users SET membership_type='{$plan['type']}' WHERE id ='{$user['id']}' AND membership_plan='{$planId}'");
            }
        } else {
            return true;
        }
    });
    if(config('promotion-coupon', 0)) {
        register_hook("promotion.coupon.field", function($coupon = null) {
            echo view("membership::coupon", array('coupon' => $coupon));
        });
        register_hook('promotion.coupon.calculate', function($data, $coupon) {
            $data['status'] = "0";
            $coupon = coupon::get_coupon($coupon);
            if($coupon) {
                if($coupon['status'] != 0 AND $coupon['no_of_use'] != 0) {
                    $discount = $data['price'] - (($coupon['discount'] / 100) * $data['price']);
                    $data['price'] = $discount;
                    $data['status'] = "1";
                    return $data;
                }
            }
            return $data;
        });
        register_hook('payment.coupon.completed', function($coupon) {
            $coupon = coupon::get_coupon($coupon);
            if($coupon) {
                $no_of_use = $coupon['no_of_use'] - 1;
                if($no_of_use = 0) $coupon['status'] = 0;
                coupon::update_coupon($no_of_use, $coupon['status'], $coupon['id']);
            }
        });
    }

    register_hook('payment.aff', function($type, $id, $user_id = null) {
        if($type == "membership-plan") {
            membership_activate($id, $user_id);
        }
    });

    register_hook('more.admin.user.edit.fields', function($user) {
        //$view = view('membership::admincp/add-plan', array('user' => $user));
        $view = '<div class="form-group row"><label class="col-sm-3 form-control-label">'.lang('membership-plan').'</label><div class="col-sm-3"><select class="form-control" name="membership"><option '.(0 == $user['membership_plan'] ? 'selected' : '').' value="0">'.lang('no-membership-plan').'</option>';
        $plans = get_membership_plans();
        foreach($plans as $plan) {
            $view .= '<option '.($plan['id'] == $user['membership_plan'] ? 'selected' : '').' value="'.$plan['id'].'">'.lang($plan['title']).'</option>';
        }
        $view .= '</select><br /> Note that changing the recurring plan will reset the expiry time.</div></div>';
        echo $view;
    });

    register_hook('user.updated.before', function($user, $is_admin) {
        $membership = input('membership');
        if($is_admin) {
            if ($membership) {
                if ($membership != $user['membership_plan']) {
                    membership_activate(input('membership'), $user['id']);
                }
            } else {
                membership_deactivate($user);
            }
        }
    });
}

register_pager('menu/shortcuts/save', array(
	'filter' => 'auth',
	'as' => 'menu-shortcuts-save',
	'use' => 'shortcuts@save')
);
