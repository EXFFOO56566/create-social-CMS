<?php
function clear_page($app) {
	clear_temp_data();
	return redirect_back(array('id' => 'admin-message', 'message' => lang('temp-cleared-message')));
}
function auto_delete_data_pager($app){
    $app->setTitle(lang('auto-delete-data'));
    $message = "";
    $status = 0;
    $val = input('delete');
    if ($val and is_admin()){
        $autoType = $val;
        if ($autoType){
            switch ($autoType){
                case 1:
                    // Delete all inactive users
                    $users = get_users('non-active')->results();
                    foreach ($users as $user){
                        delete_user($user['id']);
                    }
                    break;
                case 2:
                    // Delete users that have not logged in more than 1 week
                    $lastWeek = strtotime('-1 week');
                    $whereClause = "online_time < '{$lastWeek}'";
                    $users = get_users('','100000','',$whereClause);
                    foreach ($users->results() as $user){
                        delete_user($user['id']);
                    }
                    break;
                case 3:
                    // Delete users that have not logged in more than 1 month
                    $lastMonth = strtotime('-1 month');
                    $whereClause = "online_time < '{$lastMonth}'";
                    $users = get_users('','100000','',$whereClause);
                    foreach ($users->results() as $user){
                        delete_user($user['id']);
                    }
                    break;
                case 4:
                    // Delete users that have not logged in more than 1 year
                    $lastYear = strtotime('-1 year');
                    $whereClause = "online_time < '{$lastYear}'";
                    $users = get_users('','100000','',$whereClause);
                    foreach ($users->results() as $user){
                        delete_user($user['id']);
                    }
                    break;
                case 5:
                    // Delete posts that are longer than 1 week
                    $lastWeek = strtotime('-1 week');
                    $whereClause = "time < '{$lastWeek}'";
                    $feeds = get_feed($whereClause);
                    foreach ($feeds as $feed){
                        remove_feed($feed['feed_id'],$feed,true);
                    }
                    break;
                case 6:
                    // Delete posts that are longer than 1 month
                    $lastMonth = strtotime('-1 month');
                    $whereClause = "time < '{$lastMonth}'";
                    $feeds = get_feed($whereClause);
                    foreach ($feeds as $feed){
                        remove_feed($feed['feed_id'],$feed,true);
                    }
                    break;
                case 7:
                    // Delete posts that are longer than 1 year
                    $lastYear = strtotime('-1 year');
                    $whereClause = "time < '{$lastYear}'";
                    $feeds = get_feed($whereClause);
                    foreach ($feeds as $feed){
                        remove_feed($feed['feed_id'],$feed,true);
                    }
                    break;
                case 8:
                    // Delete all banned Users
                    $users = get_users('non-active')->results();
                    foreach ($users as $user){
                        delete_user($user['id']);
                    }
                    break;
            }
        }
        $status = 1;
        $result = array('status' => $status);
        return json_encode($result);
    }
    return $app->render(view('tool/auto-delete', array('message' => $message)));
}
 