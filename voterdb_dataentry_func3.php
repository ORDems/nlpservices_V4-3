<?php
/*
 * Name: voterdb_dataentry_func3.php    V4.1  5/20/18
 */
/** * * * * * functions supported * * * * * *
 * voterdb_canvass_date, voterdb_turf_select, voterdb_display_goals, 
 * voterdb_history_option, voterdb_lists, voterdb_instruct_disp,
 * voterdb_coordinator_disp
 */

use Drupal\voterdb\NlpPaths;

define('DE_MONTH_ARRAY', serialize(array(
    'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
    'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC')));
define('DE_DAY_ARRAY', serialize(array(
    '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
    '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
    '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31')));

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_canvass_date
 *
 * Format a date entry for the canvass date and put it in the form generator
 * entry  The current date is the default.  
 * 
 * @param type $form_state
 * @return array - form element.
 */
function voterdb_canvass_date(&$form_state){
  $form_element['dform'] = array(
      '#type' => 'fieldset',
      '#attributes' => array(
        'style' => array('background-image:none; '
        . 'border-collapse:collapse; border-style: hidden; '
        . 'padding:0px; margin:0px; background-color:rgb(255,255,255);'), ),
      '#prefix' => '<div id="dform-div" style = "float: left;">',
      '#suffix' => '</div>',  
  );
  $form_element['dform']['date-title'] = array(
      '#markup' => " \n".'<!-- Canvass date table -->'." \n".'<table style="width:240px; '
    . 'border-collapse:collapse; border-style:hidden;" class="nowhite">'
    . " \n".'<tbody class="nowhite">'
    . " \n".'<tr>'." \n ".'<td class="nowhite" style="line-height:100%;">'
    . '<strong>Canvass Date</strong></td>'
      );
  if(!isset($form_state['voterdb']['date-month'])) {
    $cd_month_default = format_date(time(), 'custom', 'n')-1;
    $cd_day_default = format_date(time(), 'custom', 'j')-1;
    $form_state['voterdb']['date-month'] = $cd_month_default;
    $form_state['voterdb']['date-day'] = $cd_day_default;
  } else {
    $cd_month_default = $form_state['voterdb']['date-month'];
    $cd_day_default = $form_state['voterdb']['date-day'];
  }
  $cd_month = unserialize(DE_MONTH_ARRAY);
  $cd_month_default = format_date(time(), 'custom', 'n')-1;
  $form_element['dform']["MO-0"] = array(
      '#type' => 'select',
      '#options' => $cd_month,
      '#attributes' => array(
        'class' => array('nowhite'),
        'style' => array('line-height:100%; margin:0px; padding:0px; font-size:x-small;'),),
      '#prefix' => " \n ".'<td class="nowhite" style="font-size:x-small;">',
      '#suffix' => '</td>',
      '#ajax' => array(
        'callback' => 'voterdb_dselect_callback',
        'wrapper' => 'dform-div',
        '#event' => 'change',
        ),
      '#default_value' => $cd_month_default,
      );
  $cd_day = unserialize(DE_DAY_ARRAY);
  $cd_day_default = format_date(time(), 'custom', 'j')-1;
  $form_element['dform']["DY-0"] = array(
      '#type' => 'select',
      '#options' => $cd_day,
      '#attributes' => array(
        'class' => array('nowhite'),
        'style' => array('line-height:100%; margins:0px; padding:0px; font-size:x-small;'),),
      '#prefix' => " \n ".'<td class="nowhite" style="font-size:x-small;">',
      '#suffix' => '</td>'." \n".'</tr>'." \n".'</tbody>'." \n".'</table>'." \n".'<!-- End of canvass date table -->'." \n",
      '#ajax' => array(
        'callback' => 'voterdb_dselect_callback',
        'wrapper' => 'dform-div',
        '#event' => 'change',
        ),
      '#default_value' => $cd_day_default,
      );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_select
 * 
 * Create a submit button so the NL can change the turf (when the NL has more
 * than one turf.  NLs with multiple turfs were confused by the need to log
 * in for each turf.
 * 
 * @param type $form_state
 */
function voterdb_turf_select($form_state) {
  $form_element['turf-select']["TF-0"] = array (
      '#type' => 'submit',
      '#id' => 'show-turfs',
      '#value' => 'Back to turf select',
      '#prefix' => '<div id="next-turf-div" style = "float: left; margin-left: 150px;">',
      '#suffix' => ' You have more than one turf.  Click this button to select another turf.</div>',  
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_goals
 *
 * Create the goals form.  Create one line for the county goal and one line
 * for the HD associated with this NL.  The rows will be the NL recruitment
 * goal, the progress with recruitment, and the progress for reporting by
 * the recuited NLs.  The hope is to encourage NLs to help recruitment and 
 * to encourage reporting.  The information is stored in a table and left 
 * adjusted by being encapsulated in an aside.
 * 
 * @param type &$form_state - group name, HD
 * @return string - form element for display of goals in a left aside.
 */
function voterdb_display_goals($form_state) {
  $dg_county = $form_state['voterdb']['county'];
  $dg_hd = $form_state['voterdb']['HD'];
  // Get the recruitment goal and progress in reporting results for the county.
  $dg_cogoal = voterdb_get_goals($dg_county,'ALL');  // func4.
  $dg_nl_recruited = voterdb_get_nlscount($dg_county,'ALL',NN_NLSIGNUP);//func4.
  $dg_rpt = voterdb_get_nlscount($dg_county,'ALL',NN_RESULTSREPORTED);//func4.
  // Get the goal and progress for the house district for this NL.
  $dg_hdgoal = voterdb_get_goals($dg_county,$dg_hd);
  $dg_hd_recruited = voterdb_get_nlscount($dg_county,$dg_hd,NN_NLSIGNUP);//func4.
  $dg_hd_rpt = voterdb_get_nlscount($dg_county,$dg_hd,NN_RESULTSREPORTED);//func4.
  // Build the styles for the form generator entry for the display of the goals
  // and for the display of the links for walk sheet PDF and call sheet.  Also,
  // Create the header for the columns in the table.
  $form_element['goals-tbl'] = array (
    '#markup' => " \n  ".'<!-- Goals table -->'." \n  ".'<table style="width:315px;" class="noborder nowhite">'.
    " \n  ".'<tbody style="border-collapse: collapse; border-style: hidden;">'.
    " \n  ".'<tr>'.
    " \n   ".'<td class="nowhite" style="width:120px;font-weight: bold;">NL Recruitment</td>'.
    " \n   ".'<td class="goal-hdr nowhite" style="margin-top:0px;">Goal</td>'.
    " \n   ".'<td class="goal-hdr">Recruited</td>'.
    " \n   ".'<td class="goal-hdr nowhite" style="margin-top:0px;">Reporting</td>'.
    " \n  ".'</tr>'
      );
  // Create the form generator entry for the county goal (one line).
   $form_element['goals-co'] = array (
     '#markup' => " \n  ".'<tr>'.
     " \n   ".'<td>County</td>'.
     " \n   ".'<td class="goal-tx" style="margin-top:0;">'.$dg_cogoal.'</td>'.
     " \n   ".'<td class="goal-tx nowhite" style="margin-top:0px;">'.$dg_nl_recruited.'</td>'.
     " \n   ".'<td class="goal-tx nowhite" style="margin-top:0px;">'.$dg_rpt.'</td>'.
     " \n  ".'</tr>'
      );
   // Create the form generator entry for the HD goal (one line) and terminate
   // the table.
   $form_element['goals-hd'] = array (
      '#markup' => " \n  ".'<tr>'.
     " \n   ".'<td style="margin-top:0px;">HD'.$dg_hd.'</td>'.
     " \n   ".'<td class="goal-tx nowhite" style="margin-top:0px;">'.$dg_hdgoal.'</td>'.
     " \n   ".'<td class="goal-tx nowhite" style="margin-top:0px;">'.$dg_hd_recruited.'</td>'.
     " \n   ".'<td class="goal-tx nowhite" style="margin-top:0px;">'.$dg_hd_rpt.'</td>'.
     " \n  ".'</tr>'.
     " \n  ".'</tbody>'.
     " \n  ".'</table>'." \n  ".'<!-- End of Goals table -->'
      );
   return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_history_option
 * 
 * Create a small box to sit between the goals and the right aside.  This will
 * contain a check box to change the display of historical contacts with the
 * voters in this turf.
 * 
 * @param type $form_state
 * @return string - form element for display of box.
 */
function voterdb_history_option($form_state) {
  $form_element['history-title'] = array(
    '#markup' => " \n  ".'<section class="nowhite" style="width:100px; text-align:center;  
       margin-left:auto; margin-right:auto;">Display historical contacts.',
  );
  $form_element['HI-0'] = array(
    '#type' => 'checkbox',
    '#attribute' => array('class' => array('nowhite')),
    '#ajax' => array(
        'callback' => 'voterdb_checkbox_callback',
        'wrapper' => 'vform-div',
        ),
  );
  $form_element['history-end'] = array(
    '#markup' => "  \n ".'</section>',
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_lists
 *
 * Create a container with right adjust for display of the links to get
 * a copy of the walksheet PDF and the call list.  Also, for a county
 * the NL can see the other NLs in the HD.
 * 
 * @param type $form_state
 * @return string - form element for display of right aside.
 */
function voterdb_lists($form_state) {
  //$ra_mcid = $form_state['voterdb']['mcid'];
  $turfIndex = $form_state['voterdb']['turfIndex'];
  $turf = $form_state['voterdb']['turfArray']['turfs'][$turfIndex];
  
  //voterdb_debug_msg('turf array', $form_state['voterdb']);
  $ra_county = $form_state['voterdb']['county'];
  $ra_call_file = $form_state['voterdb']['call-file'];
  $form_element['lists'] = array (
      '#type' => 'markup',
      '#markup' => "  \n ".'<section style="width:360px; margin:0px; padding:5px;'
    . ' border: 1px solid #3090cc;" class="nowhite">',
    );
  // Get the PDF and Mail list file name associated with this turf.
  //$pathsObj = $form_state['voterdb']['pathsObj'];
  $pathsObj = new NlpPaths();
  $ra_turf_pdfname = $turf['TurfPDF'];  // The PDF file name.
  // If we have a PDF, add the link to it in the aside box.
  if ($ra_turf_pdfname!='') {
    // add the path to the PDF file name.
    //$ra_pdf_path = voterdb_get_path('PDF',$ra_county);
    $ra_pdf_path = $pathsObj->getPath('PDF',$ra_county);
    $ra_turf_pdfname = $ra_pdf_path . $ra_turf_pdfname;
    $ra_url = file_create_url($ra_turf_pdfname);
    $ra_link =  '<span style="font-weight:bold; color: #ed174f;">Get your walk sheet: </span><a href="'.$ra_url.'" target="_blank"> Click Here</a>';
    $form_element['other-pdf'] = array(
        '#type' => 'markup',
        '#markup' => $ra_link,
        '#prefix' => " \n   ".'<section class="rt" style="margin:0px;">',
        '#suffix' => " \n   ".'</section>',
    );
  }
  //voterdb_debug_msg('pdf', '');
  // Mail list.
  $ra_mail_pathname = $GLOBALS['base_url'] .'/'.VO_MAILLIST_PAGE;
  
  $ra_mail_path = $pathsObj->getPath('MAIL',$ra_county);
  //$ra_mail_path = voterdb_get_path('MAIL',$ra_county);
  $ra_turf_mailname = $turf['TurfMail'];  // The txt file name.
  $ra_mail_uri = $ra_mail_path.$ra_turf_mailname;
  $ra_mail_url = file_create_url($ra_mail_uri);
  $ra_mail_link = 'Get a postcard mailing list: <a href="'.$ra_mail_pathname. '?FileName='.$ra_mail_url.'" target="_blank">Click here</a> ';
  $form_element['other-maillist'] = array(
      '#type' => 'markup',
      '#markup' => $ra_mail_link,
      '#prefix' => '<section class="rt" style="margin-top:5px;">',
      '#suffix' => '</section>'
  );
  // Now add the link to the call list and terminate the aside.
  // Path to the page that displays the call list.
  $ra_call_pathname = $GLOBALS['base_url'] .'/'.VO_CALLLIST_PAGE;
  $ra_call_url = file_create_url($ra_call_file);
  $ra_GOTV_link = 'Get a GOTV Call list: <a href="'.$ra_call_pathname. '?FileName='.$ra_call_url.'" target="_blank">Click here</a> ';
  $form_element['other-calllist'] = array(
      '#type' => 'markup',
      '#markup' => $ra_GOTV_link,
      '#prefix' => " \n   ".'<section class="rt" style="margin-top:5px;">',
      '#suffix' => " \n   ".'</section>'." \n  "
  );
  /*
  // See if the NL wants to see if there are others.
  $form_element['other-note'] = array (
      '#type' => 'markup',
      '#markup' => 'See who else is a Neighborhood Leader: ',
      '#prefix' => " \n   ".'<section class="nowhite">',
  );
  // Insert a submit button for the NL to select the list of other NLs.
  $form_element['other-submit'] = array (
      '#type' => 'submit',
      '#id' => 'show-list',
      '#value' => 'Show List',
  );
   * 
   */
  $form_element['other-end'] = array (
      '#type' => 'markup',
      '#markup' => " \n   ".'</section>',
  );
  
  
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_instruct_disp
 * 
 * Create a small box for display for the NL with links to the PDF files
 * for the instructions for the canvass and for the postcard if needed.
 * 
 * @param type $form_state
 * @return array - form elements for the display.
 */
function voterdb_instruct_disp($form_state) {
  global $base_url;
  $id_county = $form_state['voterdb']['county'];
  // Start of table.
  $form['inst-box-c1'] = array(
        '#markup' => " \n".'<div>'.
        " \n".'<!-- Inst box table -->'." \n".'<table style="width:120px; margin:0px; padding:0px; border: 1px solid #3090cc; ">'.
        " \n".'<thead>'.
        " \n".'<tr>'.
        " \n ".'<th style="width:200px; margin:0px; padding:0px 2px; background-color: #3090cc; color: #ffffff; ">Instructions'.'<!-- goals cell -->',
      );
  // End of header and start of body.
  $form['inst-box-c2'] = array('#markup'=>" \n ".'</th></tr><tbody><tr>'.
        " \n ".'<td style="width:100px; margin:0px; padding:0px 2px">',);
  // Create the URL to the instructions for this canvass (or postcard).
  $id_instructions = voterdb_get_instructions($id_county);
  //voterdb_debug_msg('instructions', $id_instructions);
  $id_path = voterdb_get_path('INST',$id_county);
  
  
  $id_canvass = $id_instructions[NE_CANVASS][NI_FILENAME];
  if(!empty($id_canvass)) {
    $id_curl = file_create_url($id_path.$id_instructions[NE_CANVASS][NI_FILENAME]);
    $id_cmessage = t('<a href="@c-url" target="_blank">Canvass</a>',
      array('@c-url' => $id_curl));
    $form['inst-box-c3'] = array(
        '#markup' => " \n ".$id_cmessage,
      );
    }
    
  $id_postcard = $id_instructions[NE_POSTCARD][NI_FILENAME];
  if(!empty($id_postcard)) {
    $id_purl = file_create_url($id_path.$id_instructions[NE_POSTCARD][NI_FILENAME]);
    $id_pmessage = t('<a href="@p-url" target="_blank">Postcard</a>',
      array('@p-url' => $id_purl));
    $form['inst-box-c4'] = array('#markup'=>" \n ".'</td></tr><tr>'.
        " \n ".'<td style="width:100px; margin:0px; padding:0px 2px">',);
    $form['inst-box-c5'] = array(
        '#markup' => " \n ".$id_pmessage,
      );
  }
  
  $id_absentee = $id_instructions[NE_ABSENTEE][NI_FILENAME];
  if(!empty($id_absentee) AND $id_absentee != 'not uploaded yet') {
    $id_aurl = file_create_url($id_path.$id_instructions[NE_ABSENTEE][NI_FILENAME]);
    $id_title = $id_instructions[NE_ABSENTEE][NI_TITLE];
    $id_amessage = t('<a href="@a-url" target="_blank">'.$id_title.'</a>',
      array('@a-url' => $id_aurl));
    $form['inst-box-c4'] = array('#markup'=>" \n ".'</td></tr><tr>'.
        " \n ".'<td style="width:100px; margin:0px; padding:0px 2px">',);
    $form['inst-box-c5'] = array(
        '#markup' => " \n ".$id_amessage,
      );
  }
  
  $form['inst-box-c6'] = array('#markup' => " \n ".'</td>'.
        " \n".'</tr>'.
        " \n".'</tbody>'.
        " \n".'</table>'." \n".'<!-- End of Inst box table -->'.
        " \n".'</div>');
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_coordinator_disp
 * 
 * Create a box with the contact information for the coordinator for this NL.
 * 
 * @param type $form_state
 * @return array - form element for display.
 */
function voterdb_coordinator_disp($form_state) {
  $cd_region['coordinators'] = voterdb_coordinators_getall();
  $cd_region['hd'] = $form_state['voterdb']['HD'];
  $cd_region['pct'] = $form_state['voterdb']['pct'];
  $cd_region['county'] = $form_state['voterdb']['county'];
  $cd_co = voterdb_get_coordinator($cd_region);
  if(empty($cd_co)) {return NULL;}
  $cd_fname = $cd_co[CR_FIRSTNAME];
  $cd_lname = $cd_co[CR_LASTNAME];
  $cd_email = $cd_co[CR_EMAIL];
  $cd_phone = $cd_co[CR_PHONE];
  $form['co-box-c1'] = array(
        '#markup' => " \n".'<div>'.
        " \n".'<!-- Co box table -->'." \n".'<table style="width:190px; margin:0px; padding:0px; border: 1px solid #3090cc; ">'.
        " \n".'<thead>'.
        " \n".'<tr>'.
        " \n ".'<th style="width:200px; margin:0px; padding:0px 2px; background-color: #3090cc; color: #ffffff; ">Coordinator',
      );
  // End of header and start of body.
  $form['co-box-c2'] = array('#markup'=>" \n ".'</th></tr><tbody><tr>'.
        " \n ".'<td style="width:100px; margin:0px; padding:0px">',);
  $form['co-box-c4'] = array('#markup'=>" \n ".'</td></tr><tr>'.
        " \n ".'<td style="width:100px; margin:0px; padding:0px 0px 4px 2px; font-size:x-small; line-height:100%;">',);
  $form['co-box-c5'] = array(
        '#markup' => " \n ".$cd_fname.' '.$cd_lname.'<br>'.$cd_phone.'<br>'.
    $cd_email,
      );
  $form['co-box-c6'] = array('#markup' => " \n ".'</td>'.
        " \n".'</tr>'.
        " \n".'</tbody>'.
        " \n".'</table>'." \n".'<!-- End of Co box table -->'.
        " \n".'</div>');
  return $form;
}
