<?php
function find_mentions($text) {
	$mention_field = "id,username,first_name,last_name,avatar";
	$mention_field = fire_hook('more.users.fields', $mention_field, array($mention_field));
	$sql = "SELECT {$mention_field} FROM `users` WHERE ";
	$where_clause = " username LIKE '%{$text}%' OR first_name LIKE '%{$text}%' OR last_name LIKE '%{$text}%'";
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, false, false));
	$order_by = " LIMIT 5";
	$sql .= $where_clause.$order_by;
	$query = db()->query($sql);
	return fetch_all($query);
}

function mention_parse($text) {
	if(empty($text)) return false;
	preg_match_all('/(^|\s)(@\w+)/', $text, $matches);
	$mentions = array_map('trim', $matches[0]);
	return $mentions;
}