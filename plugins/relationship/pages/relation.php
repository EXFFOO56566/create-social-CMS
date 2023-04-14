<?php
function select_family_pager($app) {
	CSRFProtection::validate(false);
	$uid = input('uid');
	return view('relationship::family', array('id' => $uid, 'type_id' => 0, 'relation' => 0));
}

function add_family_pager($app) {
	CSRFProtection::validate(false);
	$uid = input('uid');
	$type = input('type');
	$check_relation = check_relation($user = null, $uid);
	if($check_relation > 0) {
		$result = array('status' => false);
		return json_encode($result);
	} elseif($check_relation == 0) {
		$privacy = 1;
		$add = add_relationship_member(get_userid(), $type, $uid, 0, $privacy);
		if($add) {
			send_notification($uid, 'relationship.relation', $add, find_user($uid));
			$content = view('relationship::families', array('id' => $uid, 'type_id' => $add));
			$result = array('status' => true, 'content' => $content);
			return json_encode($result);
		}
	} else {
		$result = array('status' => false);
		return json_encode($result);
	}

}

function accept_relation_request_pager($app) {
	$id = input('id');
	$userid = input('userid');
	family_accept_request($userid, $id);
	send_notification($userid, 'relationship.accept', $id, find_user($userid));
	return redirect_back();
}

function decline_relation_request_pager($app) {
	$id = input('id');
	$userid = input('userid');
	family_accept_request($userid, $id);
	return redirect_back();
}

function delete_relation_request_pager($app) {
	$id = input('id');
	$userid = input('userid');
	delete_relation($id, $userid);
	return redirect_back();
}

function user_relation_update_pager($app) {
	$value = $_GET['val'];
	$user = get_userid();
	$success = "There was an error";
	$update = update_m_status($value, $user);
	if($update) {
	    $value = lang(get_family_relationship($value)['relationship']);
		$success = "Successfully Updated";
		$result = array('success' => $success, 'message' => $value, 'status' => '1');
		return json_encode($result);
	} else {
		$result = array('success' => $success, 'message' => 'There was an error', 'status' => 0);
		return json_encode($result);
	}
}
function relation_edit_pager(){
    $id = input('id');
    $type_id = input('r');
    $relation = check_relation_get(get_userid(), $id);
    return view('relationship::family', array('id' => $id,'type_id' => $type_id, 'relation' => $relation));
}
function relation_save_pager(){
    $id = input('id');
    $r = input('rel');
    $message = "There was an error";
    $status = 0;
    $content = "";
    $update = update_family_relation($id, $r);
    if ($update){
        $message = "Successful";
        $status = 1;
        $content = view('relationship::families', array('id' => $id,'type_id' => $id));
    }
    $result = array('id' => $id, 'success' => $message, 'content' => $content, 'status' => $status);
    return json_encode($result);
}