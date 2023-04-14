<?php
function submit_contact($val) {
	$from = config('email-from-address');
	$to = config('email-from-address');
	$subject = sanitizeText($val['subject']);
	$message = '<html><title>'.$subject.'</title><body><p>From: '.sanitizeText($val['name']).' | '.sanitizeText($val['email']).'</p><p>'.sanitizeText($val['message']).'</p></body></html>';
	$headers = "MIME-Version: 1.0"."\r\n"."Content-type:text/html;charset=UTF-8"."\r\n".'From: <'.$from.'>'."\r\n";
	try {
		mailer()->setAddress($to)->setSubject($subject)->setMessage($message)->send();
	} catch(Exception $e) {
		try {
			mail($to, $subject, $message, $headers);
		} catch(Exception $e) {
			//exit($e->getMessage());
		}
	}
	return true;
}