<?php
function save($app) {
	$val = input('val', array());
	$val['group-shortcuts-menu'] = isset($val['group-shortcuts-menu']) ? perfectSerialize($val['group-shortcuts-menu']) : perfectSerialize(array());
	$val['page-shortcuts-menu'] = isset($val['page-shortcuts-menu']) ? perfectSerialize($val['page-shortcuts-menu']) : perfectSerialize(array());
	$val['game-shortcuts-menu'] = isset($val['game-shortcuts-menu']) ? perfectSerialize($val['game-shortcuts-menu']) : perfectSerialize(array());
	save_privacy_settings($val);
	return view('layouts/shortcuts');
}
