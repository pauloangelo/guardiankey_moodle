<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * GuardianKEY authentication login - prevents user login.
 *
 * @package    auth_guardiankey
 * @copyright  Paulo Angelo Alves Resende
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_once($CFG->libdir.'/authlib.php');

require_once(dirname(__FILE__).'/guardiankey.class.php');

define('AES_256_CBC', 'aes-256-cbc');


function getGKConf() {
    $GKconfig = array(
        'agentid' => get_config('auth_guardiankey', 'hashid'),  /* ID for the agent (your system) */
        'key' => get_config('auth_guardiankey', 'key'),     /* Key in B64 to communicate with GuardianKey */
        'iv' => get_config('auth_guardiankey', 'iv'),      /* IV in B64 for the key */
        'service' => get_config('auth_guardiankey', 'service'),      /* Your service name*/
        'orgid' => get_config('auth_guardiankey', 'organizationId'),   /* Your Org identification in GuardianKey */
        'authgroupid' => get_config('auth_guardiankey', 'authGroupId'), /* A Authentication Group identification, generated by GuardianKey */
        'reverse' => get_config('auth_guardiankey', 'reverse'), /* If you will locally perform a reverse DNS resolution */
    );
    return $GKconfig;
}
    
    
function processEvent($eventData){
 
     $userid = $DB->get_record('auth_guardiankey_user_hash', array('userhash'=>$event["userhash"]));
     $user   = $DB->get_record('user', array('id'=>$userid->userid));
     $emailsubject = get_config('auth_guardiankey', 'emailsubject');
     $emailtext    = get_config('auth_guardiankey', 'emailtext');
     $emailhtml    = get_config('auth_guardiankey', 'emailhtml');
     $testmode 	   = get_config('auth_guardiankey', 'test');
     $supportaddr  = get_config('auth_guardiankey', 'supportaddr');
     $date         = userdate($eventData["generatedTime"], get_string('strftimedatetimeshort', 'langconfig'));
     $time         = userdate($eventData["generatedTime"], get_string('strftimetime', 'langconfig'));


      $emailhtml=str_replace("[IP]",$event["clientIP"],$emailhtml);
      $emailhtml=str_replace("[IP_REVERSE]",$event["clientReverse"],$emailhtml);
      $emailhtml=str_replace("[CITY]",$event["city"],$emailhtml);
      $emailhtml=str_replace("[COUNTRY]",$event["country"],$emailhtml);
      $emailhtml=str_replace("[USER_AGENT]",$event["client_ua"],$emailhtml);
      $emailhtml=str_replace("[SYSTEM]",$event["service"],$emailhtml);
      $emailhtml=str_replace("[DATE]",$date,$emailhtml);
      $emailhtml=str_replace("[TIME]",$time,$emailhtml);
      $emailhtml=str_replace("[]","",$emailhtml);
      $emailhtml=str_replace("()","",$emailhtml);
      
      $emailtext=str_replace("[IP]",$event["clientIP"],$emailtext);
      $emailtext=str_replace("[IP_REVERSE]",$event["clientReverse"],$emailtext);
      $emailtext=str_replace("[CITY]",$event["city"],$emailtext);
      $emailtext=str_replace("[COUNTRY]",$event["country"],$emailtext);
      $emailtext=str_replace("[USER_AGENT]",$event["client_ua"],$emailtext);
      $emailtext=str_replace("[SYSTEM]",$event["service"],$emailtext);
      $emailtext=str_replace("[DATE]",$date,$emailtext);
      $emailtext=str_replace("[TIME]",$time,$emailtext);
      $emailtext=str_replace("[]","",$emailtext);
      $emailtext=str_replace("()","",$emailtext);

     // Get information from table user-hash
     // Send e-mail to user

      $emailuser = new stdClass();
      $emailuser->email = $CFG->supportemail;
      $emailuser->firstname = $CFG->supportname;
      $emailuser->lastname = 'Moodle administration';
      $emailuser->username = 'moodleadmin';
      $emailuser->maildisplay = 2;
      $emailuser->alternatename = "";
      $emailuser->firstnamephonetic = "";
      $emailuser->lastnamephonetic = "";
      $emailuser->middlename = "";

  
      if($testmode != "1")
        $success = email_to_user($user, $emailuser, $emailsubject, $emailtext, $emailhtml, '', '', true);

      if(strlen(trim($supportaddr))>0){
        // Send an e-mail for the support address
        $mailer =& get_mailer();
        $result = $mailer->send($supportaddr, $emailsubject." (user $emailuser)", $emailhtml, 'quoted-printable', 1);
      }
      
      
      /*
              $message = new \core\message\message();
              $message->component = 'auth_guardiankey';
              $message->name = 'instantmessage';
              //$message->userfrom = $USER;
              $message->userto = $user;
              $message->subject = 'message subject 1';
              $message->fullmessage = 'message body';
              $message->fullmessageformat = FORMAT_MARKDOWN;
              $message->fullmessagehtml = '<p>message body</p>';
              $message->smallmessage = 'small message';
              $message->notification = '0';
              $message->contexturl = 'http://GalaxyFarFarAway.com';
              $message->contexturlname = 'Context name';
              //$message->replyto = "random@example.com";
              $content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor
              $message->set_additional_content('email', $content);
              $messageid = message_send($message);
      */
      
}
      
try {
  $GK = new guardiankey(getGKConf());
  $output=$GK->processWebHookPost();
  processEvent($output);
} catch (Exception $e) {
  echo "Something got wrong, probably the key do not match.\n";
}

