<?php
function play_video_pager($app) {
	$link = input('link');
	$vid = input('id');
	$photo = input('photo');
	return view("video/embed", array('link' => video_url($link), 'videoid' => $vid, 'photo' => $photo));
}
 