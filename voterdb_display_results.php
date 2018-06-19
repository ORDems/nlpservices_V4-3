<?php
/*
 * Name: voterdb_display_results.php   V4.0 2/18/18
 */
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_log_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_constants_bc_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_turf_tbl.php";
require_once "voterdb_get_county_names.php";
require_once "voterdb_group.php";
require_once "voterdb_path.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_nlscount
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
 * @param type $gc_county - name of the group.
 * @param type $gc_hd - HD number or set to ALL to for a county wide count.
 * @param type $gc_type - Column name to count.
 * @return type - goal count or zero if error.
 */
function voterdb_get_nlscount($gc_county,$gc_hd,$gc_type) {
  db_set_active('nlp_voterdb');
  try {
    $gc_query = db_select(DB_NLSSTATUS_TBL, 'r');
    $gc_query->join(DB_NLS_TBL, 'n', 'r.'.NN_MCID.' = n.'.NH_MCID );
    $gc_query->condition(NN_NLSIGNUP,'Y');
    $gc_query->condition('r.'.NN_COUNTY,$gc_county);
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
 * voterdb_get_participating_counties
 * 
 * @return int
 */
function voterdb_get_participating_counties() {
  // Count the number of voters assigned to NLs for this group.
  db_set_active('nlp_voterdb');
  try {
    $pc_query = db_select(DB_NLPVOTER_GRP_TBL, 'r');
    $pc_query->addField('r', NV_COUNTY);
    $pc_query->distinct();
    $pc_result = $pc_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return FALSE;
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
 * voterdb_get_voter_count
 * 
 * @param type $vc_county
 * @return boolean
 */
function voterdb_get_voter_count($vc_county) {
  // Count the number of voters assigned to NLs for this group.
  db_set_active('nlp_voterdb');
  try {
    $vc_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $vc_query->addField('g',NV_VANID);
    $vc_query->condition(NV_COUNTY,$vc_county);
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
 * voterdb_get_voted
 * 
 * @param type $gv_county
 * @return boolean
 */
function voterdb_get_voted($gv_county) {
  db_set_active('nlp_voterdb');
  try {
    $gv_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $gv_query->join(DB_MATCHBACK_TBL, 'm', 'g.'.VN_VANID.' = m.'.MT_VANID );
    $gv_query->condition('g.'.NV_COUNTY,$gv_county);
    $gv_query->condition(MT_DATE_INDEX,'','!=');
    $gv_br2 = $gv_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $gv_br2;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_contacted
 * 
 * @param type $co_county
 * @return boolean
 */
function voterdb_contacted($co_county) {
  $co_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  db_set_active('nlp_voterdb');
  try {
    $co_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $co_query->addField('r',NC_VANID);
    $co_query->distinct();
    $co_query->condition(NC_CYCLE,$co_cycle);
    $co_query->condition(NC_COUNTY,$co_county);
    $co_query->condition(NC_TYPE,'Contact');
    $co_query->condition(db_or()->condition(NC_VALUE, 'Face-to-Face')->condition(NC_VALUE, 'Phone Contact'));
    $co_rr = $co_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  db_set_active('default');
  return $co_rr;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_voting_contact
 * 
 * @param type $vc_county
 * @return boolean
 */
function voterdb_voting_contact($vc_county) {
  $vc_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  db_set_active('nlp_voterdb');
  try {
    $vc_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $vc_query->join(DB_MATCHBACK_TBL, 'm', 'r.'.NC_VANID.' = m.'.MT_VANID );
    $vc_query->addField('r',NC_VANID);
    $vc_query->distinct();
    $vc_query->condition(NC_CYCLE,$vc_cycle);
    $vc_query->condition('r.'.NC_COUNTY,$vc_county);
    $vc_query->condition(NC_TYPE,'Contact');
    $vc_query->condition(MT_DATE_INDEX,'','!=');
    $vc_query->condition(db_or()->condition(NC_VALUE, 'Face-to-Face')->condition(NC_VALUE, 'Phone Contact'));
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
 * voterdb_get_ballot_counts
 * 
 * Get the entries in the ballot count table and build an associate array of
 * counts for Ds, Rs, and all voters.   The count of each and the count of those 
 * who have voted.  Then the percentage is calculated.  The minor parties are
 * in the database but only Ds, Rs and all voters are entered into the array.
 * 
 * @return associate array of counties and the counts for each.
 */
function voterdb_get_ballot_counts(&$bc_cnts,$bc_grp_array) {
  $bc_cnts = array();
  // Get all the records from the ballot count table.
  db_set_active('nlp_voterdb');
  $bc_tselect = "SELECT * FROM {".DB_BALLOTCOUNT_TBL."} WHERE  1";
  $bc_result = db_query($bc_tselect);
  $bc_counts = $bc_result->fetchAll(PDO::FETCH_ASSOC);
  // If the cross tab counts are not yet loaded, return zeros.
  // Initialize to count array.
  $bc_cnt_keys = array('dem','dem-br','dem-pc','rep','rep-br','rep-pc','all','all-br','all-pc');
  foreach ($bc_grp_array as $bc_county) {
    foreach ($bc_cnt_keys as $bc_cnt_key) {
      $bc_cnts[$bc_county][$bc_cnt_key] = 0;
    }
  }
  if(empty($bc_counts)) {return TRUE;}
  db_set_active('default');
  // Fetch each record and convert to the associate array.
  foreach ($bc_counts as $bc_cnt_array) {
    $bc_party = $bc_cnt_array[BC_PARTY];
    $bc_county = $bc_cnt_array[BC_COUNTY];
    switch ($bc_party) {
      case 'D':
        $bc_v = $bc_cnt_array[BC_REG_VOTERS];
        $bc_v_br = $bc_cnt_array[BC_REG_VOTED];
        $bc_cnts[$bc_county]['dem'] = $bc_v;
        $bc_cnts[$bc_county]['dem-br'] = $bc_v_br;
        $bc_cnts[$bc_county]['dem-pc'] = voterdb_percent($bc_v, $bc_v_br);
        break;
      case 'R':
        $bc_v = $bc_cnt_array[BC_REG_VOTERS];
        $bc_v_br = $bc_cnt_array[BC_REG_VOTED];
        $bc_cnts[$bc_county]['rep'] = $bc_v;
        $bc_cnts[$bc_county]['rep-br'] = $bc_v_br;
        $bc_cnts[$bc_county]['rep-pc'] = voterdb_percent($bc_v, $bc_v_br);
        break;
      case 'ALL':
        $bc_v = $bc_cnt_array[BC_REG_VOTERS];
        $bc_v_br = $bc_cnt_array[BC_REG_VOTED];
        $bc_cnts[$bc_county]['all'] = $bc_v;
        $bc_cnts[$bc_county]['all-br'] = $bc_v_br;
        $bc_cnts[$bc_county]['all-pc'] = voterdb_percent($bc_v, $bc_v_br);
        break;
    }
  }
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_count_display
 * 
 * @param type $cd_county
 * @param type $cd_cnts
 * @return string
 */
function voterdb_build_count_display ($cd_county,$cd_cnts) {
  $cd_out = '<table style="white-space: nowrap; width:453px;">';
  $cd_out .= '<thead><tr><th style="text-align: left; width:150px;"></th>
      <th style="width:100px;">Registered</th><th style="width:100px;">Voted</th>
      <th style="width:100px;">Participation</th></tr></thead><tbody>';

  $cd_hdr = ($cd_county == "NLP")?"All NLP Counties":"County";
  $cd_out .= '<tr><td style="text-align: left;">'.$cd_hdr.'</td>
              <td>'.$cd_cnts['all'].'</td>
              <td>'.$cd_cnts['all-br'].'</td><td>'.$cd_cnts['all-pc'].'</td></tr>';
  $cd_out .= '<tr><td style="text-align: left;">Rep</td><td>'.$cd_cnts['rep'].'</td>
              <td>'.$cd_cnts['rep-br'].'</td><td>'.$cd_cnts['rep-pc'].'</td></tr>';
  $cd_out .= '<tr><td style="text-align: left;">Dem</td><td>'.$cd_cnts['dem'].'</td>
              <td>'.$cd_cnts['dem-br'].'</td><td>'.$cd_cnts['dem-pc'].'</td></tr>';

  $cd_out .= '<tr><td style="text-align: left;">NLP</td><td>'.$cd_cnts['vtr'].'</td>
              <td>'.$cd_cnts['vtr-br'].'</td><td>'.$cd_cnts['vtr-pc'].'</td></tr>';
  $cd_out .= '<tr><td style="text-align: left;">Contacted</td>
              <td>'.$cd_cnts['ctd'].'</td>
              <td>'.$cd_cnts['ctd-br'].'</td><td>'.$cd_cnts['ctd-pc'].'</td></tr>
              </tbody></table>';
  return $cd_out;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_results
 * 
 * Display the voter turnout for the NL program.
 *
 * @return string - HTML for display.
 */
function voterdb_display_results() {
  $gc_button_obj = new NlpButton;
  $gc_button_obj->setStyle(); 
  $form_state = array();
  if(!voterdb_get_group($form_state)) {return;}
  $gc_county = $form_state['voterdb']['county'];
  $gc_all = isset($form_state['voterdb']['ALL']);
  $gc_banner = voterdb_build_banner ($gc_county);
  $output = $gc_banner;
  // Set up the table style.
  drupal_add_css('
    table {border-collapse: collapse;}
    td {border: 1px solid black;
      text-align: right;}
    th{text-align: right;}', array('type' => 'inline'));
  if ($gc_all) {
    // All the groups.
    $gc_grp_array = voterdb_get_participating_counties();
  } else {
    $gc_grp_array = array($gc_county);  // Just one group.
  }
  $gc_cnts = array();
  voterdb_get_ballot_counts($gc_cnts,$gc_grp_array);
  foreach ($gc_grp_array as $gc_county) {
    // For a county, get the ballot counts and percentages of voting.
    // Count the number of voters assigned to NLs for this group.
    $gc_vtr = voterdb_get_voter_count($gc_county);
    $gc_cnts[$gc_county]['vtr'] = $gc_vtr;
    // Count the number of these voters who returned ballots.
    $gc_br2 = voterdb_get_voted($gc_county);
    $gc_cnts[$gc_county]['vtr-br'] = $gc_br2;
    // Display the voter participation.
    $gc_vtr_percent = '0%';
    if($gc_vtr > 0) {
        $gc_vtr_percent = round($gc_br2/$gc_vtr*100,1).'%';}
    $gc_cnts[$gc_county]['vtr-pc'] = $gc_vtr_percent;
    // Count the number of voters who were contacted by NLs, either Face-to-face or by phone.
    $gc_rr = voterdb_contacted($gc_county);
    $gc_cnts[$gc_county]['ctd'] = $gc_rr;
    // Count the number of the voters who had a personal contact and who voted.
    $gc_br = voterdb_voting_contact($gc_county);
    $gc_cnts[$gc_county]['ctd-br'] = $gc_br;
    // Results for personal contact.
    $gc_rr_percent = '0%';
    if($gc_rr > 0) {$gc_rr_percent = round($gc_br/$gc_rr*100,1).'%';}
    $gc_cnts[$gc_county]['ctd-pc'] = $gc_rr_percent;
  }
  // Build the tables.
  $gc_nls_sum = $gc_rpt_sum = 0;
  foreach ($gc_grp_array as $gc_county) {
    $output .= '<p style="text-decoration: underline; font-size: large;">'.$gc_county.'</p>';
    $gc_grp_cnts = $gc_cnts[$gc_county];
    if (!isset($gc_sum_cnts)) {
      $gc_sum_cnts = $gc_grp_cnts;
    } else {
      foreach ($gc_grp_cnts as $gc_key => $gc_value) {
        $gc_sum_cnts[$gc_key] += $gc_value;
      }
    }
    $gc_nls_cnt = voterdb_get_nlscount($gc_county,'ALL', NN_NLSIGNUP);
    $gc_nls_rpt = voterdb_get_nlscount($gc_county,'ALL', NN_RESULTSREPORTED);
    $gc_nls_sum += $gc_nls_cnt;
    $gc_rpt_sum += $gc_nls_rpt;
    $output .= "<p>Number of participating NLs: ".$gc_nls_cnt."<br>";
    $output .= "Number of NLs reporting results: ".$gc_nls_rpt."</p>";
    $output .= voterdb_build_count_display ($gc_county,$gc_grp_cnts);
    $output .= "<p>&nbsp;</p>";
  }
  // Display the sum of all participating counties.
  if ($gc_all) {
    $gc_pcr = array('dem','rep','all','vtr','ctd');
    $output .= '<p style="text-decoration: underline; font-size: large;">'.'NLP Counties'.'</p>';
    $output .= "<p>Number of participating NLs: ".$gc_nls_sum."<br>";
    $output .= "Number of NLs reporting results: ".$gc_rpt_sum."</p>";
    // Fix the percentages.
    foreach ($gc_pcr as $gc_pck) {
      $gc_sum_cnts[$gc_pck.'-pc'] = voterdb_percent($gc_sum_cnts[$gc_pck],$gc_sum_cnts[$gc_pck.'-br']);
    }
    $output .= voterdb_build_count_display ('NLP',$gc_sum_cnts);
    $output .= "<p>&nbsp;</p>";
  }
  $output .= '<a href="nlpadmin?County='.$gc_county.'" class="button ">Done</a>';
  return $output;
}
