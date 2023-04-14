<?php
load_functions('marketplace::marketplace');

register_asset('marketplace::css/marketplace.css');

register_asset('marketplace::js/marketplace.js');


register_hook('role.permissions', function($roles) {
	$roles[] = array(
		'title' => lang('marketplace::marketplace-permissions'),
		'description' => '',
		'roles' => array(
			'can-create-listing' => array('title' => lang('marketplace::can-create-listing'), 'value' => 1),
		)
	);
	return $roles;
});

register_pager('admincp/marketplace/categories', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-categories-list',
	'use' => 'marketplace::admincp@categories_pager')
);

register_pager('admincp/marketplace/category/add', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-category-add',
	'use' => 'marketplace::admincp@add_category_pager')
);

register_pager('admincp/marketplace/category/edit', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-category-edit',
	'use' => 'marketplace::admincp@edit_category_pager')
);

register_pager('admincp/marketplace/category/delete', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-category-delete',
	'use' => 'marketplace::admincp@delete_category_pager')
);

register_pager('admincp/marketplace/list', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-listings-list',
	'use' => 'marketplace::admincp@listings_pager')
);

register_pager('admincp/marketplace/list?t=p', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-listings-list-pending',
	'use' => 'marketplace::admincp@listings_pager')
);

register_pager('admincp/marketplace/listing/edit', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-listing-edit',
	'use' => 'marketplace::admincp@edit_listing_pager')
);

register_pager('admincp/marketplace/listing/delete', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-listing-delete',
	'use' => 'marketplace::admincp@delete_listing_pager')
);

register_pager('admincp/marketplace/action/batch', array(
	'filter' => 'admin-auth',
	'as' => 'admin-marketplace-batch-action',
	'use' => 'marketplace::admincp@marketplace_action_batch_pager')
);

if(is_loggedIn() && user_has_permission('can-create-listing')) {
	register_pager('marketplace/create-listing', array(
			'filter' => 'user-auth',
			'as' => 'marketplace-create-listing',
			'use' => 'marketplace::create_listing@create_listing_pager'
		)
	);
}

register_pager('marketplace/edit-listing', array(
	'filter' => 'user-auth',
	'as' => 'marketplace-edit-listing',
	'use' => 'marketplace::edit_listing@edit_listing_pager')
);

register_pager('marketplace/delete-listing', array(
	'filter' => 'user-auth',
	'as' => 'marketplace-delete-listing',
	'use' => 'marketplace::delete_listing@delete_listing_pager')
);

register_pager('marketplace/add-images', array(
	'filter' => 'user-auth',
	'as' => 'marketplace-add-images',
	'use' => 'marketplace::add_images@add_images_pager')
);

register_pager('marketplace/delete-image', array(
	'filter' => 'user-auth',
	'as' => 'marketplace-delete-image',
	'use' => 'marketplace::delete_image@delete_image_pager')
);

register_pager('marketplace/listing/{slug}', array(
	'as' => 'marketplace-listing',
	'use' => 'marketplace::listing@listing_pager'))->where(array('slug' => '.*')
);

register_pager('marketplace', array(
	'as' => 'marketplace',
	'use' => 'marketplace::marketplace@marketplace_pager')
);

register_hook('admin-started', function() {
	get_menu('admin-menu', 'plugins')->addMenu(lang('marketplace::marketplace-manager'), '#', 'admin-marketplace-manager');
	get_menu('admin-menu', 'plugins')->findMenu('admin-marketplace-manager')->addMenu(lang('marketplace::categories'), url_to_pager('admin-marketplace-categories-list'), 'categories');
	get_menu('admin-menu', 'plugins')->findMenu('admin-marketplace-manager')->addMenu(lang('marketplace::add-category'), url_to_pager('admin-marketplace-category-add'), 'add-category');
	get_menu('admin-menu', 'plugins')->findMenu('admin-marketplace-manager')->addMenu(lang('marketplace::listings'), url_to_pager('admin-marketplace-listings-list'), 'listings');
});

register_hook('comment.add', function($type, $type_id, $text) {
	if($type == 'listing') {
		$listing = marketplace_get_listing($type_id);
		if($listing['user_id'] != get_userid()) {
			send_notification($listing['user_id'], 'listing.comment', $type_id, $listing, '', $text);
		}
	}
});

register_hook('display.notification', function($notification) {
	if($notification['type'] == 'listing.comment') {
		return view('marketplace::notifications/listing_comment', array('notification' => $notification, 'data' => unserialize($notification['data'])));
	}
});

register_hook('search-dropdown-start', function($content, $term) {
	$listings = marketplace_get_listings(array( 'term' => $term), null, 5);
	if($listings->total) {
		$content .= view('marketplace::search/dropdown', array('listings' => $listings));
	}
	return $content;
});

register_hook('register-search-menu', function($term) {
	add_menu('search-menu', array('title' => lang('marketplace::listings'), 'id' => 'listings', 'link' => form_search_link('listings', $term)));
});

register_hook('search-result', function($content, $term, $type) {
	if($type == 'listings') {
		get_menu('search-menu', 'listings')->setActive();
		$listings = marketplace_get_listings(array( 'term' => $term));
		$content = view('marketplace::search/page', array('listings' => $listings));
	}
	return $content;
});


register_hook('user.delete', function($userid) {
	$db = db();
	$db->query("DELETE FROM FROM marketplace_listings WHERE user_id = ".$userid);
});

register_hook('admin.statistics', function($stats) {
	$stats['marketplace'] = array(
		'count' => marketplace_num_listings(),
		'title' => lang('marketplace::marketplace'),
		'icon' => 'ion-android-cart',
		'link' => url_to_pager('admin-marketplace-listings-list'),
	);
	$stats['pendinglistings'] = array(
		'count' => marketplace_num_pending_listings(),
		'title' => lang('marketplace::pending-listings'),
		'icon' => 'ion-android-cart',
		'link' => url('admincp/marketplace/list?t=p'),
	);
	return $stats;
});

register_hook('uid.check', function($result, $value, $type = null, $type_id = null) {
	if(!$type || $type == 'marketplace.listing') {
		$listing = marketplace_get_listing($value);
		if($listing) {
			if(!$type_id || ($type_id && $type_id != $listing['id'])) {
				$result[0] = false;
			}
		}
	}
	return $result;
});

add_menu_location('marketplace-menu', 'marketplace::marketplace-menu');

add_available_menu('marketplace::marketplace', 'marketplace', 'ion-android-cart');

register_hook('marketplace.create', function($type, $id, $val = null) {
    if ($type =="marketplace.create" and $id){
        $market = marketplace_get_listing($id);
        if ($market){
            add_activity($market['link'], null, 'marketplace', $id, $market['privacy']);
            if($market['entity_type'] == 'user') {
                add_feed(array(
                    'entity_id' => $market['entity_id'],
                    'entity_type' => $market['entity_type'],
                    'type' => 'feed',
                    'type_id' => 'marketplace-added',
                    'type_data' => $id,
                    'marketplace' => $id,
                    'privacy' => $market['privacy'],
                    'images' => '',
                    'auto_post' => true,
                    'can_share' => 1
                ));
            }
        }
    }
});

register_hook('feed.arrange', function($feed) {
    if(is_numeric($feed['marketplace'])) {
        $market = marketplace_get_listing($feed['marketplace']);

        if($market) {
            if(($market['entity_id'] != get_userid()))
            $feed['marketplaceDetails'] = $market;
        }
    }
    return $feed;
});
register_hook("activity.title", function($title, $activity, $user) {
    switch($activity['type']) {
        case 'marketplace':
            $marketId = $activity['type_id'];
            $market = marketplace_get_listing_host($marketId);
            if(!$market) return "invalid";
            $link = $market['link'];
            $owner = marketplace_get_listing_host($market);

            return activity_form_link($owner['link'], $owner['name'], true)." ".lang("activity::added-new")." ".activity_form_link($activity['link'], lang('marketplace::marketplace'), true, true);
            break;
    }
    return $title;
});

register_hook('feed-title', function($feed) {
    if($feed['type_id'] == 'marketplace-added') {
        echo lang('marketplace::added-marketplace');
    }
});

register_hook('feed.custom.column.sql', function ($columns, $val){
    if (isset($val['marketplace'])){
        $columns[0] = !isset($columns) ? '' : ',marketplace';
    }
    return $columns;
});

register_hook('feed.custom.value.sql', function ($value, $val){
    if (isset($val['marketplace'])){
        $value[0] = !isset($value) ? '' : ",'{$val['marketplace']}'";
    }
    return $value;
});

register_hook('feeds.query.fields', function ($fields, $more = null){
    $fields .=",marketplace";
    return $fields;
});
register_hook('feed.post.plugins.hook', function($feed) {
    if ($feed['marketplace']){
        $market = marketplace_get_listing($feed['marketplace']);
        if ($market) echo view("marketplace::feed-content", array('listing'=> $market));
    }
});