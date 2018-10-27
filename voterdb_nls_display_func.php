<?php
/*
 * Name: voterdb_nls_display_func.php   V4.3  10/17/18
 *
 */
/*
 * voterdb_build_nls_tbl, 
 * voterdb_checkbox_callback, voterdb_textbox_callback, 
 * voterdb_selectbox_callback
 */

use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpReports;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_nls_table
 * 
 * This function builds an HTML table using the array of information about
 * the NLs found in $form_state.
 * 
 * @param type $form
 * @param type $form_state
 * @return string - $form - form elements for the table.
 */
function voterdb_build_nls_table(&$form,&$form_state) {
  $nlRecords = $form_state['voterdb']['nlRecords'];
  $nlsObj = $form_state['voterdb']['nlsObj'];
  $reportsObj = new NlpReports();
  
  // Create the AJAX wrapper for updating the form changes.
  $form['nlform'] = array(
    '#prefix' => '<div id="nlform-div">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
    '#attributes' => array('style' => array('background-image:none; border:0px;'
      . ' padding:0px; margin:0px; background-color:rgb(255,255,255);'), ),
  );
  // Start the table.
  $form['nlform']['table_start'] = array(
    '#markup' => " \r ".'<table class="tborder nowhite" '
      . 'style = "font-size:x-small; font-family: Trebuchet, Verdana, Arial, Sans-serif;">',
  );

  // Now construct the header information for each column title.  Uses the th
  // table element.

  $nf_hdr_row = " \n  ".'<th class ="hborder cell-hd cell-bold">HD</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-pct cell-bold">Pct</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-name cell-bold">Name-MCID</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-addr cell-bold">Address</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-email cell-bold">Email-Phone</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-text cell-bold">Notes</th>';
  
  $nf_hint = '<span>The current status of the <u><b>NL</b></u> for this cycle.'
              . '  The status is automatically set to "yes" when a turf is checked in.'
              . '  If this NL is no longer asked to participate, remove the activist code'
              . ' in MyCampaign.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-ask cell-bold cell-color"><div id="hintth" ><p>NL'.$nf_hint.'</p></div></th>';
  
  $nf_hint = '<span style="position: absolute; width: 300px; left: -100px; top: -50px; ">'
              . 'A <u>T</u>urf has been <u>C</u>ut for the NL and checked into NLP Services.'
              . '  This box is checked automatically when a turf is checked in.'
              . '  It can be set manually as well.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-chk cell-bold cell-color"><div id="hintth" ><p>TC'.$nf_hint.'</p></div></th>';
  
  $nf_hint = '<span style=" position: absolute; width: 300px; left: -100px; top: -70px; ">'
              . 'The <u>T</u>urf was <u>D</u>elivered to the NL.'
              . '  This box is checked automatically when the turf packet is sent to the NL via the Admin email function.'
              . '  Once this box is checked he NL has 7 days to login.'
              . '  Checking this box also enables the counts of contacts and contact attempts.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-chk cell-bold cell-color"><div id="hintth" ><p>TD'.$nf_hint.'</p></div></th>';
  
  $nf_hint = '<span style=" position: absolute; width: 300px; left: -100px; top: -70px; ">'
              . 'The <u>CO</u>ntact type box indicates the method of contact committed by the NL.'
              . '  For those sending postcards, this option enables the instrucitonf for sending a postcard.'
              . '  The instructions are included in the email sent from the Admin funciton.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-type cell-bold cell-color"><div id="hintth" ><p>CO'.$nf_hint.'</p></div></th>';
  
  $nf_hint = '<span style=" position: absolute; width: 200px; left: -100px; top: -50px; ">'
              . 'The NL has <u>L</u>ogged <u>I</u>n to NLP Services to get the turf.'
              . '  The display is the date of the last login by the NL.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-lin cell-bold cell-color"><div id="hintth" ><p>LI'.$nf_hint.'</p></div></th>';
  
  $nf_hint = '<span style=" position: absolute; width: 200px; left: -145px; top: -40px; ">'
              . 'Count of the contact <u>attempts</u> with voters.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-atmp cell-bold cell-color"><div id="hintth" ><p>Atmps'.$nf_hint.'</p></div></th>';
  
  $nf_hint = '<span style=" position: absolute; width: 200px; left: -175px; top: 20px; ">'
              . 'Count of voters who have given and answer to the survey question.  '
          . 'This will usually be a Pledge to Vote question.</u>.</span>';
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-cont cell-bold cell-color"><div id="hintth" ><p>P2V'.$nf_hint.'</p></div></th>';
  
  
  //$nf_sortable = array();
  //$form_state['voterdb']['sortable'] = $nf_sortable;
  // Create the header row.
  $form['nlform']['header_row'] = array(
    '#markup' => " \n <thead> \n <tr>".$nf_hdr_row." \n </tr> \n </thead> ",
  );
  // Start the table body.
  $form['nlform']['nlbody-start'] = array(
    '#markup' => " \n ".'<tbody>',
  );
  // Display a row for each NL in the list.
  $nf_ask = $nlsObj->askList;
  $nf_contact = $nlsObj->contactList;

  $nf_row = 0;
  foreach ($nlRecords as $nlRecord) {
    
    $nf_mcid = $nlRecord['mcid'];
    // Use the Drupal class for odd/even table rows and start the row.
    if($nf_row%2 == 0) {$nf_row_style = " \n ".'<tr class="odd nowhite">';
    } else {$nf_row_style = " \n ".'<tr class="even nowhite">';} 
    $form['nlform']["row-start$nf_row"] = array('#markup' => $nf_row_style,);

    $hdValue = '<span style="font-weight:bold;">'.$nlRecord['hd'].'</span>';
    $nf_cell = " \n ".'<td class="cell-hd nowhite">'.$hdValue.'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-hd'] = array(
      '#markup' => $nf_cell,
    );
    
    $hdValue = '<span style="font-weight:bold;">'.$nlRecord['pct'].'</span>';
    $nf_cell = " \n ".'<td class="cell-pct nowhite">'.$hdValue.'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-pct'] = array(
      '#markup' => $nf_cell,
    );
    
    $nf_leader_col = '<span style="font-weight:bold;">'.$nlRecord['lastName'].",".$nlRecord['nickname'].'</span><br>'.$nf_mcid;
    $nf_cell = " \n ".'<td class="cell-name nowhite">'.$nf_leader_col.'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-name'] = array(
      '#markup' => $nf_cell,
    );
    
    $nf_addr_col = str_replace(',', '<br>', $nlRecord['address']);
    $nf_cell = " \n ".'<td class="cell-addr nowhite">'.$nf_addr_col.'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-addr'] = array(
      '#markup' => $nf_cell,
    );
    
    $nf_email_col = $nlRecord['email'].'<br>'.$nlRecord['phone'];
    $nf_cell = " \n ".'<td class="cell-email nowhite">'.$nf_email_col.'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-email'] = array(
      '#markup' => $nf_cell,
    );
    
    
    $nf_note_default = $nlRecord['status']['notes'];
    $notesWrap = $nlsObj::NOTESWRAP;
    $nf_wrap = wordwrap($nf_note_default,$notesWrap,"\n",true);
    $form['nlform']['TB-'.$nf_mcid.'-notes'] = array(
      '#type' => 'textarea',
      '#attributes' => array('class' => array('textarea-width nowhite')),
      '#cols' => 10,
      '#rows' => 2,
      '#resizable' => FALSE,
      '#prefix' => " \n ".'<td class="cell-text">',
      '#suffix' => '</td>',
      '#ajax' => array(
        'callback' => 'voterdb_textbox_callback',
        'wrapper' => 'nlform-div',
        ),
      '#default_value' => $nf_wrap,
    );
    
    //voterdb_debug_msg('nlrecord', $nlRecord);
    //voterdb_debug_msg('aask', $nf_ask);
    //$nf_asked = $nlRecord['status']['asked'];
    
    //$nf_default_ask = $nlsObj->askList[$nf_asked];
    $nf_default_ask = $nlRecord['status']['asked'];
    
    $form['nlform']["AS-".$nf_mcid.'-ask'] = array(
      '#type' => 'select',
      '#options' => $nf_ask,
      '#prefix' => " \n ".'<td class="cell_ask">',
      '#suffix' => '</td>',
      '#ajax' => array(
        'callback' => 'voterdb_selectbox_callback',
        'wrapper' => 'nlform-div',
        '#event' => 'change',
        ),
      '#default_value' => $nf_default_ask,
      );
    
    

    $nf_default_tc = ($nlRecord['status']['turfCut']=='Y')?1:0;
    $form['nlform']["TC-".$nf_mcid.'-turfcut'] = array (
    '#type' => 'checkbox',
    '#default_value' => $nf_default_tc,
    '#prefix' => " \n ".'<td class="cell-chk">',
    '#suffix' => '</td>',
    '#ajax' => array(
      'callback' => 'voterdb_checkbox_callback',
      'wrapper' => 'nlform-div',
      ),
    );
    
    $nf_default_td = ($nlRecord['status']['turfDelivered']=='Y')?1:0;
    $form['nlform']["TD-".$nf_mcid.'-turfdelivered'] = array (
    '#type' => 'checkbox',
    '#default_value' => $nf_default_td,
    '#prefix' => " \n ".'<td class="cell-chk">',
    '#suffix' => '</td>',
    '#ajax' => array(
      'callback' => 'voterdb_checkbox_callback',
      'wrapper' => 'nlform-div',
      ),
    );
    
    
    $nf_co_default = $nlRecord['status']['contact'];
    $form['nlform']["CO-".$nf_mcid.'-contact'] = array(
      '#type' => 'select',
      '#options' => $nf_contact,
      '#prefix' => " \n ".'<td class="cell-type">',
      '#suffix' => '</td>',
      '#ajax' => array(
        'callback' => 'voterdb_selectbox_callback',
        'wrapper' => 'nlform-div',
        '#event' => 'change',
        ),
      '#default_value' => $nf_co_default,
      );
    
    
    if($nf_default_tc) {
      $nf_cell = " \n ".'<td class="cell-lin nowhite">'.$nlRecord['status']['loginDate'].'</td>';
    } else {
      $nf_cell = " \n ".'<td class="cell-lin nowhite"></td>';
    }
    $form['nlform']['TX-'.$nf_mcid.'-login'] = array(
      '#markup' => $nf_cell,
    );
    
    $nf_cell = " \n ".'<td class="cell-atmp nowhite">'.$nlRecord['progress']['attempts'].'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-atmps'] = array(
      '#markup' => $nf_cell,
    );
    
    $nf_cell = " \n ".'<td class="cell-cont nowhite">'.$nlRecord['progress']['contacts'].'</td>';
    $form['nlform']['TX-'.$nf_mcid.'-conts'] = array(
      '#markup' => $nf_cell,
    );
    

    // End of row.
    $form['nlform']["row-end$nf_row"] = array(
      '#markup' => " \n ".'</tr>',
     );
    $nf_row++;
  } 
  // End of table body.
  $form['nlform']['nlbody-end'] = array(
    '#markup' => " \n ".'</tbody>',
    );
  // End of the table.
  $form['nlform']['table_end'] = array(
    '#markup' => " \n ".'</table>'." \n ",
    );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_checkbox_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_checkbox_callback($form, &$form_state) {
  return $form['nlform'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_textbox_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_textbox_callback($form, &$form_state) {
  return $form['nlform'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_selectbox_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_selectbox_callback($form, &$form_state) {
  return $form['nlform'];
}
