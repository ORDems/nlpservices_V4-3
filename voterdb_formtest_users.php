<?php
/*
 * Name: voterdb_formtest_users.php   V4.3 8/15/18
 * 
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_magic_word.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";



use Drupal\voterdb\NlpDrupalUser;
use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpMagicWord;



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
   '.cell-name {text-align:left; width:100px; padding:0px;}
    .cell-addr {text-align:left; width:130px; padding:0px; }
    .cell-email {text-align:left; width:150px; padding:0px; }
    .cell-checkbox {text-align:left; width:20px; padding:0px;}
    '
  );



function voterdb_display_users($county) {
  
  
  $queryObj = new EntityFieldQuery();
  $userObj = new NlpDrupalUser();
  
  $users = $userObj->getUsers($queryObj,$county);
  
  $nlObj = new NlpNls();
  
  foreach ($users as $uid => $user) {
    if(!empty($user['mcid'])) {
      $nl = $nlObj->getNlById($user['mcid']);
      voterdb_debug_msg('nl: '.$uid, $nl);
      $users[$uid]['hd'] = $nl['hd'];
    } else {
      $users[$uid]['hd'] = '';
    }
  }
  
  voterdb_debug_msg('Users', $users);
  
  $form_element['nlform'] = array(
    '#type' => 'fieldset',
    '#attributes' => array('style' => array('background-image:none; border:0px;'
      . ' padding:0px; margin:0px; background-color:rgb(255,255,255);'), ),
  );
  // Start the table.
  $form_element['nlform']['table_start'] = array(
    '#markup' => " \r ".'<table class="tborder nowhite" '
      . 'style = "font-size:x-small; font-family: Trebuchet, Verdana, Arial, Sans-serif;">',
  );

  // Now construct the header information for each column title.  Uses the th
  // table element.

  $nf_hdr_row = " \n  ".'<th class ="hborder cell-checkbox cell-bold">Edit</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-checkbox cell-bold">HD</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-name cell-bold">MCID</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-name cell-bold">Last Name</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-name cell-bold">First Name</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-name cell-bold">Username</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-email cell-bold">Phone</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-email cell-bold">Email</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-email cell-bold">Shared Email</th>';
  
  $nf_hdr_row .= " \n  ".'<th class ="hborder cell-name cell-bold">Roles</th>';

  // Create the header row.
  $form_element['nlform']['header_row'] = array(
    '#markup' => " \n <thead> \n <tr>".$nf_hdr_row." \n </tr> \n </thead> ",
  );
  // Start the table body.
  $form_element['nlform']['nlbody-start'] = array(
    '#markup' => " \n ".'<tbody>',
  );

  $nf_row = 0;
  foreach ($users as $user) {
    
    $nf_mcid = $user['mcid'];
    // Use the Drupal class for odd/even table rows and start the row.
    if($nf_row%2 == 0) {$nf_row_style = " \n ".'<tr class="odd nowhite">';
    } else {$nf_row_style = " \n ".'<tr class="even nowhite">';} 
    $form_element['nlform']["row-start$nf_row"] = array('#markup' => $nf_row_style,);
    

    $form_element['nlform']['CB-'.$nf_mcid.'-checkbox'] = array(
      '#type' => 'checkbox',
    );

    
    $value = '<span style="font-weight:bold;">'.$user['hd'].'</span>';
    $nf_cell = " \n ".'<td class="cell-checkbox nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-hd'] = array(
      '#markup' => $nf_cell,
    );

    $value = '<span style="font-weight:bold;">'.$user['mcid'].'</span>';
    $nf_cell = " \n ".'<td class="cell-name nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-mcid'] = array(
      '#markup' => $nf_cell,
    );
    
    $value = '<span style="font-weight:bold;">'.$user['lastName'].'</span>';
    $nf_cell = " \n ".'<td class="cell-name nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-lastname'] = array(
      '#markup' => $nf_cell,
    );
    
    $value = '<span style="font-weight:bold;">'.$user['firstName'].'</span><br>';
    $nf_cell = " \n ".'<td class="cell-name nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-firstname'] = array(
      '#markup' => $nf_cell,
    );
    
    $value = '<span style="font-weight:bold;">'.$user['userName'].'</span><br>';
    $nf_cell = " \n ".'<td class="cell-name nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-username'] = array(
      '#markup' => $nf_cell,
    );
    
    $value = '<span style="font-weight:bold;">'.$user['phone'].'</span><br>';
    $nf_cell = " \n ".'<td class="cell-email nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'phone'] = array(
      '#markup' => $nf_cell,
    );
    
    $value = '<span style="font-weight:bold;">'.$user['email'].'</span><br>';
    $nf_cell = " \n ".'<td class="cell-email nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-email'] = array(
      '#markup' => $nf_cell,
    );
    
    $value = '<span style="font-weight:bold;">'.$user['sharedEmail'].'</span><br>';
    $nf_cell = " \n ".'<td class="cell-email nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-sharedemail'] = array(
      '#markup' => $nf_cell,
    );
    $roleDisplay = '';
    $roles = $user['roles'];
    foreach ($roles as $role) {
      if(!empty($roleDisplay)) {
        $roleDisplay .= '<br>';
      }
       $roleDisplay .= $role;
    }
    
    $value = '<span style="font-weight:bold;">'.$roleDisplay.'</span><br>';
    $nf_cell = " \n ".'<td class="cell-email nowhite">'.$value.'</td>';
    $form_element['nlform']['TX-'.$nf_mcid.'-roles'] = array(
      '#markup' => $nf_cell,
    );

    
 
    // End of row.
    $form_element['nlform']["row-end$nf_row"] = array(
      '#markup' => " \n ".'</tr>',
     );
    $nf_row++;
  } 
  // End of table body.
  $form_element['nlform']['nlbody-end'] = array(
    '#markup' => " \n ".'</tbody>',
    );
  // End of the table.
  $form_element['nlform']['table_end'] = array(
    '#markup' => " \n ".'</table>'." \n ",
    );
  return $form_element;
  
  
  
  
  
  
}


function voterdb_test_form($form,&$form_state) {
  
  
  
  drupal_add_css(DC_FORMITEM.DC_NLTBL.DC_NLCELL, array('type' => 'inline'));
  
  
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['reenter'] = TRUE;
    $form_state['voterdb']['page'] = 'chooseCounty';
  }
  
  $county = $form_state['voterdb']['county'];
  
  $page = $form_state['voterdb']['page'];
  $banner = voterdb_build_banner ($county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $banner
  );

  $queryObj = new EntityFieldQuery();
  $userObj = new NlpDrupalUser();
  $currentUser = $userObj->getCurrentUser();
  
  $adminRole = FALSE;
  $roles = $currentUser['roles'];
  foreach ($roles as $role) {
    if($role == 'NLP Admin') {
      $adminRole = TRUE;
      break;
    }
  }
  
  //voterdb_debug_msg('roles', $roles);
  
  $users = $userObj->getUsers($queryObj,$county);
  $numUsers = count($users);
  
  if($page=='chooseCounty') {
    if(!$adminRole) {
      $page = 'displayUsers';
    }
  }
  if($page=='displayUsers') {
    if($numUsers>150) {
      $page = 'chooseHd';
    }
  }
  
 
  
  
  
  
  switch ($page) {
    case 'chooseCounty':
      
      //$countiesObj = new NlpCounties();
      //$counties = $countiesObj->getCountyNames();
      
      
      $countyArray = $userObj->getCounties($queryObj);
      $counties = $countyArray['names'];
      voterdb_debug_msg('counties', $counties);
 
      $form['county'] = array(
        '#type' => 'select',
        '#title' => t('County'),
        '#options' => $counties,
      );
      
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
      
      break;
    
    case 'chooseHd':
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
      break;
    case 'displayUsers':
      $form['users'] = voterdb_display_users($county);
      
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
      break;
  }
  

  
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}

function voterdb_test_form_validate($form, &$form_state) {
  $page = $form_state['voterdb']['page'];
  switch ($page) {
    case 'chooseCounty':
      
      break;
    case 'chooseHd':
      break;
    case 'displayUsers':
      break;
  }
}

function voterdb_test_form_submit($form, &$form_state) {
  voterdb_debug_msg('values', $form_state['values']);
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist.
  $page = $form_state['voterdb']['page'];
  switch ($page) {
    case 'chooseCounty':
      $form_state['voterdb']['county'] = $form_state['values']['county'];
      $form_state['voterdb']['page'] = 'displayUsers';
      break;
    case 'chooseHd':
      break;
    case 'displayUsers':
      break;
  }
}