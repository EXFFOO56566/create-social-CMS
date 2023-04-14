<?php
function members_pager($app) {
	get_menu("admin-menu", "admin-users")->setActive();
	$app->setTitle(lang('manage-members'));
	$filter = input('filter', 'active');
	$users = get_users($filter, 20, input('val'));
	$users = fire_hook('modified.result.members', $users, array());
	return $app->render(view('user/lists', array('users' => $users, 'filter' => $filter)));
}

function posts_pager($app) {
	$app->setTitle(lang('manage-posts'));
	get_menu("admin-menu", "cms")->setActive();
	return $app->render(view('posts/list', array('feeds' => get_all_feeds())));
}

function user_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));
	foreach($ids as $id) {
		$user = find_user($id, true);
		if($user) {
			switch($action) {
				case 'verify':
					$fields = array('verified' => 1);
					update_user($fields, $user['id'], false, true);
				break;

				case 'activate':
					$fields = array(
						'active' => 1,
						'activated' => 1
					);
					update_user($fields, $user['id'], false, true);
				break;

				case 'deactivate':
					$fields = array(
						'active' => 0,
						'activated' => 0
					);
					update_user($fields, $user['id'], false, true);
				break;

				case 'ban':
					$fields = array('banned' => 1);
					update_user($fields, $user['id'], false, true);
				break;

				case 'delete':
					delete_user($id);
				break;
			}
		}
	}
	if($action == "export") {
		$delimiter = ",";
		$filename = "members_".date('Y-m-d').".csv";

		//create a file pointer
		$f = fopen('php://memory', 'w');
		//set column headers
		$fields = array('ID', 'Username', 'Email', 'Firstname', 'Lastname', 'Country', 'Gender', 'DOB', 'Join date', 'Status');
		$fields = fire_hook('users.export.header', $fields);
		fputcsv($f, $fields, $delimiter);
		$export_all = input('all');
		if(!$export_all) {
			foreach($ids as $id) {
				$user = find_user($id);
				$status = ($user['active'] == '1') ? 'Active' : 'Inactive';
				$lineData = array($user['id'], $user['username'], $user['email_address'], $user['first_name'], $user['last_name'], $user['country'], $user['gender'], $user['birth_day']."-".$user['birth_month']."-".$user['birth_year'], $user['join_date'], $status);
				$lineData = fire_hook('users.export.data', $lineData);
				fputcsv($f, $lineData, $delimiter);
			}
		} else {
			foreach(get_all_user() as $user) {
				$status = ($user['active'] == '1') ? 'Active' : 'Inactive';
				$lineData = array($user['id'], $user['username'], $user['email_address'], $user['first_name'], $user['last_name'], $user['country'], $user['gender'], $user['birth_day']."-".$user['birth_month']."-".$user['birth_year'], $user['join_date'], $status);
				$lineData = fire_hook('users.export.data', $lineData);
				fputcsv($f, $lineData, $delimiter);
			}
		}

		//move back to beginning of file
		fseek($f, 0);

		//set headers to download file rather than displayed
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'";');

		try {
			//output all remaining data on a file pointer
			fpassthru($f);
		} catch (Exception $e) {
			exit($e->getMessage());
		}
		exit;
	}
	return redirect_back();
}


function user_action_pager($app) {
	get_menu("admin-menu", "admin-users")->setActive();
	$app->setTitle(lang('manage-members'));
	$type = input('type', 'edit');

	switch($type) {
		case 'edit':
			$user = find_user(input('id'), true);
			if(!$user) return redirect(url_to_pager('admin-members-list'));
			$val = input('val');

			if($val) {
				CSRFProtection::validate();
				/**
				 * @var $password
				 */
				extract($val);
				if(isset($val['password'])) {
					$password = ($password) ? hash_make($password) : $user['password'];
					$val['password'] = $password;
				}

				if(isset($val['activated'])) {
					$val['active'] = $val['activated'];
				}

				$val = fire_hook("update.customized.fields", $val, array($user));
				update_user($val, $user['id'], false, true);
				fire_hook('user.type.signup.completed', $user['id']);
				redirect(url_to_pager('admin-members-list'));
			}
			return $app->render(view('user/edit-user', array('user' => $user)));
		break;
		case 'delete':
			delete_user(input('id'));
			redirect(url_to_pager('admin-members-list'));
		break;
		case 'add':
			$val = input('val');
			$message = "";
			if($val) {
				CSRFProtection::validate();
				/**
				 * @var $password
				 */
				$rules = array(
					'first_name' => 'required|predefined',
					'last_name' => 'required|predefined',
					'username' => 'required|predefined|alphanum|min:3|username',
					'email_address' => 'required|email|unique:users',
					'password' => 'required|min:6',
					'country' => 'required'
				);
				if(input('val.password') != input('val.cpassword')) {
					$message = 'Password do not match';
				}
				$gender = input('val.gender');
				if(!($gender == 'male' || $gender == 'female' || empty($gender))) {
					$message = 'Invalid Gender';
				}
				load_functions('country');
				if(!is_valid_country(input('val.country'))) {
					$message = 'Invalid Country';
				}
				if(validation_passes() && !$message) {
					$added = add_user($val);
					if($added) {
						redirect(url_to_pager('admin-members-list'));
					}
				}
			}
			$_GET['message'] = $message;
			return $app->render(view('user/add-user', array('message' => $message)));
		break;
	}
}

function custom_fields_pager($app) {
	$type = input('type', 'user');
	$action = input('action', 'list');
	$message = "";

	get_menu('admin-menu', 'settings')->setActive();
	//get_menu("admin-menu", "admin-users")->setActive();
	if($type == 'user') {
		//get_menu('admin-menu', 'admin-custom-field')->findMenu('user-custom-fields')->setActive();
	}

	fire_hook("admincp.custom-field", null, array($type));

	switch($action) {
		case 'add':
			$val = input('val');
			$app->setTitle(lang('add-new-custom-field'));
			if($val) {
				CSRFProtection::validate();
				$added = add_custom_field($type, $val);
				if($added) redirect(url("admincp/custom-fields/?type=".$type));
				$message = "Failed to add custom field maybe it already exists";
			}
			$content = view("custom-fields/add", array('type' => $type, "message" => $message));
		break;
		case 'edit':
			$id = input('id');
			$app->setTitle(lang('edit-custom-field'));
			$field = get_custom_field($id);
			if(!$field) redirect_to_pager("admin-user-custom-fields");

			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				$save = add_custom_field($type, $val, true, $id);
				if($save) redirect(url("admincp/custom-fields/?type=".$type));
			}

			$content = view("custom-fields/edit", array('field' => $field, 'type' => $type));
		break;
		case 'delete':
			$id = input('id');
			delete_custom_field($id);
			redirect(url("admincp/custom-fields/?type=".$type));
		break;
		case 'order':
			CSRFProtection::validate(false);
			$ids = input('data');
			$category = input('category');
			for($i = 0; $i < count($ids); $i++) {
				update_custom_field_order($category, $ids[$i], $i);
			}
			exit;
		break;
		default:
			$app->setTitle(lang('custom-fields'));
			$content = view("custom-fields/list", array('type' => $type));
		break;
	}
	return $app->render($content);
}

function custom_fields_category_pager($app) {
	$action = input("action", "list");
	$type = input("type", "user");
	$message = null;

	get_menu('admin-menu', 'settings')->setActive();
	//get_menu("admin-menu", "admin-users")->setActive();
	if($type == 'user') {
		get_menu('admin-menu', 'admin-custom-field')->findMenu('users-custom-fields')->setActive();
	}
	fire_hook("admincp.custom-field", null, array($type));

	switch($action) {
		default:
			$app->setTitle(lang('custom-fields-categories'));
			$categories = get_custom_field_categories($type);
			$content = view("custom-fields/categories", array('categories' => $categories, 'type' => $type));
		break;
		case "edit":
			$app->setTitle(lang('edit-custom-field-category'));
			$category = get_custom_field_category(input("id"));
			if(!$category) return redirect(url_to_pager("admin-custom-fields-category")."?type=".$type);
			$title = input("title");
			if($val = input('val')) {
				save_custom_field_category(input("id"), $val);
				return redirect(url_to_pager("admin-custom-fields-category")."?type=".$type);
			}
			$content = view("custom-fields/edit-category", array("category" => $category));
		break;
		case "delete":
			delete_custom_field_category(input("id"));
			return redirect(url_to_pager("admin-custom-fields-category")."?type=".$type);
		break;
		case "add":
			$app->setTitle(lang('add-custom-field-category'));
			$val = input("val");
			if($val) {
				CSRFProtection::validate();
				$added = add_custom_field_category($val, $type);
				if($added) return redirect(url_to_pager("admin-custom-fields-category")."?type=".$type);
				$message = "Failed to add custom field category, try again..";
			}
			$content = view("custom-fields/add-category", array("message" => $message, 'type' => $type));
		break;
	}
	return $app->render($content);
}

function roles_pager($app) {
	get_menu("admin-menu", "admin-users")->setActive();
	$app->setTitle(lang('user-roles'));
	$action = input('action', 'lists');

	switch($action) {
		default:
			$val = input('val');
			$message = "";
			$errorMessage = "";
			if($val) {
				CSRFProtection::validate();
				$add = add_user_role($val);
				if($add) {
					$message = lang('successfully-done');
				} else {
					$errorMessage = lang('user-role-error');
				}
			}
			$content = view("user/roles", array('message' => $message, 'errorMessage' => $errorMessage));
		break;
		case 'edit':
			$role = get_user_role(input('id'));
			if(!$role or !$role['can_edit']) return redirect_to_pager('admin-user-roles');
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				save_user_role($val, $role);
				return redirect_to_pager('admin-user-roles');
			}
			$content = view("user/edit-role", array('dbrole' => $role));
		break;
		case 'delete' :
			$role = get_user_role(input('id'));
			if(!$role or !$role['can_delete']) return redirect_to_pager('admin-user-roles');
			delete_user_role($role);
			return redirect_to_pager('admin-user-roles');
		break;
	}
	return $app->render($content);
}


function verify_questions_pager($app) {
	$app->setTitle(lang('verification-questions'));
	$questions = get_verification_questions();
	return $app->render(view('user/verify-questions', array('questions' => $questions)));
}

function add_verify_question_pager($app) {
	$app->setTitle(lang('add-verification-question'));
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		submit_question($val);
		redirect_to_pager('admin-verification-question');
	}
	return $app->render(view('user/add-question'));
}

function edit_verify_question_pager($app) {
	$app->setTitle(lang('add-verification-question'));
	$id = input('id');
	$val = input('val');
	$question = get_verification_question($id);
	if($val) {
		CSRFProtection::validate();
		edit_verification_question($val);
		redirect_to_pager('admin-verification-question');
	}
	return $app->render(view('user/edit-question', array('question' => $question)));
}

function delete_verify_question_pager($app) {
	$app->setTitle(lang('add-verification-question'));
	$id = input('id');
	delete_verification_question($id);
	redirect_to_pager('admin-verification-question');
}

function verify_requests_pager($app) {
	$app->setTitle(lang('verification-requests'));
	$type = input('type');
	return $app->render(view('user/requests', array('requests' => get_verification_requests($type))));
}

function verify_requests_action_pager($app) {
	$id = input('id');
	$type = input('type');
	$query = db()->query("SELECT * FROM verification_requests WHERE id = ".$id);
	$request = $query->fetch_assoc();
	$request_type = $request['type'];
	$request_type_id = $request['type_id'];
	if($type == 'approve') {
		db()->query("UPDATE verification_requests SET approved = 1 WHERE id = ".$id);
		if($request_type == 'user') {
			db()->query("UPDATE users SET verified = 1 WHERE id = ".$request_type_id);
		} elseif($request_type == 'page') {
			db()->query("UPDATE pages SET verified = 1 WHERE page_id = ".$request_type_id);
		}
	} else if($type == 'ignore' || $type == 'revoke') {
		db()->query("DELETE FROM verification_requests WHERE id = ".$id);
		if($request_type == 'user') {
			db()->query("UPDATE users SET verified = 0 WHERE id = ".$request_type_id);
		} elseif($request_type == 'page') {
			db()->query("UPDATE pages SET verified = 0 WHERE page_id = ".$request_type_id);
		}
	}
	return redirect_back();
}