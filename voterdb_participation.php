<?php
/*
 * Name: voterdb_participation.php   V4.0 12/27/17
 */
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_log_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_constants_bc_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_turf_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_path.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_survey_response_counts
 * 
 * Query the database for all the responses to the current survey question and
 * count occurrences of each response.
 * 
 * @return associative array of counts for responses.
 */
function voterdb_get_survey_response_counts() {
  $sq_response_list = variable_get('voterdb_survey_responses', '');
  $sq_responses = explode(',',$sq_response_list);
  $sq_counts = array();
  foreach ($sq_responses as $sq_response) {
    $sq_counts[$sq_response] = 0;
  }
  $sq_cycle = variable_get('voterdb_ecycle', 'yyyy-mm-t');
  $sq_title = variable_get('voterdb_survey_title', '');
  db_set_active('nlp_voterdb');
  try {
    $sq_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $sq_query->addField('r',NC_VALUE);
    $sq_query->condition(NC_ACTIVE,TRUE);
    $sq_query->condition(NC_CYCLE,$sq_cycle);
    $sq_query->condition(NC_TYPE,TA_SURVEY);
    $sq_query->condition(NC_TEXT,$sq_title);

    $sq_result = $sq_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  do {
  $sq_report = $sq_result->fetchAssoc();
  if(empty($sq_report)) {break;}
    $sq_response = $sq_report[NC_VALUE];
    if(!isset($sq_counts[$sq_response])) {
      $sq_counts[$sq_response] = 1;
    } else {
      $sq_counts[$sq_response]++;
    }
  } while (TRUE);
  return $sq_counts;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_survey_title
 * 
 * Get the survey question title if it exists.
 * 
 * @return type
 */
function voterdb_get_survey_title() {
  $sq_title = variable_get('voterdb_survey_title', '');
  $ad_response_options = variable_get('voterdb_survey_responses', '');
  if(!empty($sq_title) AND !empty($ad_response_options)) {
    return $sq_title;
  }
  return NULL;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_signedup_nl
 * 
 * GEt an associative array of NLs that have signed up to  participate.
 * 
 * @return int
 */
function voterdb_get_signedup_nl() {
  db_set_active('nlp_voterdb');
  try {
    $gc_query = db_select(DB_NLSSTATUS_TBL, 'r');
    $gc_query->join(DB_NLS_TBL, 'n', 'r.'.NN_MCID.' = n.'.NH_MCID );
    $gc_query->addField('n',NH_MCID);
    $gc_query->addField('n',NH_NICKNAME);
    $gc_query->addField('n',NH_LNAME);
    $gc_query->addField('r',NN_NLSIGNUP);
    $gc_query->addField('r',NN_RESULTSREPORTED);
    $gc_query->addField('r',NN_LOGINDATE);
    $gc_query->condition(NN_NLSIGNUP,'Y');
    $gc_nls = $gc_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $gc_nls;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_counts
 * 
 * Loop through the list of NLs and coint the number who have logged in to
 * get their turf and the number that have reported results.
 * 
 * @param type $cn_nls
 * @return int
 */
function voterdb_counts($cn_nls) {
  $cn_signedup = $cn_loggedin = $cn_reported = 0;
  foreach ($cn_nls as $cn_nl) {
    if($cn_nl[NN_NLSIGNUP] == 'Y') {
      $cn_signedup++;
      if($cn_nl[NN_LOGINDATE] != NULL) {$cn_loggedin++;}
      if($cn_nl[NN_RESULTSREPORTED] != NULL) {$cn_reported++;}
    }
  }
  $cn_cnts['signedup'] = $cn_signedup;
  $cn_cnts['loggedin'] = $cn_loggedin;
  $cn_cnts['reported'] = $cn_reported;
  return $cn_cnts;
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_logincnt
 *
 * Counts NLs that either have signed up or who have reported results.  Type
 * selects which type to count.  The count is either for a single HD or for
 * the entire county.   The HD parameter selects which kind of count.
 *
 * The type parameter is the field name to count.  It is either the
 * NN_RESULTSREPORTED or the NN_NLSIGNUP column in the DB_NLSSTATUS_TBL.
 * These fields are either Y for yes or null for no.  We count the numbe
 * with Y set.
 *
 * @return type - goal count or zero if error.
 */
function voterdb_get_logincnt() {
  db_set_active('nlp_voterdb');
  try {
    $gc_query = db_select(DB_NLSSTATUS_TBL, 'r');
    $gc_query->join(DB_NLS_TBL, 'n', 'r.'.NN_MCID.' = n.'.NH_MCID );
    $gc_query->condition(NN_NLSIGNUP,'Y');
    $gc_query->isNotNull(NN_LOGINDATE);
    $gc_cnt = $gc_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $gc_cnt;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_nlcnt
 *
 * Counts NLs that either have signed up or who have reported results.  Type
 * selects which type to count.  The count is either for a single HD or for
 * the entire county.   The HD parameter selects which kind of count.
 *
 * The type parameter is the field name to count.  It is either the
 * NN_RESULTSREPORTED or the NN_NLSIGNUP column in the DB_NLSSTATUS_TBL.
 * These fields are either Y for yes or null for no.  We count the number
 * with Y set.
 *
 * @return type - count of NLs that have logged in or zero if error.
 */
function voterdb_get_nlcnt($gc_type) {
  db_set_active('nlp_voterdb');
  try {
    $gc_query = db_select(DB_NLSSTATUS_TBL, 'r');
    $gc_query->join(DB_NLS_TBL, 'n', 'r.'.NN_MCID.' = n.'.NH_MCID );
    $gc_query->condition(NN_NLSIGNUP,'Y');
    $gc_query->condition($gc_type,'Y');
    $gc_cnt = $gc_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $gc_cnt;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_voter_count
 * 
 * Count the number of voters assigned to NLs.
 * 
 * @return integer
 */
function voterdb_get_voter_count() {
  // Count the number of voters assigned to NLs.
  db_set_active('nlp_voterdb');
  try {
    $vc_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $vc_query->addField('g',NV_VANID);
    $vc_vtr = $vc_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $vc_vtr;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_contact_attempts
 * 
 * Count the number of voters that have at least one contact attempt of
 * any kind.
 * 
 * @return boolean
 */
function voterdb_voterdb_contact_attempts($vc_cycle) { 
  db_set_active('nlp_voterdb');
  try {
    $vc_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $vc_query->addField('r',NC_VANID);
    $vc_query->distinct();
    $vc_query->condition(NC_CYCLE,$vc_cycle);
  $vc_br = $vc_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $vc_br;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_report_counts
 * 
 * For each NL that has reported results for this cycle, count the number
 * of voters with attempted contact and also count the face-to-face contacts.
 * 
 * @param type $gr_county
 * @return boolean|int
 */
function voterdb_get_report_counts($gr_county) {
  //  Get the reports from NLs for voter contact for this cycle.
  $gr_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  db_set_active('nlp_voterdb');
  try {
    $gr_rquery = db_select(DB_NLPRESULTS_TBL, 'r');
    $gr_rquery->fields('r');
    $gr_rquery->condition(NC_COUNTY,$gr_county);
    $gr_rquery->condition(NC_CYCLE,$gr_cycle);
    $gr_result = $gr_rquery->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return FALSE;
  }
  db_set_active('default');
  // Count each report for a voter as an attempt and each face-to-face as a contact.
  $gr_result_array = unserialize(DE_RESULT_ARRAY);
  $gr_f2f = $gr_result_array[RE_F2F];
  $gr_type_array = unserialize(DE_TYPE_ARRAY);
  $gr_survey = $gr_type_array[RT_SURVEY];
  // For each report, identify which voter was contacted.
  $gr_vstatus = array();
  do {
    $gr_report = $gr_result->fetchAssoc();
    if(!$gr_report) {break;}
    $gr_vanid = $gr_report[NC_VANID];
    $gr_vstatus[$gr_vanid]['mcid'] = $gr_report[NC_MCID];
    $gr_vstatus[$gr_vanid]['attempt'] = TRUE;
    if($gr_report[NC_TYPE]==$gr_survey) {

      $gr_response = $gr_report[NC_VALUE];
      $gr_vstatus[$gr_vanid]['survey'] = $gr_response;
    }
  } while (TRUE);
  // For each NL, count the attempts and f2f contacts with voters.
  $gr_counts = array();
  foreach ($gr_vstatus as $gr_status) {
    $gr_mcid = $gr_status['mcid'];
    if(empty($gr_counts[$gr_mcid]['attempts'])) {
      $gr_counts[$gr_mcid]['attempts'] = 1;
    } else {
      $gr_counts[$gr_mcid]['attempts']++;
    }
    if(!empty($gr_status['survey'])) {
      if (empty($gr_counts[$gr_mcid][$gr_status['survey']])) {
        $gr_counts[$gr_mcid][$gr_status['survey']] = 1;
      } else {
        $gr_counts[$gr_mcid][$gr_status['survey']]++;
      }
    }
  }
  return $gr_counts;
 }
 
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_percent
 * 
 * @param type $pe_base
 * @param type $pe_cnt
 * @return type
 */
function voterdb_percent($pe_base,$pe_cnt) {
  $pe_percent = ($pe_base > 0)?round($pe_cnt/$pe_base*100,1).'%':'0%';
  return $pe_percent;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nl_info
 * 
 * Get information from the database that is needed to report the progress
 * of the NL to contact voters.
 * 
 * @param type $ns_mcid
 * @return type
 */
function voterdb_nl_info($ns_mcid) {
  db_set_active('nlp_voterdb');
  try {
    $ns_query = db_select(DB_NLS_TBL, 'n'); 
    $ns_query->join(DB_NLSSTATUS_TBL, 's', 's.'.NN_MCID.' = n.'.NH_MCID );
    $ns_query->addField('n',NH_COUNTY);
    $ns_query->addField('n',NH_FNAME);
    $ns_query->addField('n',NH_LNAME);
    $ns_query->addField('s',NN_RESULTSREPORTED);
    $ns_query->condition('n.'.NH_MCID,$ns_mcid);
    $ns_result = $ns_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  $ns_nl = $ns_result->fetchAssoc();
  db_set_active('default');
 
  return $ns_nl;
}

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
    $pc_query->addField('r', NV_COUNTY) ; 
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
 * voterdb_participation_cnts
 * 
 * Fill a file with records that show the progress of each NL to contact
 * voters and gather responses to the survey question.
 * 
 * @param type $pc_file_uri - a temp file for the report.
 * @return boolean
 */
function voterdb_participation_cnts($pc_file_uri) {
  $pc_file_fh = fopen($pc_file_uri,"w");
  if(!$pc_file_fh) {return FALSE;}
  // Write the header as the first record in this tab delimited file.
  $pc_record = array("County", "MCID", "FName", "LName","Rpt", "Attempts");
  // If we have a survey question, add the colmns for title and responses.
  $sq_response_list = variable_get('voterdb_survey_responses', '');
  $sq_title = variable_get('voterdb_survey_title', '');
  $pc_record[] = "Survey Title";
  if(!empty($sq_response_list)) {
    $sq_responses = explode(',',$sq_response_list);
    foreach ($sq_responses as $sq_response) {
      $pc_record[] = $sq_response;
    }
  }
  $pc_string = implode("\t", $pc_record)."\tEOR\n";
  fwrite($pc_file_fh,$pc_string);
  // Now fill the file.
  $pc_counties = voterdb_get_participating_counties();
  foreach ($pc_counties as $pc_county) {
    $pc_counts = voterdb_get_report_counts($pc_county);
    //voterdb_debug_msg('county counts', $pc_counts);
    // List of NLs with turfs.
    db_set_active('nlp_voterdb');
    try {
      $pc_query = db_select(DB_NLSTURF_TBL, 't'); 
      $pc_query->addField('t', TT_MCID);
      $pc_query->distinct();
      $pc_query->condition(TT_COUNTY,$pc_county);
      $pc_result = $pc_query->execute();
    }
    catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return NULL;
    }
    db_set_active('default');  
    // Copy the results to a tab delimited file.
    do {
      $pc_nl_record = $pc_result->fetchAssoc();
      if (!$pc_nl_record) {break;}
      $pc_mcid = $pc_nl_record[TT_MCID];
      // Get the name and other information for the display.
      $pc_nl = voterdb_nl_info($pc_mcid);
      // Construct the status record for the NL.
      $pc_nl_stat[NH_COUNTY] = $pc_nl[NH_COUNTY];
      $pc_nl_stat[TT_MCID] = $pc_mcid;
      $pc_nl_stat[NH_FNAME] = $pc_nl[NH_FNAME];
      $pc_nl_stat[NH_LNAME] = $pc_nl[NH_LNAME];
      $pc_nl_stat[NN_RESULTSREPORTED] = $pc_nl[NN_RESULTSREPORTED];
      $pc_atmps = (!empty($pc_counts[$pc_mcid]['attempts']))?$pc_counts[$pc_mcid]['attempts']:0;
      $pc_nl_stat['attempts'] = $pc_atmps;
      // If there is a survey question, include the title and the response columns.
      if(!empty($sq_response_list)) {
        $pc_nl_stat['title'] = $sq_title;
        foreach ($sq_responses as $sq_response) {
          $pc_nl_stat[$sq_response] = (empty($pc_counts[$pc_mcid][$sq_response]))?0:$pc_counts[$pc_mcid][$sq_response];
        }
      }
      $pc_string = implode("\t", $pc_nl_stat);
      $pc_string .= "\tEOR\n";
      fwrite($pc_file_fh,$pc_string);
    } while (TRUE);
  }
  fclose($pc_file_fh);
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_participation
 * 
 * Display the participation counts for the NL program.
 *
 * @return string - HTML for display.
 */
function voterdb_participation() {
  $gc_county = variable_get('voterdb_state', 'Select');
  $gc_banner1 = voterdb_build_banner ($gc_county);
  $gc_banner = str_replace("County", "State", $gc_banner1);
  $output = $gc_banner;
  // Set up the table style.
  drupal_add_css('
    table {border-collapse: collapse;}
    td {border: 1px solid black;
      text-align: right;}
    th{text-align: right;}', array('type' => 'inline'));
  // Count the NLs who have signed up, the voters assigned to NLs, and the
  // progress on contacting voters.
  $gc_nlcnt = voterdb_get_nlcnt(NN_NLSIGNUP);
  $gc_nlreporting = voterdb_get_nlcnt(NN_RESULTSREPORTED);
  $gc_nllogincnt = voterdb_get_logincnt();
  $gc_voter_count = voterdb_get_voter_count();
  // Count the progress on collecting responses for the survey question.
  $gc_cycle = variable_get('voterdb_ecycle', 'Gxxxx');
  $gc_contactattempts = voterdb_voterdb_contact_attempts($gc_cycle);
  // Display the participation counts in a nice table.
  $output .= '<table style="white-space: nowrap; width:453px;">';
  $output .= '<thead><tr><th style="text-align: left; width:150px;">Count Type</th>
      <th style="width:100px;">Count</th></tr></thead><tbody>';
  $output .= '<tr><td style="text-align: left;">Number of NLs</td>
              <td>'.$gc_nlcnt.'</td></tr>';
  $output .= '<tr><td style="text-align: left;">NLs logged in</td><td>'.$gc_nllogincnt.'</td></tr>';
  $output .= '<tr><td style="text-align: left;">NLs reporting results</td><td>'.$gc_nlreporting.'</td></tr>';

  $output .= '<tr><td style="text-align: left;">Voters assigned to NLs</td><td>'.$gc_voter_count.'</td></tr>';
  $output .= '<tr><td style="text-align: left;">Reported voter contact attempts</td><td>'.$gc_contactattempts.'</td></tr>';
  // If we have a survey question for this cycle, report it else don't include the columns.
  $gc_title = voterdb_get_survey_title();
  if (!empty($gc_title)) {
    $output .= '<tr><td style="text-align: left;">Survey: '.$gc_title.'</td><td></td></tr>';
    $gc_response_counts = voterdb_get_survey_response_counts();
    foreach ($gc_response_counts as $gc_response=>$gc_count) {
       $output .= '<tr><td style="text-align: left; font-style: italic;">&nbsp;&nbsp;'.$gc_response.'</td><td>'.$gc_count.'</td></tr>';
    }
  }
  $output .= '</tbody></table>';
  $output .= "<p>&nbsp;</p>";
  // Export the pregress report for each participating NL.
  $gc_temp_dir = 'public://temp';
  $gc_cdate = date('Y-m-d-H-i-s',time());
  $gc_participation_uri = $gc_temp_dir.'/participation_report-'.$gc_county.'-'.$gc_cdate.'.txt';
  $gc_participation_object = file_save_data('', $gc_participation_uri, FILE_EXISTS_REPLACE);
  $gc_participation_object->status = 0;
  file_save($gc_participation_object);
  if(!voterdb_participation_cnts($gc_participation_uri)) {return $output;}
  $gc_participation_url = file_create_url($gc_participation_uri);
  $output .= "<p><a href=".$gc_participation_url.">Right click here to download the participation report.</a> "
    . "<i>(Use the Save link as... option)</i></p>";
  return $output;
}