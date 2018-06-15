<?php
/*
 * Name: voterdb_blocked_report.php   V4.0  2/19/18
 */
require_once "voterdb_constants_bounce_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_delete_notification
 * 
 * Delete the record of a bounced email report.
 * 
 * @param type $dn_report_id
 */
function voterdb_delete_notification($dn_report_id) {
  db_delete(DB_BOUNCE_REPORT_NOTIFY_TBL)
    ->condition(BA_REPORT_ID, $dn_report_id)
    ->execute();
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_bounced_status
 * 
 * The bounce non-delivery table is maintained by the bounce module.  This
 * function get the record kept by the bounce module.
 * 
 * @param type $bs_report_id - unique id of a bounce report.
 * @return associated array - The bounce record.
 */
function voterdb_bounced_status($bs_report_id) {
 try {
    $br_dquery = db_select(DB_BOUNCE_NON_DELIVERY_TBL, 'n');
    $br_dquery->addField('n',BN_REPORT_ID);
    $br_dquery->condition(BN_REPORT_ID,$bs_report_id);
    $br_dresult = $br_dquery->execute();
  }
  catch (Exception $e) {
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return '';
  }
  $br_bounced = $br_dresult->fetchAssoc();
  return $br_bounced;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_blocked_status
 * 
 * The bounce block table is created and maintained by the bounce module.  
 * This function will query to the table for an entry for the email.  The
 * entry will exist if the bounce module believes the email is permanently
 * undeliverable.   The bounce module will also be blocking NLP Services from
 * sending to the address.
 * 
 * @param type $bs_remail - email address to test if blocked.
 * @return string
 */
function voterdb_blocked_status($bs_remail) {
 try {
    $br_dquery = db_select(DB_BOUNCE_BLOCK_TBL, 'n');
    $br_dquery->addField('n',BB_BLOCKED_ID);
    $br_dquery->condition(BB_MAIL,$bs_remail);
    $br_dresult = $br_dquery->execute();
  }
  catch (Exception $e) {
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return '';
  }
  $br_blocked = $br_dresult->fetchAssoc();
  $br_bstat = ($br_blocked)? 'Blocked':''; 
  return $br_bstat;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_blocked_report
 * 
 *
 * @return $output - display.
 */
function voterdb_blocked_report() {
  $dd_button_obj = new NlpButton;
  $dd_button_obj->setStyle();
  $form_state = array();
  if(!voterdb_get_group($form_state)) {return;}
  $br_county = $form_state['voterdb']['county'];
  $br_banner = voterdb_build_banner ($br_county);
  $output = $br_banner;
  // get the reported non-delivery emails.
  try {
    $br_rquery = db_select(DB_BOUNCE_REPORT_NOTIFY_TBL, 'n');
    $br_rquery->orderBy(BA_COUNTY)->orderBy(BA_NLEMAIL);
    $br_rquery->fields('n');
    $br_bresult = $br_rquery->execute();
    }
  catch (Exception $e) {
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return $output;
  }
  $cd_out = '<table style="white-space: nowrap; width:600px;">';
  $cd_out .= '<thead><tr>
    <th style="text-align: left; width:150px;">County</th>
    <th style="width:100px;">Sender</th>
    <th style="width:100px;">NL (Bounced email)</th>
    <th style="width:50px;">Status</th>
    <th style="width:50px;">Date</th>
    <th style="width:50px;">Code</th>
    <th style="width:100px;">Description</th>
    </tr></thead><tbody>';
  $br_cnt = 0;
  do {
    $br_bouncer = $br_bresult->fetchAssoc();
    if (!$br_bouncer) {break;}
    $br_scounty = $br_bouncer[BA_COUNTY];
    $br_report_id = $br_bouncer[BA_REPORT_ID];
    $br_desc = $br_bouncer[BA_DESCRIPTION];
    $br_code = $br_bouncer[BA_CODE];
    $br_rfn = $br_bouncer[BA_NLFNAME];
    $br_rln = $br_bouncer[BA_NLLNAME]; 
    $br_remail = $br_bouncer[BA_NLEMAIL]; 
    $br_sfn = $br_bouncer[BA_SFNAME];
    $br_sln = $br_bouncer[BA_SLNAME];
    $br_semail = $br_bouncer[BA_SEMAIL];
    $br_bstat = voterdb_blocked_status($br_remail);
    $br_brpt = voterdb_bounced_status($br_report_id);
    // If both the bounced status and the blocked status exist, then the admin
    // has deleted them to restore the email.
    if ($br_bstat != '' AND !$br_brpt) {
      // Delete the notification record and stop reporting this email.
      voterdb_delete_notification($br_report_id);
    } else {
      // Report this eamil as having delivery problems.
      $br_cnt++;
      $cd_nl = $br_remail.'<br>'.$br_rfn.' '.$br_rln;
      $cd_coodinator = $br_semail.'<br>'.$br_sfn.' '.$br_sln;
      $cd_out .= '<tr>
        <td style="text-align: left;">'.$br_bouncer['county'].'</td>'.
        '<td>'.$cd_coodinator.'</td>'.
        '<td>'.$cd_nl.'</td>'.
        '<td>'.$br_bstat.'</td>'.
        '<td>'.$br_bouncer[BA_DATE].'</td>'.
        '<td>'.$br_bouncer[BA_CODE].'</td>'.
        '<td>'.$br_bouncer[BA_DESCRIPTION].'</td>'.
        '</tr>';
    }
  } while (TRUE);
  $cd_out .= '</tbody></table>';
  $output .= $cd_out;
  $output .= '<a href="nlpadmin?County='.$br_county.'" class="button ">Return to Admin page >></a>';
  return $output;
} 