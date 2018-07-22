  <?php
/*
 * Name: voterdb_export_nls_status.php   V4.2 7/16/18
 *
 */
//require_once "voterdb_constants_rr_tbl.php";
//require_once "voterdb_constants_log_tbl.php";
//require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
//require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_group.php";
//require_once "voterdb_path.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_get_browser.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_nlreports_nlp.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\GetBrowser;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpReports;

define('DD_NLS_STATUS_FILE','nls-status');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_participating_counties  
 * 
 * Get a list of county names for which there are turfs assigned.  This is
 * the list of active counties for this cycle.
 * 
 * @return array - array of county names.
 */
function voterdb_get_participating_counties() {
  // Get the list of county names with turfs.
  db_set_active('nlp_voterdb');
  try {
    $pc_query = db_select(DB_NLPVOTER_GRP_TBL, 'r');
    $pc_query->addField('r', NV_COUNTY);
    $pc_query->distinct();
    $pc_query->orderBy(NV_COUNTY); 
    $pc_result = $pc_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  $pc_county_list = $pc_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  $pc_names = array();
  foreach ($pc_county_list as $pc_name) {
    $pc_names[] = $pc_name[NV_COUNTY];
  } 
  return $pc_names;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_voter_count
 * 
 * Count the number of voters assigned to an NL (includes all their turfs).
 * 
 * @param type $vc_mcid
 * @return int
 */
function voterdb_voter_count($vc_mcid) {
  db_set_active('nlp_voterdb');
  try {
    $vc_vquery = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $vc_vquery->addField('g',NV_VANID);
    $vc_vquery->condition(NV_MCID,$vc_mcid);
    $vc_vquery->condition(NV_VOTERSTATUS,'A');
    $vc_vc = $vc_vquery->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $vc_vc;
}
 
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_progress
 * 
 * Create an associate array with the progress of the NL to make contact with
 * the voters on the turf, the progress on making face-to-face contact and 
 * a judgement that they are done.
 * 
 * @param type $gp_mcid
 * @return string|int
 */
function voterdb_get_progress($gp_mcid,$gp_counts) {
  $gp_progress['attempts'] = '';  // Voter contact attempts.
  $gp_progress['contacts'] = ''; // Voter contacts.
  $gp_progress['done'] = '';  // Every voter contacted. 
  // Countnthe number of voters assigned to this NL.
  $gp_voter_cnt = voterdb_voter_count($gp_mcid);
  //  Now get the contact attempt count and the successful f2f count.
  $gp_ccnt = $gp_acnt = 0;
  if(!empty($gp_counts[$gp_mcid]['attempts'])) {
    $gp_acnt = $gp_counts[$gp_mcid]['attempts'];
    $gp_ccnt = $gp_counts[$gp_mcid]['contacts'];
  }
  // Return the strings for display of voter contact attempts and actual f2f contacts.
  $gp_progress['attempts'] = $gp_acnt.'/'.$gp_voter_cnt;
  $gp_progress['contacts'] = $gp_ccnt.'/'.$gp_voter_cnt;
  if($gp_ccnt == $gp_voter_cnt AND $gp_voter_cnt != 0) {
    $gp_progress['done'] = 'Done';  // Every voter was contacted.
  }
  return $gp_progress;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_create_status
 * 
 * Fill the file with the NL status information for the selected county or
 * counties.
 * 
 * @param type $an_all -  report all the oarticipating counties.
 * @param type $an_county
 * @param type $dd_list_uri
 * @return type
 */
function voterdb_create_status($an_all,$an_county,$dd_list_uri) {
  $an_hdr = array('MCID','County','HD','Pct','First Name','Last Name','Email','Phone',
      'Signed up','Login Date','Reported','Attempts','Contacts',
      'Email (formatted)');
  // Create the status file.
  $an_list_fh = fopen($dd_list_uri,"w");
  // Write the header.
  $an_hdrs = implode("\t", $an_hdr);
  $an_hdrs .= "\n";  // End for string.
  fwrite($an_list_fh,$an_hdrs);
  // All participating counties or just one.
  if ($an_all) {
    $an_counties = voterdb_get_participating_counties();
  } else {
    $an_counties = array($an_county);
  }
  $nlObj = new NlpNls();
  $nlReportsObj = new NlpReports();
  
  $an_starttime = voterdb_timer('start',0);
  set_time_limit(60);
  foreach ($an_counties as $an_county) {
    // Get the list of all the NLs in a county.
    
    $an_nl_list = $nlObj->getCountyNls($an_county);
    //$an_nl_list = voterdb_get_county_nls($an_county);
    if(!$an_nl_list) {return NULL;}
    
    $an_counts = $nlReportsObj->getCountyReportCounts($an_county);
    //$an_counts = voterdb_get_report_counts($an_county);
    foreach ($an_nl_list as $an_nl) {
      $an_progress = voterdb_get_progress($an_nl['mcid'],$an_counts);
      // restore the apostrophies.
      $an_nickname =  str_replace("&#039;", "'", $an_nl['nickname']); 
      $an_lname =  str_replace("&#039;", "'", $an_nl['lastName']);
      $an_email = $an_nl['email'];
      if(empty($an_email)) {
        $an_formatted_email = '';
      } else {
        $an_formatted_email = $an_nickname.' '.$an_lname. '<'.$an_email.'>';
      }
      $an_nl_row = array();
      $an_nl_row[] = $an_nl['mcid'];
      $an_nl_row[] = $an_nl['county'];
      $an_nl_row[] = $an_nl['hd'];
      $an_nl_row[] = $an_nl['pct'];
      $an_nl_row[] = $an_nickname;
      $an_nl_row[] = $an_lname;
      $an_nl_row[] = $an_email;
      $an_nl_row[] = $an_nl['phone'];
      $an_nl_row[] = $an_nl['nlSignup'];
      $an_nl_row[] = $an_nl['loginDate'];
      $an_nl_row[] = $an_nl['resultsReported'];
      $an_nl_row[] = $an_progress['attempts'];
      $an_nl_row[] = $an_progress['contacts'];
      $an_nl_row[] = $an_formatted_email;
      $an_nl_rec = implode("\t", $an_nl_row);
      $an_nl_rec .= "\n";  // End for string.
      fwrite($an_list_fh,$an_nl_rec);   
    } 
  }
  $an_totaltime = voterdb_timer('end',$an_starttime);
  $msg = 'The NL status report was created in ' .round($an_totaltime,2). ' seconds.';
  drupal_set_message($msg,'status');
  fclose($an_list_fh);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_nls_status
 *
 * Create a file is a tab delimited list of NLs with the status for this
 * election.   This permits the user of Excel to sort the list and select
 * NLs for additional attention.
 *  
 * @return string - HTML for display with the links to the files.
 */
function voterdb_export_nls_status() {
  $dd_button_obj = new NlpButton();
  $dd_button_obj->setStyle();
  $form_state = array();
  if(!voterdb_get_group($form_state)) {return "";}
  $dd_county = $form_state['voterdb']['county'];
  $dd_all = (isset($form_state['voterdb']['ALL']))? TRUE : FALSE;
  $dd_banner = voterdb_build_banner ($dd_county);
  $output = $dd_banner;
  // Create temp files for the statuslist.
  // The file will be managed files to be deleted by Drupal after 6 hours.
  $dd_temp_dir = 'public://temp';
  // Use a date in the name to make the file unique., just in case two people 
  // are doing an export at the same time.
  $dd_cdate = date('Y-m-d-H-i-s',time());
  // Create the status file.
  $dd_list_uri = $dd_temp_dir.'/'.DD_NLS_STATUS_FILE.'-'.$dd_county.'-'.$dd_cdate.'.txt';
  $dd_list_object = file_save_data('', $dd_list_uri, FILE_EXISTS_REPLACE);
  $dd_list_object->status = 0;
  file_save($dd_list_object);
  // Now fill them with information.
  voterdb_create_status($dd_all,$dd_county,$dd_list_uri);
  // Provide the external link to the user so the file can be downloaded.
  $dd_list_url = file_create_url($dd_list_uri);
  $dd_browser_obj = new GetBrowser();
  $dd_browser = $dd_browser_obj->getBrowser();
  $dd_browser_hint = $dd_browser['hint'];
  $output .= "\n".'<div style="width:450px;"> <fieldset><legend><span style="font-size:large; color:#af2108; font_weight:bold;">NL status report</span></legend>';
  $output .= "<p>This file contains a list of NLs that have a turf.  It 
    provides the email to communicate with the group with a bulk email.  
    And, it contains the current status of activity by the NL for this 
    election cycle</p>";
  //$output .= '<p> <a href="'.$dd_list_url.'">Right click to download NL status file</a></p>';
  $output .= '<p id="hint1"> <a href="'.$dd_list_url.'">Right-click to download voting results for each turf. <span>Remember to right-click the link and then select "'.$dd_browser_hint.'".</span> </a></p>';
  
  $output .= "\n".'</fieldset></div>';
  $output .= '<p><a href="nlpadmin?County='.$dd_county.'" class="button ">Return to Admin page >></a></p>';
  return $output;
}
