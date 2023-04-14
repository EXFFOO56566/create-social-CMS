<?php
get_menu("admin-menu", "billings")->setActive(true);
function suscribers_pager($app) {
    $active = input('filter', 'all');
    $app->setTitle(lang('suscribers'));
    return $app->render(view('user/membership/suscribers', array('users' => get_membership_suscribers($active), 'filter' => $active)));
}


function plans_pager($app) {
    $app->setTitle(lang('membership::membership-plans'));
    return $app->render(view('user/membership/plans', array("plans" => get_membership_plans())));
}

function add_plans_pager($app) {
    $app->setTitle(lang('membership::membership-plans'));
    $message = null;
    $val = input('val', null, array('content'));

    if($val) {
        CSRFProtection::validate();
        $validator = validator($val, array(
            'role' => 'required',
            'type' => 'required'
        ));

        if(validation_passes()) {
            add_membership_plan($val);
            redirect(url("admincp/membership/plans"));
        } else {
            $message = validation_first();
        }
    }
    return $app->render(view('user/membership/add-plan', array('message' => $message)));
}

function manage_plans_pager($app) {
    $app->setTitle(lang('membership::membership-plans'));
    $message = null;
    $action = input('action');
    $id = input('id');
    switch($action) {
        case 'delete' :
            delete_membership_plan($id);
            redirect(url("admincp/membership/plans"));
            break;
        case 'edit':
            $val = input('val');
            $plan = get_membership_plan($id);
            if(!$plan) redirect(url("admincp/membership/plans"));
            if($val) {
                CSRFProtection::validate();
                $validator = validator($val, array(
                    'role' => 'required',
                    'type' => 'required'
                ));

                if(validation_passes()) {
                    save_membership_plan($val, $plan);
                    redirect(url("admincp/membership/plans"));
                } else {
                    $message = validation_first();
                }
            }
            return $app->render(view('user/membership/edit-plan', array('message' => $message, 'plan' => $plan)));
            break;
    }

}

function coupon_generator_pager($app) {
    $val = input('val');
    if($val) {
        $generate = coupon::generating_value($val);
        if($generate) {
            $promotion_codes = coupon::getPromotionCodes();
            return $app->render(view('membership::coupons', array("promotion_codes" => $promotion_codes)));
        }
    }
    return $app->render(view('user/membership/generate-coupon-code'));
}

function coupon_list_pager($app) {
    $id = input('id');
    $status = input('status');
    $search = input('search');
    $promotion_codes = coupon::getPromotionCodes($id, $status, $search);
    return $app->render(view('user/membership/coupons', array("promotion_codes" => $promotion_codes)));
}

function manage_coupon($app) {
    $type = input('action');
    $message = "";
    if($type == "activate") {
        $id = input('id');
        $method = input('method');
        $activate = coupon::activate_coupon($id, $method);
        if($activate) {
            $message = $method."ed Successfully";
        }
    } elseif($type == 'delete') {
        $id = input('id');
        $activate = coupon::activate_delete($id);
        if($activate) {
            $message = "deleted Successfully";
        }
    } else {
        $message = "Unknown error occurred";
    }
    return redirect_back(array('id' => 1, 'message' => $message));
}