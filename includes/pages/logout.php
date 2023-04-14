<?php
function logout_pager($app) {
	logout_user();
	fire_hook('logout.user.session');
	redirect(url());
}