<?php
function marketplace_get_categories() {
	$db = db();
	$sql = "SELECT * FROM marketplace_categories ORDER BY id";
	$query = $db->query($sql);
	$categories = fetch_all($query);
	return $categories;
}

function marketplace_is_category_exist($category_id) {
	$db = db();
	$sql = "SELECT id FROM marketplace_categories WHERE id = ".$category_id;
	$query = $db->query($sql);
	$num_rows = $query->num_rows;
	return $num_rows == 0 ? false : true;
}

function marketplace_get_category($category_id) {
	$db = db();
	$sql = "SELECT id, slug, title FROM marketplace_categories WHERE id = ".$category_id;
	$query = $db->query($sql);
	$category = $query->fetch_assoc();
	return $category;
}

function marketplace_get_listings($filters, $page = null, $limit = null) {
	$filters = isset($filters) ? $filters : array();
	$page = isset($page) && $page ? $page : 1;
	$limit = isset($limit) ? $limit : config('pagination-limit-listings', 20);
	$expected_filters = array('category_id' => null, 'user_id' => null, 'term' => null, 'featured' => null, 'id' => null, 'min_price' => null, 'max_price' => null, 'location' => null);
	extract(array_merge($expected_filters, $filters));
	$where_sql = "";
	if(isset($category_id) && $category_id) {
		$where_sql .= " AND marketplace_listings.category_id = ".$category_id;
	}
	if(isset($user_id) && $user_id) {
		$where_sql .= " AND marketplace_listings.user_id = ".$user_id;
	}
	if(isset($term) && $term && $term) {
		$where_sql .= " AND (marketplace_listings.title LIKE '%".$term."%' OR marketplace_listings.description LIKE '%".$term."%' OR marketplace_listings.tags LIKE '%".$term."%')";
	}
	if(isset($featured) && $featured != '') {
		$where_sql .= " AND marketplace_listings.featured = ".$featured;
	}
	if(isset($approved) && $approved != '') {
		$where_sql .= " AND marketplace_listings.approved = ".$approved;
	}
	if(isset($id) && $id) {
		$where_sql .= " AND ".(is_numeric($id) ? "marketplace_listings.id = ".$id : "marketplace_listings.slug = '".$id."'");
	}
	if(isset($min_price) && $min_price != '') {
		$where_sql .= " AND marketplace_listings.price >= ".$min_price;
	}
	if(isset($max_price) && $max_price != '') {
		$where_sql .= " AND marketplace_listings.price <= ".$max_price;
	}
	if(isset($location) && $location != '') {
		$where_sql .= " AND marketplace_listings.location LIKE '%".$location."%'";
	}
	$privacy_sql = fire_hook('privacy.sql', ' ');
	if($privacy_sql) {
		$where_sql .= " AND (".$privacy_sql.")";
	}
	if(!is_admin()) {
		$where_sql .= " AND marketplace_listings.active = 1";
	}
	if($where_sql) {
		$where_sql = " WHERE 1 ".$where_sql;
	}
	$sql = "SELECT marketplace_listings.id, marketplace_listings.slug, marketplace_listings.title, marketplace_listings.description, marketplace_listings.user_id, marketplace_listings.entity_type, marketplace_listings.entity_id, marketplace_listings.date, marketplace_listings.category_id, marketplace_listings.tags, marketplace_listings.image, marketplace_listings.location, marketplace_listings.contact, marketplace_listings.link, marketplace_listings.nov, marketplace_listings.last_viewed, marketplace_listings.price, marketplace_listings.featured, marketplace_listings.approved, marketplace_listings.active, marketplace_listings.privacy, marketplace_categories.title AS category_title, users.username, users.first_name, users.last_name, users.avatar FROM marketplace_listings LEFT JOIN users ON marketplace_listings.user_id = users.id LEFT JOIN marketplace_categories ON marketplace_listings.category_id = marketplace_categories.id".$where_sql." ORDER BY marketplace_listings.date DESC";
	$sql = fire_hook("more.where.to.marketplace", $sql, array($where_sql));
	$listings = paginate($sql, $limit, 7, $page);
	return $listings;
}

function marketplace_get_listing($listing_id) {
	$listing = false;
	$paginator = marketplace_get_listings(array('id' => $listing_id));
	foreach($paginator->results() as $listing) {
		break;
	}
	return $listing;
}

function marketplace_get_listing_images($listing_id) {
	$db = db();
	$sql = "SELECT id, image FROM marketplace_images WHERE listing_id = ".$listing_id;
	$query = $db->query($sql);
	$listing_images = fetch_all($query);
	return $listing_images;
}

function marketplace_get_listing_image($image_id) {
	$db = db();
	$sql = "SELECT id, image FROM marketplace_images WHERE id = ".$image_id;
	$query = $db->query($sql);
	$listing_image = $query->fetch_assoc();
	return $listing_image;
}

function marketplace_execute_form($val) {
	$db = db();
	$type = isset($val['type']) ? $val['type'] : null;
	switch($type) {
		case 'add_category':
			if(is_admin()) {
				/** @var array $title */
				$expected = array('title' => '');
				extract(array_merge($expected, $val));
				$title_slug = "marketplace_category_".md5(time().serialize($val)).'_title';
				$slug = unique_slugger(lang($title_slug));
				$sql = "INSERT INTO marketplace_categories(slug, title) VALUES('".$slug."', '".$title_slug."')";
				$query = $db->query($sql);
				if($query) {
					foreach($title as $lang_id => $t) {
						add_language_phrase($title_slug, $t, $lang_id, 'marketplace');
					}
				}
				if($query) {
					fire_hook('market.place.category.added', $db->insert_id);
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		break;

		case 'edit_category':
			if(is_admin()) {
				/** @var array $title */
				$expected = array('title' => '');
				extract(array_merge($expected, $val));
				$category = marketplace_get_category($val['id']);
				$title_slug = $category['title'];
				$slug = lang($category['title']) == $val['title'][app()->lang] ? $category['slug'] : unique_slugger(lang($title_slug));
				$sql = "UPDATE marketplace_categories SET slug = '".$slug."' WHERE id = ".$val['id'];
				$query = $db->query($sql);
				if($query) {
					foreach($title as $lang_id => $t) {
						(phrase_exists($lang_id, $title_slug)) ? update_language_phrase($title_slug, $t, $lang_id) : add_language_phrase($title_slug, $t, $lang_id, 'marketplace');
					}
				}
				if($query) {
					fire_hook("market.place.category.edited", $val['category_id']);
					return true;
				} else {
					return false;
				}
			}
			return false;
		break;

		case 'delete_category':
			if(is_admin()) {
				$new_id = $val['new_id'] == 'NULL' ? $val['id'] : $val['new_id'];
				$sql = "DELETE FROM marketplace_categories WHERE id = ".$val['id'];
				$query = $db->query($sql);
				if($query) {
					$category = marketplace_get_category($val['id']);
					delete_all_language_phrase($category['title']);
					$sql = "UPDATE marketplace_listings SET category_id = ".$new_id." WHERE category_id = ".$val['id'];
					$query = $db->query($sql);
				}
				return $query ? true : false;
			}
			return false;
		break;

		case 'create_listing':
			if(isset($val['entity'])) {
				$entity = explode('-', $val['entity']);
				if(count($entity) == 2) {
					$entity_type = $entity[0];
					$entity_id = $entity[1];
				}
			}
			if(!isset($entity_type) || !isset($entity_id)) {
				return false;
			}
			$db = db();
			$slug = unique_slugger($val['title']);
			$sql = "INSERT INTO marketplace_listings (slug, title, description, date, category_id, user_id, entity_type, entity_id, privacy, tags, location, contact, link, price, featured, image, approved, active) VALUES ('".$slug."', '".$val['title']."', '".$val['description']."', '".date('Y-m-d H:i:s')."', ".$val['category_id'].", ".get_userid().", '".$entity_type."', ".$entity_id.", '".$val['privacy']."', '".$val['tags']."', '".$val['location']."', '".$val['contact']."', '".$val['link']."', '".$val['price']."', 0, '".$val['image']."', ".config('default-approval', 1).", 1)";
			$query = $db->query($sql);
			$listing_id = $db->insert_id;
			fire_hook('marketplace.create', null, array($type = 'marketplace.create', $type_id = $listing_id, $text = $val['title']));
			fire_hook("market.place.arg", $listing_id, array());
			return $query ? $listing_id : false;
		break;

		case 'edit_listing':
			if(marketplace_is_listing_owner($val['id']) || is_admin()) {
				$listing = marketplace_get_listing($val['id']);
				$db = db();
				$slug = $listing['title'] == $val['title'] ? $listing['slug'] : unique_slugger(lang($val['title']));
				$active = is_admin() && isset($val['active']) ? $val['active'] : $listing['active'];
				$approved = is_admin() && isset($val['approved']) ? $val['approved'] : $listing['approved'];
				$featured = is_admin() && isset($val['featured']) ? $val['featured'] : $listing['featured'];
				$sql = "UPDATE marketplace_listings SET slug = '".$slug."', title = '".$val['title']."', description = '".$val['description']."', category_id = ".$val['category_id'].", tags = '".$val['tags']."', location = '".$val['location']."', contact = '".$val['contact']."', link = '".$val['link']."', price = '".$val['price']."', featured = ".$featured.", approved = ".$approved.", active = ".$active.", image = '".$val['image']."' WHERE id = ".$val['id'];
				$query = $db->query($sql);
				fire_hook("market.place.arg", $val['id'], array());
				return $query ? true : false;
			}
			return false;
		break;

		case 'delete_listing':
			if(marketplace_is_listing_owner($val['id']) || is_admin()) {
				$db = db();
				$sql = "DELETE FROM marketplace_listings WHERE id = ".$val['id'];
				$query = $db->query($sql);
				if($query) {
					fire_hook('marketplace-listing-delete', $val['id']);
					return true;
				} else {
					return false;
				}
			}
			return false;
		break;

		case 'add_images':
			if(marketplace_is_listing_owner($val['listing_id']) || is_admin()) {
				$db = db();
				$query = false;
				foreach($val['images'] as $image) {
					$sql = "INSERT INTO marketplace_images (image, listing_id) VALUES('".$image."', ".$val['listing_id'].")";
					$query = $db->query($sql);
				}
				return $query ? true : false;
			}
			return false;
		break;

		case 'delete_image':
			if(marketplace_is_listing_owner($val['listing_id']) || is_admin()) {
				$db = db();
				$sql = "SELECT image FROM marketplace_images WHERE id = ".$val['id'];
				$query = $db->query($sql);
				$row = $query->fetch_row();
				if(isset($row[0]) && $row[0]) {
					$image = $row[0];
					$sql = "DELETE FROM marketplace_images WHERE id = ".$val['id']." AND listing_id = ".$val['listing_id'];
					$query = $db->query($sql);
					if($query) {
						delete_file(path($image));
						return true;
					}
				}
			}
			return false;
		break;

		default:
			return false;
		break;
	}
}

function marketplace_view_listing($listing_id) {
	if(!isset($_SESSION['veiwedlistings']) || (isset($_SESSION['veiwedlistings']) && !in_array($listing_id, $_SESSION['veiwedlistings']))) {
		$db = db();
		$sql = "UPDATE marketplace_listings SET nov = nov + 1, last_viewed = '".date("Y-m-d H:i:s")."' WHERE id = ".$listing_id;
		$query = $db->query($sql);
		if($query) {
			$_SESSION['veiwedlistings'][] = $listing_id;
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function marketplace_delete_listing($listing_id) {
	$db = db();
	$sql = "DELETE FROM marketplace_listings WHERE id = ".$listing_id;
	$query = $db->query($sql);
	if($query) {
		$sql = "SELECT id, image FROM marketplace_images WHERE listing_id = ".$listing_id;
		$query = $db->query($sql);
		while($obsolete_image = $query->fetch_assoc()) {
			$sql = "DELETE FROM marketplace_images WHERE id = ".$obsolete_image['id'];
			$db->query($sql);
			delete_file(path($obsolete_image['image']));
		}
	}
	return $query ? true : false;
}

function marketplace_num_listings() {
	$db = db();
	$sql = "SELECT COUNT(id) FROM marketplace_listings";
	$query = $db->query($sql);
	$row = $query->fetch_row();
	$num_listings = $row[0];
	return $num_listings;
}

function marketplace_num_pending_listings() {
	$db = db();
	$sql = "SELECT COUNT(id) FROM marketplace_listings WHERE approved = 0";
	$query = $db->query($sql);
	$row = $query->fetch_row();
	$num_listings = $row[0];
	return $num_listings;
}

function marketplace_is_listing_owner($listing_id, $user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$listing = marketplace_get_listing($listing_id);
	return is_loggedIn() && $listing['user_id'] == $user_id ? true : false;
}

function marketplace_get_listing_host($listing) {
	$result = array('name' => '', 'image' => '', 'link' => '', 'id' => '');
	$entity = fire_hook('entity.info', $listing);
	$result['name'] = $entity['name'];
	$result['image'] = $entity['avatar'];
	$result['link'] = url($entity['id']);
	$result['id'] = $entity['id'];
	return $result;
}
