<?php
/*
 * Name: voterdb_display_results.php   V4.3 7/31/18
 */

require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_voters.php";
require_once "voterdb_class_matchback.php";
require_once "voterdb_class_crosstabs_and_counts.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpVoters;
use Drupal\voterdb\NlpMatchback;
use Drupal\voterdb\NlpCrosstabCounts;


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
function voterdb_get_ballot_counts($bc_counts) {
  if(empty($bc_counts)) {return NULL;}
  $bc_cnts = array();
  // Get all the records from the ballot count table.

  // Fetch each record and convert to the associate array.
  foreach ($bc_counts as $county=>$countyCounts) {
    foreach ($countyCounts as $party => $partyCounts) {
      $bc_v = $partyCounts['regVoters'];
      $bc_v_br = $partyCounts['regVoted'];
      switch ($party) {
        case 'Democrats':
          $bc_cnts[$county]['dem'] = $bc_v;
          $bc_cnts[$county]['dem-br'] = $bc_v_br;
          $bc_cnts[$county]['dem-pc'] = voterdb_percent($bc_v, $bc_v_br);
          break;
        case 'Republicans':
          $bc_cnts[$county]['rep'] = $bc_v;
          $bc_cnts[$county]['rep-br'] = $bc_v_br;
          $bc_cnts[$county]['rep-pc'] = voterdb_percent($bc_v, $bc_v_br);
          break;
        case 'ALL':
          $bc_cnts[$county]['all'] = $bc_v;
          $bc_cnts[$county]['all-br'] = $bc_v_br;
          $bc_cnts[$county]['all-pc'] = voterdb_percent($bc_v, $bc_v_br);
          break;
      }
    }
  }
  return $bc_cnts;
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
  $gc_button_obj = new NlpButton();
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
  
  $matchbackObj = new NlpMatchback();
  $voterObj = new NlpVoters();
  if ($gc_all) {
    $gc_counties = $voterObj->getParticipatingCounties();
  } else {
    $gc_counties = array($gc_county); 
  }
  
  $nlsObj = new NlpNls();
  $reportsObj = new NlpReports();

  
  
  $crosstabsObj = new NlpCrosstabCounts();
  $gc_counts = $crosstabsObj->fetchCrosstabCounts();
  //voterdb_debug_msg('counts', $gc_counts);
  
  $gc_cnts = voterdb_get_ballot_counts($gc_counts);
  
  foreach ($gc_counties as $gc_county) {
    // For a county, get the ballot counts and percentages of voting.
    // Count the number of voters assigned to NLs for this group.
    //voterdb_debug_msg('county: '.$gc_county, '');
    $gc_vtr = $voterObj->getVoterCount($gc_county);
    //voterdb_debug_msg('vtr: '.$gc_vtr, '');
    $gc_cnts[$gc_county]['vtr'] = $gc_vtr;
    // Count the number of these voters who returned ballots.
    
    $gc_br2 = $voterObj->getVoted($gc_county,$matchbackObj);
    //voterdb_debug_msg('br: '.$gc_br2, '');
    $gc_cnts[$gc_county]['vtr-br'] = $gc_br2;
    // Display the voter participation.
    $gc_vtr_percent = '0%';
    if($gc_vtr > 0) {
        $gc_vtr_percent = round($gc_br2/$gc_vtr*100,1).'%';}
    $gc_cnts[$gc_county]['vtr-pc'] = $gc_vtr_percent;
    // Count the number of voters who were contacted by NLs, either Face-to-face or by phone.
    
    $gc_rr = $reportsObj->countyContacted($gc_county);
    //voterdb_debug_msg('rr: '.$gc_rr, '');

    $gc_cnts[$gc_county]['ctd'] = $gc_rr;
    // Count the number of the voters who had a personal contact and who voted.
    
    $gc_br = $voterObj->getVotedAndContacted($gc_county,$matchbackObj,$reportsObj);
    //voterdb_debug_msg('br: '.$gc_br, '');
    
    $gc_cnts[$gc_county]['ctd-br'] = $gc_br;
    // Results for personal contact.
    $gc_rr_percent = '0%';
    if($gc_rr > 0) {$gc_rr_percent = round($gc_br/$gc_rr*100,1).'%';}
    $gc_cnts[$gc_county]['ctd-pc'] = $gc_rr_percent;
  }
  // Build the tables.
  $gc_nls_sum = $gc_rpt_sum = 0;
  foreach ($gc_counties as $gc_county) {
    $output .= '<p style="text-decoration: underline; font-size: large;">'.$gc_county.'</p>';
    $gc_grp_cnts = $gc_cnts[$gc_county];
    if (!isset($gc_sum_cnts)) {
      $gc_sum_cnts = $gc_grp_cnts;
    } else {
      foreach ($gc_grp_cnts as $gc_key => $gc_value) {
        $gc_sum_cnts[$gc_key] += $gc_value;
      }
    }
    
    $gc_nls = $nlsObj->getNls($gc_county,'ALL');
    $gc_nls_cnt = $gc_nls_rpt = 0;
    foreach ($gc_nls as $gc_nl) {
      if($gc_nl['status']['nlSignup']) {$gc_nls_cnt++;}
      if($gc_nl['status']['resultsReported']) {$gc_nls_rpt++;}
    }
    //$gc_nls_cnt = voterdb_get_nlscount($gc_county,'ALL', NN_NLSIGNUP);
    //$gc_nls_rpt = voterdb_get_nlscount($gc_county,'ALL', NN_RESULTSREPORTED);
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
