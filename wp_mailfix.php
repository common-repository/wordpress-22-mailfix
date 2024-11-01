<?php
/*
Plugin Name: Wordpress 2.2 Mailfix
Plugin URI: http://pn.xn--strbe-mva.de/
Description: Fixes problems sending HTML-mails - Only for WP 2.2.x! Do not use it together with other mail-plugins.
Author: Moritz Str&uuml;be
Version: 1.3
License: GPL
Author URI: http://xn--strbe-mva.de
Min WP Version: 2.2
*/

//This is a adjusted version of the original wp_mail
if ( (!function_exists('wp_mail'))/* && (get_option('db_version') > 4772)  && (get_option('db_version') < 6124)*/) :
function wp_mail($to, $subject, $message, $headers = '') {
	global $phpmailer;

	
	if ( !is_object( $phpmailer ) ) {
		require_once(ABSPATH . WPINC . '/class-phpmailer.php');
		require_once(ABSPATH . WPINC . '/class-smtp.php');
		$phpmailer = new PHPMailer();
	}

	$mail = compact('to', 'subject', 'message', 'headers');
	$mail = apply_filters('wp_mail', $mail);
	extract($mail);

	if ( $headers == '' ) {
		$headers = "MIME-Version: 1.0\n" .
			"From: " . apply_filters('wp_mail_from', "wordpress@" . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']))) . "\n" . 
			"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	}

	$phpmailer->ClearAddresses();
	$phpmailer->ClearCCs();
	$phpmailer->ClearBCCs();
	$phpmailer->ClearReplyTos();
	$phpmailer->ClearAllRecipients();
	$phpmailer->ClearCustomHeaders();

	$phpmailer->FromName = "WordPress";
	$phpmailer->AddAddress("$to", "");
	$phpmailer->Subject = $subject;
	$phpmailer->Body    = $message;
	$phpmailer->IsHTML(false);
	$phpmailer->IsMail(); // set mailer to use php mail()

	do_action_ref_array('phpmailer_init', array(&$phpmailer));

	$mailheaders = (array) explode( "\n", $headers );
	foreach ( $mailheaders as $line ) {
		$header = explode( ":", $line );
		switch (strtolower(trim( $header[0] )) ) {
			case 'from':
				$from = trim( str_replace( '"', '', $header[1] ) );
				if ( strpos( $from, '<' ) ) {
					$phpmailer->FromName = str_replace( '"', '', substr( $header[1], 0, strpos( $header[1], '<' ) - 1 ) );
					$from = trim( substr( $from, strpos( $from, '<' ) + 1 ) );
					$from = str_replace( '>', '', $from );
				} else {
					$phpmailer->FromName = $from;
				}
				$phpmailer->From = trim( $from );
				break;
			case 'content-type':
				$contentparam = explode(' ', trim($header[1]));
				$phpmailer->ContentType = substr(trim($contentparam[0]), 0, -1); //Remove ;
				if(!(strpos($contentparam[1], 'charset') === false)){
					$charset = explode('=', $contentparam[1]);
					$phpmailer->CharSet = str_replace( '"', '', $charset[1]);
				}
				break;
			default:
				if ( $line != '' && $header[0] != 'MIME-Version' && $header[0] != 'Content-Type' )
					$phpmailer->AddCustomHeader( $line );
				break;
		}
	}

	$result = @$phpmailer->Send();

	return $result;
}
endif;



?>