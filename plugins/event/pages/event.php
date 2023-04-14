<?php
if(is_loggedIn()) get_menu('dashboard-main-menu', 'events')->setActive();
function events_pager($app) {
	$app->setTitle(lang('event::events'));
	$type = input('type', 'upcoming');
	$parameter = trim(input('param'));
	$app->eventType = $type;
	$events = get_events($type, input('term'), 10, false, input('category'), 'user', '', $parameter);
	if($type == 'birthdays') {
		return _render(view('event::birthdays', array('events' => get_events($type, input('term'), 10, false, input('category')))), $type);
	}
	return _render(view('event::browse', array('events' => $events)), $type);
}

function subscribers_pager($app) {
	$app->setTitle(lang('event::event-subscribers'));
	return $app->render(view('event::subscribers'));
}

function event_delete_pager($app) {
	$eventId = segment(2);
	$event = find_event($eventId);
	if(!is_event_admin($event)) return redirect_to_pager('events');

	delete_event($event);
	if(input('admin')) redirect_back();
	return redirect_to_pager('events');
}

function events_run_pager($app) {
	$type = input('type', 'event');
	$when = input('when', 'on');
	$day = date('j');
	$month = date('n');
	$year = date('Y');
	if($when == 'before') {
		$day = $day + 1;
	}

	switch($type) {
		case 'event':
			$query = db()->query("SELECT event_id,user_id FROM events WHERE event_day='{$day}' AND event_month='{$month}' AND event_year='{$year}'");
			while($event = $query->fetch_assoc()) {
				$eventId = $event['event_id'];

				$q = db()->query("SELECT user_id FROM event_invites WHERE event_id='{$eventId}' AND (rsvp = '1' OR rsvp = '2')");
				while($user = $q->fetch_assoc()) {
					$userid = $user['user_id'];
					if($userid != get_userid()) send_notification($userid, 'event.events', $eventId, array('when' => $when), null, null, $event['user_id']);
				}
			}
		break;
		case 'birthday':
			$month = event_get_month_name($month);
			$query = db()->query("SELECT id FROM users WHERE birth_day='{$day}' AND birth_month='{$month}'");
			while($user = $query->fetch_assoc()) {
				$userid = $user['id'];
				$friends = get_friends($userid);
				foreach($friends as $friend) {
					if($friend != get_userid()) send_notification($friend, 'event.birthday', $when, array(), null, null, $userid);
				}
			}
		break;
	}

	if(input('web')) return redirect_back();
}

function create_event_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang('event::create-event'));
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$validator = validator($val, array(
			'category' => 'required',
			'title' => 'required',
			'location' => 'required',
			'start_time' => 'required|datetime',
			'end_time' => 'required|datetime',
		));

		if(validation_passes()) {
			$event_id = create_event($val);
			if($event_id) {
				$event = find_event($event_id);
				$uploader = new Uploader(input_file('cover_art'), 'image');
				$uploader->setPath('event/'.$event['event_id'].'/'.date('Y').'/photos/cover/');
				if($uploader->passed()) {
					$original = $uploader->resize($uploader->getWidth(), null, 'fill', 'any')->result();
					$uploader->setPath('event/'.$event['event_id'].'/'.date('Y').'/photos/cover/resized/');
					//$cover = $uploader->crop(0, 0, $uploader->getWidth(), ($uploader->getWidth() * 0.4))->result();
					$cover = $uploader->resize()->result();
					update_event_details(array('event_cover' => $original, 'event_cover_resized' => $cover), $event['event_id']);
				}
				$status = 1;
				$message = lang('event::event-create-success');
				$redirect_url = event_url(null, $event);
			} else {
				$message = lang('event::event-create-error');
			}
		} else {
			$message = validation_first();
		}
		if(input('ajax')) {
			$result = array(
				'status' => (int) $status,
				'message' => (string) $message,
				'redirect_url' => (string) $redirect_url,
			);
			$response = json_encode($result);
			return $response;
		}
	}
	if($redirect_url) {
		return redirect($redirect_url);
	}
	return _render(view('event::create', array('message' => $message)), 'create', true);
}

/**
 * Help function to render page with its layout
 */
function _render($content, $type = "all", $fullWidth = false) {

	return app()->render(view("event::layout", array('content' => $content, 'type' => $type, 'fullWidth' => $fullWidth)));
}

function event_calender_pager($app) {
	$type = segment(2, 'upcoming');
	return _render(view('event::calender', array('events' => get_events($type, input('term'), 10, false, input('category')))), $type);
}