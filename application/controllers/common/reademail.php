<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
/**
 * class for perfoming all Signup related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Reademail extends MY_Controller {

	/**
	 * constructor method
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
		/* connect to gmail */
		$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
		$username = 'pms@isodeveloper.com';
		$password = '9iLyS1Q96R';

		/* try to connect */
		$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());

		/* grab emails */
		$emails = imap_search($inbox, 'UNSEEN');

		/* if emails are returned, cycle through each... */
		if ($emails) {

			/* begin output var */
			$output = '';

			/* put the newest emails on top */
			rsort($emails);

			/* for every email... */
			foreach ($emails as $email_number)
			{
				/* get information specific to this email */
				$overview = imap_fetch_overview($inbox, $email_number, 0);
				$info = imap_headerinfo($inbox, $email_number, 0);
				$message = imap_fetchbody($inbox, $email_number, 1);
				$message = imap_qprint($message);
				$startsAt = strpos($message, "============ REPLY BETWEEN THESE LINES ==================") + strlen("============ REPLY BETWEEN THESE LINES ==================");
				$endsAt = strpos($message, "=====================================================", $startsAt);
				$result = substr($message, $startsAt, $endsAt - $startsAt);

				$mail = $info->from[0]->mailbox . '@' . $info->from[0]->host;
				//echo $result.'<br>';
				preg_match('/: (.*?) -/', $overview[0]->subject, $display);
				//echo $display[1].'<br>';

				$this->db->select('projects_id');
				$this->db->from('projects');
				$this->db->where('projects_title', $display[1]);
				$query = $this->db->get();
				$_POST['messages_project_id'] = $query -> row('projects_id');
				$_POST['messages_text'] = $result;

				$this->db->select('team_profile_id,team_profile_avatar_filename,team_profile_full_name');
				$this->db->from('team_profile');
				$this->db->where('team_profile_email', $mail);
				$query = $this->db-> get();
				if (urlencode($result) ==  "%0D%0A" or !$query->row('projects_id'))
				{
					$data = array(
						'failed_mails_subject' => $overview[0]->subject ,
   						'failed_mails_text' => $message ,
   						'failed_mails_from' => $mail
					);
					$this->db->insert('failed_mails', $data);
					$id=$this->db->insert_id();

					$headers = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
					$headers .= 'From: PMSSystem <pms@isodeveloper.com>' . '\r\n';
					$content = 'Failed mail chat message:<br>' . $result . '<br>Sent by ' . $mail . '<br>On ' . $overview[0] -> date . '<br>Subject:' . $overview[0] -> subject . '<br>Link for manual insert: http://pms.isodeveloper.com/admin/messagecreator/'.$id;

					mail('david@isodevelopers.com', 'Failed mail chat message', $content, $headers);
					mail('mateusz.orawczak@gmail.com', 'Failed mail chat message', $content, $headers);
				}
				else
				{
					if (substr($overview[0]->subject, strrpos($overview[0]->subject, ' ') + 1) == 'Team')
					{
						
						$_POST['messages_by_id'] = $query -> row('team_profile_id');
						$_POST['messages_by'] = 'team';
						$_POST['myname'] = str_replace("'", "", $query -> row('team_profile_full_name'));
						$_POST['myavatar'] = str_replace("'", "", $query -> row('team_profile_avatar_filename'));
						$this->team_messages_model->addMessage();
					}
					else {
						
					
					if ($query -> row('team_profile_id')) {
						$_POST['messages_by_id'] = $query -> row('team_profile_id');
						$_POST['messages_by'] = 'team';
						$_POST['myname'] = str_replace("'", "", $query -> row('team_profile_full_name'));
						$_POST['myavatar'] = str_replace("'", "", $query -> row('team_profile_avatar_filename'));
						$this->messages_model->addMessage();
					} else {
						$this->db->select('client_users_id');
						$this->db->from('client_users');
						$this->db->where('client_users_email', $mail);
						$query = $this->db->get();
						if ($query -> row('client_users_id')) {
							$_POST['messages_by_id'] = $query->row('client_users_id');
							$_POST['messages_by'] = 'client';
							$this->messages_model->addMessageClient();
						}
					}
				}
					

				}

			}

		}
		/* close the connection */
		imap_close($inbox);
	}

	/**
	 * This is our re-routing function and is the inital function called
	 *
	 *
	 */
	public function index() {

	}

}

/* End of file signup.php */
/* Location: ./application/controllers/common/reademail.php */
