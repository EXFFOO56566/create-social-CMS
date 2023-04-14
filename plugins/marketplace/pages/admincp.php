<?php
get_menu("admin-menu", "plugins")->setActive();
get_menu("admin-menu", "plugins")->findMenu("admin-marketplace-manager")->setActive();

function categories_pager($app) {
	$app->setTitle(lang("marketplace::categories"));
	$categories = marketplace_get_categories();
	return $app->render(view('marketplace::admincp/category/list', array('categories' => $categories)));
}

function add_category_pager($app) {
	$app->setTitle(lang("marketplace::add-category"));
	$val = input('val');
	$messages = null;
	if($val) {
		CSRFProtection::validate();
		marketplace_execute_form($val);
		return redirect_to_pager('admin-marketplace-categories-list');
	}
	return $app->render(view('marketplace::admincp/category/add', array('messages' => $messages)));
}

function edit_category_pager($app) {
	$app->setTitle(lang("marketplace::edit-category"));
	$id = input('id', 0);
	$val = input('val');
	$messages = null;
	if($val) {
		CSRFProtection::validate();
		marketplace_execute_form($val);
		return redirect_to_pager('admin-marketplace-categories-list');
	}
	return $app->render(view('marketplace::admincp/category/edit', array('messages' => $messages, 'id' => $id, 'category' => marketplace_get_category($id))));
}

function delete_category_pager($app) {
	$app->setTitle(lang("marketplace::delete-category"));
	$val = input('val');
	$id = input('id', 0);
	$messages = '';
	$category = marketplace_get_category($id);
	$categories = marketplace_get_categories();
	if(!marketplace_is_category_exist($id)) {
		$messages = lang('category-delete-not-exist');
	}
	if($val) {
		CSRFProtection::validate();
		if(marketplace_is_category_exist($id)) {
			marketplace_execute_form($val);
			return redirect_to_pager('admin-marketplace-categories-list');
		} else {
			$messages = lang('category-delete-not-exist');
		}
	}
	return $app->render(view('marketplace::admincp/category/delete', array('messages' => $messages, 'id' => $id, 'category' => $category, 'categories' => $categories)));
}

function listings_pager($app) {
	$app->setTitle(lang("marketplace::manage-listings"));
	$category_id = input('c');
	$featured = input('f');
	$user_id = input('u');
	$term = input('s');
	$min_price = input('p0');
	$max_price = input('p1');
	$page = input('page');
	$categories = marketplace_get_categories();
	$category = $category_id ? lang(marketplace_get_category($category_id)['title']) : lang('marketplace::all-categories');
	$appends = $_GET;
	unset($appends['page']);
	$filters = array('category_id' => $category_id, 'term' => $term, 'user_id' => $user_id, 'featured' => $featured, 'min_price' => $min_price, 'max_price' => $max_price);
	$listings = marketplace_get_listings($filters, $page)->append($appends);
	$message = null;
	return $app->render(view('marketplace::admincp/listing/list', array('categories' => $categories, 'category' => $category, 'listings' => $listings, 'message' => $message)));
}

function edit_listing_pager($app) {
	$app->setTitle(lang("marketplace::edit-listing"));
	$message = null;
	$categories = marketplace_get_categories();
	$val = input('val');
	$id = input('id');
	$listing = marketplace_get_listing($id);
	$category_id = $listing['category_id'];
	if($val) {
		CSRFProtection::validate();
		$image = '';
		if(input_file('image')) {
			$uploader = new Uploader(input_file('image'));
			if($uploader->passed()) {
				$uploader->setPath('marketplace/listings/images/');
				$image = $uploader->resize()->result();
			} else {
				$message = $uploader->getError();
			}
			$val['image'] = $image;
		}
		if(!$message) {
			marketplace_execute_form($val);
			return redirect_to_pager('admin-marketplace-listings-list');
		}
	}
	$listing = marketplace_get_listing($id);
	return $app->render(view('marketplace::admincp/listing/edit', array('message' => $message, 'id' => $id, 'listing' => $listing, 'category_id' => $category_id, 'categories' => $categories)));
}

function delete_listing_pager($app) {
	$id = input('id', 0);
	$val = array('id' => $id, 'type' => 'delete_listing');
	marketplace_execute_form($val);
	return redirect_to_pager('admin-marketplace-listings-list');
}

function marketplace_action_batch_pager($app) {
	$action = input('action');

	$ids = explode(',', input('ids'));

	$db = db();
	foreach($ids as $id) {
		switch($action) {
			case 'delete_categories':
				$db->query("DELETE FROM marketplace_categories WHERE id = ".$id);
			break;
			case 'delete':
				$query = $db->query("SELECT id FROM marketplace_listings WHERE id = ".$id);
				while($row = $query->fetch_row()) {
					marketplace_delete_listing($row[0]);
				}
			break;

			default:
			break;
		}
	}
	return redirect_back();
}