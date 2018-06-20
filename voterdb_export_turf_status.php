  <?php
/*
 * Name: voterdb_export_turf_status.php   V4.0 2/16/18
 *
 */
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_log_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_constants_turf_tbl.php";
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_path.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";

define('DD_TURF_RESULTS_FILE','nl_turf_results');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_percent
 * 
 * Return a string with a displayable percentage give a base and a count.
 * Basically, this function is for readability.
 * 
 * @param type $pe_base - The basis for the percentage.
 * @param type $pe_cnt -  The count.
 * @return type
 */
function voterdb_percent($pe_base,$pe_cnt) {
  $pe_percent = ($pe_base > 0)?round($pe_cnt/$pe_base*100,1).'%':'0%';
  return $pe_percent;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_turf_list
 * 
 * Get an array of turfs assigned in the county.
 * 
 * @param type $gt_county
 * @return type
 */
function voterdb_get_turf_list($gt_county) {
  db_set_active('nlp_voterdb');
  try {
    $gt_tselect = "SELECT * FROM {".DB_NLSTURF_TBL."} WHERE  ".
      TT_COUNTY. " = :cnty ";
    $gt_targs = array(
      ':cnty' => $gt_county,);
    $gt_result = db_query($gt_tselect,$gt_targs);
    $gt_turfs = $gt_result->fetchAll(PDO::FETCH_ASSOC);
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  db_set_active('default');
  return $gt_turfs;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_voters_in_turf
 * 
 * Get a list of the voters in a turf who have not been marked as having moved.
 * 
 * @param type $vt_turf_index
 * @return int
 */
function voterdb_get_voters_in_turf($vt_turf_index) {
  db_set_active('nlp_voterdb');
  try {
  $vt_tselect = "SELECT ".NV_VANID." FROM {".DB_NLPVOTER_GRP_TBL."} WHERE  ".
    NV_NLTURFINDEX. " = :index AND ".
    NV_VOTERSTATUS." <> 'M'";
  $vt_targs = array(
    ':index' => $vt_turf_index,
    );
  $vt_result = db_query($vt_tselect,$vt_targs);
  $vt_voters = $vt_result->fetchAll(PDO::FETCH_ASSOC);
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  db_set_active('default');
  return $vt_voters;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_who_voted
 * 
 * Check if this voter has returned a ballot.
 * 
 * @param type $wv_vanid
 * @return boolean|int
 */
function voterdb_who_voted($wv_vanid) {
  db_set_active('nlp_voterdb');
  try {
    $wv_tselect = "SELECT ".MT_DATE_INDEX." FROM {".DB_MATCHBACK_TBL."} WHERE  ".
      MT_VANID. " = :index ";
    $wv_targs = array(
      ':index' => $wv_vanid,);
    $wv_result = db_query($wv_tselect,$wv_targs);
    $wv_br = $wv_result->fetchAssoc();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return FALSE;
  }
  db_set_active('default');
  if(empty($wv_br)) {return FALSE;}  // Ballot not returned, hasn't voted.
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_voter_contacted
 * 
 * Check the database for a record of at least one face-to-face contact with 
 * this voter in this election cycle.
 * 
 * @param type $vc_vanid - ID of the voter.
 * @return boolean - TRUE if face-to0face contact.
 */
function voterdb_voter_contacted($vc_vanid) {
  $vc_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  db_set_active('nlp_voterdb');
  try {
    $vc_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $vc_query->addField('r',NC_VANID);
    $vc_query->condition(NC_VANID,$vc_vanid);
    $vc_query->condition(NC_CYCLE,$vc_cycle);
    $vc_query->condition(NC_VALUE,RA_F2F);
    $vc_query->condition(NC_TYPE,TA_Contact);
    $vc_cnt = $vc_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return FALSE;
  }
  db_set_active('default');
  $vc_f2f = $vc_cnt>0;
  return $vc_f2f;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_results
 * 
 * @param type $tr_county
 * @return string
 */
function voterdb_turf_results($tr_temp_name,$tr_county) {
  $tr_hdr = array('HD','Pct','First Name','Last Name','Voters','Voted','Percent','F2F','Voted','Percent','Turf Name');
  // Create the results file.
  $tr_results_fh = fopen($tr_temp_name,"w");
  // Write the header.
  $tr_hdrs = implode("\t", $tr_hdr);
  $tr_hdrs .= "\n";  // End for string.
  fwrite($tr_results_fh,$tr_hdrs);
  $tr_turfs = voterdb_get_turf_list($tr_county);
  if (empty($tr_turfs)) {
    drupal_set_message('No turfs','error');
    return FALSE;
  }
  //  This function can take a lot of time with a large county.  
  //  Keep track of the elapsed time in case we need to upgrade the server.
  $tr_starttime = voterdb_timer('start',0);
  set_time_limit(60);
  $tr_contact_acc = $tr_voted_acc = 0;
  // For each turf, calculate the counts and create a row for display.
  foreach ($tr_turfs as $tr_turf) {
    // Get the voters in the turf.
    $tr_turf_index = $tr_turf[TT_INDEX];
    $tr_voters = voterdb_get_voters_in_turf($tr_turf_index);
    if (empty($tr_voters)) {continue;}
    // For each voter determine if a vote was recorded and if there was a 
    // face-to-face contact.
    $tr_voter_cnt = $tr_voted_cnt = $tr_ftf = $tr_ftfvoted = 0;
    foreach ($tr_voters as $tr_vtr) {
      // Get the status of ballot returned (indicates voted).
      $tr_vanid = $tr_vtr[NV_VANID];
      $tr_votedstart = voterdb_timer('start',0);
      $tr_voted = voterdb_who_voted($tr_vanid);
      $tr_votedtime = voterdb_timer('end',$tr_votedstart);
      $tr_voted_acc += $tr_votedtime;
      // Add to the count of voters and the count of those who voted.
      $tr_voter_cnt++;
      if ($tr_voted) {$tr_voted_cnt++;}
      // Now check if this voter was contaced face-to-face.
      $tr_contactstart = voterdb_timer('start',0);
      $tr_f2f_contact = voterdb_voter_contacted($tr_vanid);
      $tr_contacttime = voterdb_timer('end',$tr_contactstart);
      $tr_contact_acc += $tr_contacttime;
      // Add to the count of contacted voters.
      if ($tr_f2f_contact) {
        $tr_ftf++;
        if ($tr_voted) {$tr_ftfvoted++;}
      }
    }
    // Create the display of counts for this turf.
    $tr_fname =  str_replace("&#039;", "'", $tr_turf[TT_NLFNAME]); // fix the apostrophies.
    $tr_lname =  str_replace("&#039;", "'", $tr_turf[TT_NLLNAME]);
    $tr_voted_pc = voterdb_percent($tr_voter_cnt,$tr_voted_cnt);
    $tr_ftf_pc = voterdb_percent($tr_ftf,$tr_ftfvoted);
    $tr_turf_rec = $tr_turf[TT_HD]."\t";
    $tr_turf_rec .= $tr_turf[TT_PCT]."\t";
    $tr_turf_rec .= $tr_fname."\t";
    $tr_turf_rec .= $tr_lname."\t";
    $tr_turf_rec .= $tr_voter_cnt."\t";
    $tr_turf_rec .= $tr_voted_cnt."\t";
    $tr_turf_rec .= $tr_voted_pc."\t";
    $tr_turf_rec .= $tr_ftf."\t";
    $tr_turf_rec .= $tr_ftfvoted."\t";
    $tr_turf_rec .= $tr_ftf_pc."\t";
    $tr_turf_rec .= $tr_turf[TT_NAME]."\t\n";
    fwrite($tr_results_fh,$tr_turf_rec);
  }
  fclose($tr_results_fh);
  // Display the elapsed time for this export function.
  $tr_totaltime = voterdb_timer('end',$tr_starttime);
  $msg = 'The turf results were created in ' .round($tr_totaltime,2). ' seconds.';
  drupal_set_message($msg,'status');
  $msg1 = 'Voted time: '.round($tr_voted_acc,1).' Contact time: '.round($tr_contact_acc,1);
  drupal_set_message($msg1,'status');
  return TRUE;
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_turf_status
 *
 * Create a file with the status of NL canvassing for each turf.  The turf
 * display includes a line for each turf with the following information: HD,
 * Pct, Name of the NL, and a status of voters and face-to-face contacts.  
 * The status includes the total count, the count of those that voted, and 
 * the percentage who voted.  The file hame of the turf is also included as
 * this is typically recognizable by the coordinator and helps if there 
 * is more than one turf for an NL.
 * 
 * @return string - displayable HTML with a link to the file for download by the user.
 */
function voterdb_export_turf_status() {
  $dd_button_obj = new NlpButton;
  $dd_button_obj->setStyle();
  $form_state = array();
  if(!voterdb_get_group($form_state)) {return "";}
  $dd_county = $form_state['voterdb']['county'];
  $dd_banner = voterdb_build_banner ($dd_county);
  $output = $dd_banner;
  // Create file name for results tab-delimited file.
  $dd_temp_dir = 'public://temp';
  file_prepare_directory($dd_temp_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
  // Use a date in the name to make the file unique., just in case two people 
  // are doing an export at the same time.
  $dd_cdate = date('Y-m-d-H-i-s',time());
  // Open a temp file for receiving the records.
  $dd_base_uri = $dd_temp_dir.'/'.DD_TURF_RESULTS_FILE.'-'.$dd_county.'-'.$dd_cdate;
  $dd_temp_uri = $dd_base_uri.'.txt';
  // Create a managed file for temporary use.  Drupal will delete after 6 hours.
  $dd_file_object = file_save_data('', $dd_temp_uri, FILE_EXISTS_REPLACE);
  $dd_file_object->status = 0;
  file_save($dd_file_object);
  // Create the list of turfs with status.
  $dd_turf_results = voterdb_turf_results($dd_temp_uri,$dd_county);
  // Create the display with the link to the file.

  $dd_browser_obj = new GetBrowser();
  $dd_browser = $dd_browser_obj->getBrowser();
  $dd_browser_hint = $dd_browser['hint'];
  //voterdb_debug_msg('hint', $dd_browser_hint);
  
  if($dd_turf_results) {
    $dd_url = file_create_url($dd_temp_uri);
    $output .= "<h2>A list of turfs with voting result counts.</h2>";
    $output .= '<p id="hint1"> <a href="'.$dd_url.'">Right-click to download voting results for each turf. <span>Remember to right-click the link and then select "'.$dd_browser_hint.'".</span> </a></p>';
    $output .= '<a href="nlpadmin?County='.$dd_county.'" class="button ">Return to Admin page >></a>';
    }
  return $output;
}
