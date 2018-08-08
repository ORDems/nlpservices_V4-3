<?php
/*
 * Name: voterdb_nls_display.php     V4.3  8/7/18
 * Display the list of Prospective NLs for a county.
 *
 */
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_track.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_get_browser.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_nls_display_func.php";
require_once "voterdb_nls_display_func2.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\GetBrowser;

define("NLS_COUNTY_MAX","151");
define("NLS_ALL_MAX","500");


// Constants for building the goals table.
define('DC_LBL_W','130'); // Width of the label.
define('DC_CELL_W','36');  // Width of a HD cell.
define('DC_PAD_W','4');  // Cell padding.

define('DC_GLTBL',
   '#gl_tbl {font-size:x-small;}  
    .gl_cell {font-size:x-small;text-align:center; width:'.DC_CELL_W.'px;}
    .gl_lbl {font-size:x-small; text-align:left; width:'.DC_LBL_W.'px;}
    table.center {margin-left:auto; margin-right:auto;}
    '
  );
define('DC_FORMITEM',
       '.form-item {margin-top:2px; margin-bottom:2px;}'
        );
define('DC_NLTBL',
   'table.tborder { padding:0px; border-color:#d3e7f4; border-width: 1px; border-style: solid; width:1000px;}
    td.dborder { padding:0px; border-color:#d3e7f4; border-width:1px; border-style: solid; }
    th.hborder { padding:0px; border-color:#d3e7f4; border-width:1px; border-style: solid; }
    .nowhite {margin:1px; padding:1px; line-height:100%;}
    '
  );
define('DC_NLCELL',    
   '.cell-hd {text-align:center;width:30px; padding:0px;}
    .cell-pct {text-align:center;width:60px; padding:0px;}
    .cell-name {text-align:left; width:100px; padding:0px;}
    .cell-addr {text-align:left; width:130px; padding:0px; }
    .cell-email {text-align:left; width:150px; padding:0px; }
    .cell-text {text-align:left; width:160px; padding:0px; }
    .cell-ask {text-align:center; width:60px; padding:0px;}
    .cell-chk {text-align:center; width:30px; padding:0px;}
    .cell-type {text-align:center; width:60px; padding:0px;}
    .cell-lin {text-align:center; width:70px; padding:0px;}
    .cell-atmp {text-align:center; width:20px; padding:0px;}
    .cell-cont {text-align:center; width:20px; padding:0px;}
    .cell-bold {font-weight: bold;}
    .cell-color {color: blue;}
    .text-width {width: 160px;}
    .textarea-width {width: 140px; resize: none;}
    '
  );
//background-color: #ffffff; left: 20px; top: -50px; color: #3090cc;
define('DC_HINTS',
 '#hintth { position: relative; }
  #hintth p { margins: 0; padding: 0; }
  #hintth p span { display: none; color: #0033ff; }
  #hintth p:hover span { display: block; position: absolute; width: 300px;
    font-weight: normal;
    left: -100px; top: -60px; color: #000000; text-align: left; 
    padding: 3px; border: 2px solid #0000ff; border-radius:5px;
    background: rgba(255, 255, 255, 1.0) } '
  );


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_nls_form
 * 
 * Build a Drupal form to both list the NLs for a house district and 
 * allow the status of NL signup to be updated.  For small counties, the entire
 * list of the county is displayed.   (The Drupal AJAX processing is slow
 * when there are a lot of entries in the form.)
 * 
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_display_nls_form($form_id, &$form_state) {
  $dn_button_obj = new NlpButton();
  $dn_button_obj->setStyle();
  $nlsObj = new NlpNls();
  $form_state['voterdb']['nlsObj'] = $nlsObj;
  // Verify we know the group.
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['entry-cnt'] = 0;
    $form_state['voterdb']['csv-display'] = FALSE;
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['hd-current'] = $form_state['voterdb']['hd-new'] = 1;
    // Show all NLs if the number is small.
    $dn_county = $form_state['voterdb']['county'];
    $dn_nlcnt = $nlsObj->countNls($dn_county);
    if($dn_nlcnt < NLS_COUNTY_MAX) {
      $form_state['voterdb']['hd-new'] = 0;
    }
  } 
  $dn_county = $form_state['voterdb']['county'];
  
  //$bigObj = new NlpBigTest();
  $browserObj = new GetBrowser();
  
  if($form_state['voterdb']['csv-display'] == TRUE) {
    //voterdb_debug_msg('voterdb', $form_state['voterdb']);
    $nlRecords = $form_state['voterdb']['nlRecords'];
    //$dn_csv_hdr = $form_state['voterdb']['nl-csv-hdr'];

    $dn_hdi = $form_state['voterdb']['hd-current'];
    if($dn_hdi == 0) {
      $dn_hd = 'ALL';
    } else {
      $dn_hd = $form_state['voterdb']['hd_array'][$dn_hdi-1];
    }
    $dn_ccv_url = voterdb_create_csv($dn_county,$dn_hd,$nlRecords); // func2.
    //voterdb_debug_msg('ccv url', $dn_ccv_url);
    

    //$dn_browser_obj = new GetBrowser();
    $browser = $browserObj->getBrowser();
    $dn_browser_hint = $browser['hint'];
    
    $form['csv-note'] = array (
    '#type' => 'markup',
    '#markup' => '<p id="hint1"> <a href="'.$dn_ccv_url.
        '">Right-click to download NL Management Table data. <span>Remember to right-click the link and then select "'.
        $dn_browser_hint.'".</span> </a></p>',
    );
    
    $form['csvsubmit'] = array(
      '#name' => 'csv-done',
      '#type' => 'submit',
      '#value' => 'Return to NL Management Page >>',
    );
    
    
    return $form;
  }
  
  
  drupal_add_css(DC_FORMITEM.DC_NLTBL.DC_NLCELL.DC_HINTS, array('type' => 'inline'));

  $form_state['voterdb']['entry-cnt']++;
  // Create the form to display of all the NLs.
  $dn_banner = voterdb_build_banner ($dn_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $dn_banner
  ); 
  
  // Select the HD to display.
  $dn_hd_new = $form_state['voterdb']['hd-new'];
  $dn_hd_array = $form_state['voterdb']['hd_array'];
  // Provide a list of HD numbers for the user selection.
  $dn_hdoptions = array();
  $dn_hdoptions[] = 'ALL';
  foreach ($dn_hd_array as $dn_hd) {
    $dn_hdoptions[] = $dn_hd;
  }
  $form_state['voterdb']['hdoptions'] = $dn_hdoptions;
  
  // Add the line for selecting an HD and CSV download.
  voterdb_options_display($form,$dn_hdoptions,$dn_hd_new);
  
  $form_state['voterdb']['sortable'] = array('hd'=>'HD','pct'=>'Pct','asked'=>'NL',
      'turfCut'=>'TC','turfDelivered'=>'TD','contact'=>'CO','loginDate'=>'LI','atmps'=>'Atmps','conts'=>'Conts');
  $dn_sortable = $form_state['voterdb']['sortable'];
  // Add the line for return and sort.
  voterdb_sort_display($form,$dn_county,$dn_sortable);
    
   
  $form_state['voterdb']['hd-current'] = $form_state['voterdb']['hd-new'];
  $dn_hd = $dn_hdoptions[$dn_hd_new];
  // Fetch the list of NL names and contact information.
  if (!isset($form_state['voterdb']['nlRecords'])) {
    $nlsObj = new NlpNls();
    $nlRecords = $nlsObj->getNls($dn_county,$dn_hd);
    //voterdb_debug_msg('nlrecords', $nlRecords);
    $nlKeys = array_keys($nlRecords);
    $reportsObj = new NlpReports();
    foreach ($nlKeys as $mcid) {
      //voterdb_debug_msg('mcid: '.$mcid, '');
      $nlRecords[$mcid]['progress']  = voterdb_get_progress($nlRecords[$mcid],$reportsObj);  // func2.
      //voterdb_debug_msg('nlrecord', $nlRecords[$mcid]);
    } 
    $form_state['voterdb']['nlRecords'] = $nlRecords;
  }  
  // Build the table of NLs names and status.

  voterdb_build_nls_table($form,$form_state); // func.
  
  //$dn_sortable = $form_state['voterdb']['sortable'];
  //$form['sortselect']['#options'] = $dn_sortable;
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$dn_county.'" class="button ">Return to Admin page >></a></p>',
  );
  
  //$nf_tbl_csv = $form_state['voterdb']['tbl-csv'];
  //voterdb_debug_msg('CSV Tbl', $nf_tbl_csv);
  
  return $form;
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_nls_form_validate
 *
 * Validate the various forms we use in this multipass form process
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_display_nls_form_validate($form, &$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $nv_county = $form_state['voterdb']['county'];
  $nlsObj = new NlpNls();
  //voterdb_debug_msg('nlsobj', $nlsObj);
  // No validation needed for the HD selection.
  $nv_element_clicked = $form_state['triggering_element']['#name'];
  //voterdb_debug_msg('element clicked', $nv_element_clicked);
  if ($nv_element_clicked == 'hd-submit' OR 
      $nv_element_clicked == 'download-csv' OR 
      $nv_element_clicked == 'sort-submit' OR 
      $nv_element_clicked == 'csv-done') 
    {return;}
  // The triggering element names have the form type-mcid.
  $nv_id_array = explode('-', $nv_element_clicked);
  $nv_mcid = $nv_id_array[1];  // MCID of affected NL.
  $nv_status = $nlsObj->getNlsStatus($nv_mcid,$nv_county);
  //
  $nv_value = $form_state['triggering_element']['#value'];
  //voterdb_debug_msg('value', $nv_value);
  
  //$nv_history = array(DZ_NL=>NY_SIGNEDUP,DZ_TC=>NY_TURFCHECKEDIN,DZ_TD=>NY_DELIVEREDTURF);
  // Process the checkbox, select or textbox for this NL.
  switch ($nv_id_array[0]) {
    case 'TD':  // Turf Delivered.
      // If the turf delivered status is set, update the date the turf was delivered.
      if($nv_value) {
        $turfsObj = new NlpTurfs();
        $turfsObj->setAllTurfsDelivered($nv_mcid,$nv_county);
      }
      
      $nv_cell_display = ($nv_value)?'Y':'';
      $nv_status['turfDelivered'] = $nv_cell_display;
      
      $nlsObj->setNlsStatus($nv_status);
      //
      
      $form_state['voterdb']['nl-list'][$nv_mcid]['status']['turfDelivered'] = $nv_cell_display;
      
      $statusHistory['mcid'] = $nv_mcid;
      $statusHistory['county'] = $nv_county;
      $statusHistory['status'] = $nlsObj::HISTORYDELIVEREDTURF;
      $statusHistory['nlFirstName'] = $form_state['voterdb']['nlRecords'][$nv_mcid]['firstName'];
      $statusHistory['nlLastName'] = $form_state['voterdb']['nlRecords'][$nv_mcid]['lastName'];
      
      $nlsObj->setStatusHistory($statusHistory);
      //voterdb_nl_status_history($nv_county,$nv_mcid,NY_DELIVEREDTURF);
      
      
      break;

    case 'TC':  // Turf cut.

      $nv_cell_display = ($nv_value)?'Y':'';
      $nv_status['turfCut'] = $nv_cell_display;
      
      $nlsObj->setNlsStatus($nv_status);
      //
      
      $form_state['voterdb']['nl-list'][$nv_mcid]['status']['turfCut'] = $nv_cell_display;
      
      $statusHistory['mcid'] = $nv_mcid;
      $statusHistory['county'] = $nv_county;
      $statusHistory['status'] = $nlsObj::HISTORYTURFCHECKEDIN;
      $statusHistory['nlFirstName'] = $form_state['voterdb']['nlRecords'][$nv_mcid]['firstName'];
      $statusHistory['nlLastName'] = $form_state['voterdb']['nlRecords'][$nv_mcid]['lastName'];
      $nlsObj->setStatusHistory($statusHistory);
      //voterdb_nl_status_history($nv_county,$nv_mcid,NY_TURFCHECKEDIN);
      break;
    
    case 'TB':  // Notes (text box).
      $nv_trunc = substr($nv_value,0,$nlsObj::NOTESMAX);
      $nv_status['notes'] = $nv_trunc;
      
      $nlsObj->setNlsStatus($nv_status);
      
      //
      $form_state['voterdb']['nlRecords'][$nv_mcid]['status']['notes'] = $nv_trunc;
      break;
    
    case 'CO':  // Contact type (select); canvass, post card, phone.
      $nv_status['contact'] = $nv_value;
      $nlsObj->setNlsStatus($nv_status);
      $form_state['voterdb']['nl-list'][$nv_mcid]['status']['contact'] = $nv_value;
      break;
    
    case 'AS':  // Ask type (select); Default(NULL), Asked, Yes, No, Quit.
      $nv_status['nlSignup'] = '';
      if($nv_value == 'yes') {
        $nv_status['nlSignup'] = 'Y';
      }
      $nv_status['asked'] = $nv_value;
      $form_state['voterdb']['nl-list'][$nv_mcid]['status']['asked'] = $nv_value;
      //
      $nlsObj->setNlsStatus($nv_status);
      
      $statusHistory['mcid'] = $nv_mcid;
      $statusHistory['county'] = $nv_county;
      $statusHistory['status'] = $nlsObj->askHistory[$nv_value];
      $statusHistory['nlFirstName'] = $form_state['voterdb']['nlRecords'][$nv_mcid]['firstName'];
      $statusHistory['nlLastName'] = $form_state['voterdb']['nlRecords'][$nv_mcid]['lastName'];
      $nlsObj->setStatusHistory($statusHistory);
      //voterdb_nl_status_history($nv_county,$nv_mcid,$nv_ask_status);
      break;
  }
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_nls_form_submit
 *
 * Process the change in HD to display.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_display_nls_form_submit($form, &$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist.
  
  $dn_button_clicked = $form_state['triggering_element']['#name'];
  //voterdb_debug_msg('button clicked', $dn_button_clicked);
  
  switch ($dn_button_clicked) {
    case 'hd-submit':
      // The user may have changed the HD to display.
      $form_state['voterdb']['hd-new'] = $form_state['values']['hdselect'];
      // force rebuilding the list of NLs.
      unset($form_state['voterdb']['nlRecords']);
      // Clear existing information about prior input.  It actually contains
      // prior defaults and forms API gets confused with a new form.
      unset($form_state['buttons']);
        break;
    case 'download-csv':
      $form_state['voterdb']['csv-display'] = TRUE;
      break;
      
    case 'csv-done':
      $form_state['voterdb']['csv-display'] = FALSE;  
      break;
    
    case 'sort-submit':
      //voterdb_debug_msg('values', $form_state['values']);
      $dn_county = $form_state['voterdb']['county'];
      $dn_sortable = $form_state['voterdb']['sortable'];
      //voterdb_debug_msg('sortable', $dn_sortable);
      //$dn_column = $dn_sortable[$columnKey];
      $columnKey = $form_state['values']['sortselect'];

      $nlRecords = $form_state['voterdb']['nlRecords'];
      //$dn_sorted_data = orderBy($dn_nl_list, $dn_column_key);
      
      
      $dn_sorted_data = voterdb_sort_nls($nlRecords,$columnKey);
      //voterdb_debug_msg('sorted', $dn_sorted_data);
      $form_state['voterdb']['nlRecords'] = $dn_sorted_data;
      
      $dn_info = "Column requested for sort: ".$dn_sortable[$columnKey];
      voterdb_login_tracking('sort',$dn_county,'NL table was sorted. ',$dn_info);
      
      break;
    
  }
  
  return;
}

function orderBy($data, $field)  {
  $code = "return strnatcmp(\$a['$field'], \$b['$field']);";
  usort($data, create_function('$a,$b', $code));
  return $data;
}

function voterdb_sort_nls($nlRecords,$columnKey) {
  switch ($columnKey) {
    case 'hd':
    case 'pct':
      $sortedRecords = orderBy($nlRecords,$columnKey);
      break;
    case 'atmps':
    case 'conts':
      foreach ($nlRecords as $mcid => $nlRecord) {
        $nlRecords[$mcid]['order'] = $nlRecord['progress'][$columnKey];
      }
      $sortedRecords = orderBy($nlRecords, 'order');
      break;
    default:
      foreach ($nlRecords as $mcid => $nlRecord) {
        $nlRecords[$mcid]['order'] = $nlRecord['status'][$columnKey];
      }
      $sortedRecords = orderBy($nlRecords, 'order');
      break;
  }
  return $sortedRecords;
}

function voterdb_sort_display(&$form,$sd_county,$sd_options) {
    
 $form['sort-start'] = array(
      '#type' => 'markup',
      '#prefix' => " \n".'<table class="tborder nowhite" ><tr><td>',
  );
    
  $form['done2'] = array(
    '#markup' => " \n".'<a href="nlpadmin?County='.$sd_county.'" class="button ">Return to Admin page >></a>',
      '#prefix' => " \n".'<div>'
      . '<div style="float:left; padding-top: 4px;">',
      '#suffix' => " \n".'</div>',
  );
  
  $form['sortselect'] = array(
    '#type' => 'select',
    '#options' => $sd_options,
    '#prefix' => '</td><td style = "font-size:x-small;"><div><div  style="float:right;">',
    '#suffix' => '</div>',
    );
   $form['sortsubmit'] = array(
    '#name' => 'sort-submit',
    '#type' => 'submit',
    '#value' => 'sort by the selected column >>',
    '#prefix' => '<div  style="float:right;">',
    '#suffix' => '</div></div>',
  );
   
  $form['ssort-end'] = array(
    '#type' => 'markup',
    '#prefix' => '</td></tr></table>',
  );

 
}

function voterdb_options_display(&$form,$dn_hdoptions,$dn_hd_new) {
  
  $form['options-start'] = array(
      '#type' => 'markup',
      '#prefix' => " \n".'<table class="tborder nowhite" ><tr><td>',
  );
  // Build the HD choice
  $form['hdinstructions'] = array(
      '#type' => 'markup',
      '#markup' => 'Select an HD to display:&nbsp;',
      '#prefix' => " \n".'<div style = "font-size:x-small;">'
      . '<div style="float:left; padding-top: 4px;">',
      '#suffix' => " \n".'</div>',
  );
  
  
  

  $form['hdselect'] = array(
      '#type' => 'select',
      '#options' => $dn_hdoptions,
      '#default_value' => $dn_hd_new,
      '#prefix' => " \n".'<div style="float:left;">',
      '#suffix' => " \n".'</div>',
  );
  
  $form['space1'] = array(
      '#type' => 'markup',
      '#markup' => "&nbsp;",
      '#prefix' => " \n".'<div style="float:left;">',
      '#suffix' => " \n".'</div>',
  );
  
  // add a submit button
  $dn_submit_name = 'hd-submit';
  $form['hdsubmit'] = array(
      '#name' => $dn_submit_name,
      '#type' => 'submit',
      '#value' => 'Click to display the selected HD',
      '#prefix' => " \n".'<div style="float:left;">',
      '#suffix' => " \n".'</div>'." \n".'</div>',
  );
  

  
  $form['csv'] = array(
      '#type' => 'markup',
      '#prefix' => '</td><td style = "font-size:x-small;">',
      '#markup' => 'File with NL table content in CSV format.  ',
    );
  
  $form['csvsubmit'] = array(
    '#name' => 'download-csv',
    '#type' => 'submit',
    '#value' => 'Download table content >>',
  ); 
  
  $form['options-end'] = array(
      '#type' => 'markup',
      '#prefix' => '</td></tr></table>',
  );
  
}
