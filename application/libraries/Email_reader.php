<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
class Email_reader {
 
	
	function __construct() {
		/* connect to gmail */
$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = 'pms@isodeveloper.com';
$password = '9iLyS1Q96R';

/* try to connect */
$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());

/* grab emails */
$emails = imap_search($inbox,'ALL');

/* if emails are returned, cycle through each... */
if($emails) {
	
	/* begin output var */
	$output = '';
	
	/* put the newest emails on top */
	rsort($emails);
	
	/* for every email... */
	foreach($emails as $email_number) {
		
		/* get information specific to this email */
		$overview = imap_fetch_overview($inbox,$email_number,0);
		$info=imap_headerinfo($inbox,$email_number,0);
		$message = imap_fetchbody($inbox,$email_number,1);
		/* output the email header information */
		$output.= '<div class="toggler '.($overview[0]->seen ? 'read' : 'unread').'">';
		$output.= '<span class="subject">'.$overview[0]->subject.'</span> ';
		$output.= '<span class="from">'.$overview[0]->from.'</span>';
		$output.= '<span class="date">on '.$overview[0]->date.'</span>';
		$output.= '</div>';
		$message = imap_qprint($message);
		$startsAt = strpos($message, "============ REPLY BETWEEN THESE LINES ==================") + strlen("============ REPLY BETWEEN THESE LINES ==================");
		$endsAt = strpos($message, "=====================================================", $startsAt);
		$result = substr($message, $startsAt, $endsAt - $startsAt);
		
		echo $info->from[0]->mailbox.'@'.$info->from[0]->host;
		
		preg_match('/: (.*?) -/',$overview[0]->subject, $display);
		echo $display[1];
		/* output the email body */
		$output.= '<div class="body">'.$message.'</div>';
	}
	
	
} 

/* close the connection */
imap_close($inbox);
	}
 
 
}