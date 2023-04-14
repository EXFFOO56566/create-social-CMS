<?php
function add_blog($val) {
	$expected = array(
		'title' => '',
		'content' => '',
		'tags' => '',
		'category' => '',
		'entity' => '',
		'privacy' => ''
	);
	/**
	 * @var $title
	 * @var $content
	 * @var $tags
	 * @var $status
	 * @var $category
	 * @var $entity
	 * @var $privacy
	 *
	 */
	extract(array_merge($expected, $val));

	$image = '';
	$file = input_file('image');
	if($file) {
		$uploader = new Uploader($file);
		if($uploader->passed()) {
			$uploader->setPath('blogs/preview/');
			$image = $uploader->resize(700, 500)->result();
		}
	}

	$time = time();
	$userid = get_userid();
	$slug = unique_slugger($title);
	$content = html_purifier_purify($content);
	$entity = explode('-', $entity);
	if(count($entity) == 2) {
		$entity_type = $entity[0];
		$entity_id = $entity[1];
	}
	if(!isset($entity_type) || !isset($entity_type)) {
		return false;
	}
	db()->query("INSERT INTO blogs (user_id, entity_type, entity_id, title, slug, content, image, tags, update_time, time,status, category_id, privacy) VALUES ('".$userid."', '".$entity_type."', '".$entity_id."', '".$title."', '".$slug."', '".$content."', '".$image."', '".$tags."', '".$time."', '".$time."', '".$status."', '".$category."', '".$privacy."')");
	$blogId = db()->insert_id;
	$blog = get_blog($blogId);
	fire_hook("blog.added", null, array($blogId, $blog));
	fire_hook('plugins.users.category.updater', null, array('blogs', $val, $blogId, 'id'));
	return $blogId;
}

function save_blog($val, $blog, $admin = false) {
	$expected = array(
		'title' => '',
		'slug' => '',
		'content' => '',
		'tags' => '',
		'category' => '',
		'privacy' => '',
		'featured' => $blog['featured']
	);
	/**
	 * @var $title
	 * @var $slug
	 * @var $content
	 * @var $tags
	 * @var $status
	 * @var $category
	 * @var $privacy
	 * @var $featured
	 */
	if(!$admin) $val['featured'] = $blog['featured'];
	extract(array_merge($expected, $val));
	$image = $blog['image'];
	$id = $blog['id'];
	$slug = unique_slugger($title, 'blog', $blog['id']);
	$file = input_file('image');
	if($file) {
		$uploader = new Uploader($file);
		if($uploader->passed()) {
			$uploader->setPath('blogs/preview/');
			$image = $uploader->resize(700, 500)->result();
		}
	}

	$time = time();
	$content = html_purifier_purify($content);
	db()->query("UPDATE blogs SET slug = '".$slug."', featured = '".$featured."', image = '".$image."', title = '".$title."', tags = '".$tags."', content = '".$content."', status = '".$status."', update_time = '".$time."', privacy = '".$privacy."', category_id = '".$category."' WHERE id = '".$id."'");
	fire_hook('plugins.users.category.updater', null, array('blogs', $val, $id, 'id'));
	return true;
}

function get_blog($id) {
	$db = db();
	$query = $db->query("SELECT * FROM blogs WHERE ".(is_numeric($id) ? "id = ".$id : "slug = '".$id."'"));
	$blog = $query->fetch_assoc();
	return $blog ? arrange_blog($blog) : $blog;
}

function is_blog_owner($blog) {
	if(!is_loggedIn()) return false;
	if($blog['user_id'] == get_userid()) return true;
	return false;
}

function delete_blog($id) {
	$blog = get_blog($id);
	if($blog['image']) delete_file(path($blog['image']));
	return db()->query("DELETE FROM blogs WHERE id='".$id."'");
}

function get_blogs($type, $category = null, $term = null, $user_id = null, $limit = 10, $filter = 'all', $blog = null, $entity_type = 'user', $entity_id = null) {
	$limit = isset($limit) ? $limit : 10;
	$sql = "SELECT * FROM blogs ";
	$user_id = $user_id ? $user_id : get_userid();
	$sql = fire_hook("use.different.blogs.query", $sql, array());
	if($type == 'mine') {
		$sql .= " WHERE user_id = '".$user_id."' ";
		$sql .= $filter == 'featured' ? " AND featured = '1' " : '';
	} elseif($type == 'related') {
		$title = $blog['title'];
		$explode = explode(' ', $title);
		$w = '';
		foreach($explode as $t) {
			$w .= $w ? " OR  (title LIKE '%".$t."%' OR content LIKE '%".$t."') " : "  (title LIKE '%".$t."%' OR content LIKE '%".$t."')";
		}
		$blog_id = $blog['id'];
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$sql .= " WHERE (".$w.") AND status = '1' AND id != '".$blog_id."' AND (".$privacy_sql.") ";
		$sql = fire_hook("more.blogs.query.filter", $sql, array($entity_type, $entity_id));
	} else {
		if($term && !$category) {
			$sql .= " WHERE status = 1 AND (title LIKE '%".$term."%' OR content LIKE '%".$term."')";
		} elseif($term && $category != 'all') {
            $subCategories = get_blog_parent_categories($category);
            if(!empty($subCategories)) {
                $subIds = array();
                foreach($subCategories as $cat) {
                    $subIds[] = $cat['id'];
                }
                $subIds = implode(',', $subIds);
                $sql .= " WHERE status = 1 AND (category_id = '".$category."' OR category_id IN ({$subIds})) AND (title LIKE '%".$term."%' OR content LIKE '%".$term."')";
            } else {
                $sql .= " WHERE status = 1 AND category_id = '".$category."' AND (title LIKE '%".$term."%' OR content LIKE '%".$term."')";
            }
		} elseif($term && $category == 'all') {
			$sql .= " WHERE status = 1 AND (title LIKE '%".$term."%' OR content LIKE '%".$term."')";
		} elseif($category && $category != 'all') {
			$sql .= " WHERE status = 1 AND category_id = '".$category."'";
		} else {
			$sql .= " WHERE status = '1'";
		}
		$sql .= $filter == 'featured' ? " AND featured = '1' " : '';
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$sql .= " AND (".$privacy_sql.") ";
		if($entity_type && $entity_id) {
			$entity_sql = "entity_type = '".$entity_type."' AND entity_id = ".$entity_id;
			$sql .= " AND (".$entity_sql.") ";
		}
		$sql = fire_hook("more.blogs.query.filter", $sql, array($entity_type, $entity_id));
	}
	$sql = fire_hook('users.category.filter', $sql, array($sql));
	if($filter == 'top') {
		$sql .= " ORDER BY views desc";
	} else {
		$sql .= " ORDER BY time desc";
	}
	return paginate($sql, $limit);
}

function admin_get_blogs($term = null, $limit = 10) {
	$sql = '';

	if($term) $sql .= " WHERE title LIKE '%".$term."%' OR content LIKE '%".$term."%' OR tags LIKE '%".$term."%'";
	return paginate("SELECT * FROM blogs ".$sql." ORDER BY TIME DESC", $limit);
}

function count_total_blogs() {
	$query = db()->query("SELECT * FROM blogs");
	return $query->num_rows;
}

function top_bloggers() {
	$sql = "SELECT user_id AS blogger_id, (SELECT COUNT(id) FROM blogs WHERE user_id = blogger_id) AS user_blogs FROM blogs GROUP BY blogger_id ORDER BY user_blogs DESC LIMIT 5";
	$query = db()->query($sql);
	return fetch_all($query);
}

function count_top_blogger_blogs($blogger) {
	$query = db()->query("SELECT * FROM blogs WHERE user_id = '".$blogger."'");
	return $query->num_rows;
}

function blog_add_category($val) {
	$expected = array(
		'title' => '',
        'category' => ''
	);

	/**
	 * @var $title
	 * @var $desc
	 * @var $category
	 */
	extract(array_merge($expected, $val));
	$titleSlug = "blog_category_".md5(time().serialize($val)).'_title';
	foreach($title as $langId => $t) {
		add_language_phrase($titleSlug, $t, $langId, 'blog');
	}
	$order = db()->query('SELECT id FROM blog_categories');
	$order = $order->num_rows;
	db()->query("INSERT INTO `blog_categories`(`title`,`category_order`,`parent_id`) VALUES('".$titleSlug."','".$order."','".$category."')");
	return true;
}

function save_blog_category($val, $category) {
	$expected = array(
		'title' => ''
	);

	/**
	 * @var $title
	 */
	extract(array_merge($expected, $val));
	$titleSlug = $category['title'];

	foreach($title as $langId => $t) {
		(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, ' blog') : add_language_phrase($titleSlug, $t, $langId, 'blog');
	}

	return true;
}

function get_blog_categories() {
	$query = db()->query("SELECT * FROM `blog_categories` WHERE parent_id ='0' ORDER BY `category_order` ASC");
	return fetch_all($query);
}
function get_blog_parent_categories($id) {
    $db = db()->query("SELECT * FROM `blog_categories` WHERE parent_id='{$id}' ORDER BY `category_order` ASC");
    $result = fetch_all($db);
    return $result;
}

function get_blog_category($id) {
	$query = db()->query("SELECT * FROM `blog_categories` WHERE `id`='".$id."'");
	return $query->fetch_assoc();
}

function arrange_blog($blog) {
	$category = get_blog_category($blog['category_id']);
	if($category) {
		$blog['category'] = $category;
	}
	$blog = fire_hook('blog.arrange', $blog);
	$blog['publisher'] = get_blog_publisher($blog);
	return $blog;
}

function get_blog_publisher($blog) {
	if($blog['entity_type'] == 'user') {
		$user = find_user($blog['entity_id']);
		$publisher = array(
			'id' => $user['username'],
			'name' => get_user_name($user),
			'avatar' => get_avatar(200, $user)
		);
	} else {
		$publisher = fire_hook('entity.data', array(false), array($blog['entity_type'], $blog['entity_id']));
	}
	return $publisher;
}

function delete_blog_category($id, $category) {
	delete_all_language_phrase($category['title']);
	db()->query("DELETE FROM `blog_categories` WHERE `id`='".$id."'");
	return true;
}

function update_blog_category_order($id, $order) {
	db()->query("UPDATE `blog_categories` SET `category_order`='".$order."' WHERE  `id`='".$id."'");
}

function blog_slug_exists($slug) {
	$query = db()->query("SELECT COUNT(id) FROM `blogs` WHERE  `slug`='".$slug."'");
	$result = $query->fetch_row();
	return $result[0] == 0 ? FALSE : TRUE;
}