<?php
/*
 * Name: voterdb_turf_checkin_func2.php   V4.2  4/3/19
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */
/*
 * voterdb_get_duplicates,
 * voterdb_overlap_test, voterdb_turf_overlap
 */

use Drupal\voterdb\NlpDrupalUser;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_duplicates
 * 
 * Given the array from the search for voters assigned to more than one turf,
 * create an array of information for each voter that includes the address,
 * NL name, and the name of the existing turf.  This will be used to create
 * a display for the NL coordinator who attempted a turf checkin.
 * 
 * @param type $form_state 
 * 
 * @return - array of voter info for overlapped voters, includes info about
 *           NL and turf or FALSE for error.
 */
function voterdb_get_duplicates($form_state) {
  $vo_dup_voters = array();
  $vo_voters = $form_state['voterdb']['voters'];
  $vo_vanids = array();
  $vo_county = $form_state['voterdb']['county'];
  // Create an array of VANIDs from the turf being added for the SQL search.
  foreach ($vo_voters as $vo_voter) {
    $vo_vanids[] = $vo_voter[VN_VANID];
  }
  // Get all the existing grp records for the overlaps.
  db_set_active('nlp_voterdb');
    try {
    $vo_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $vo_query->fields('g');
    $vo_query->condition(NV_COUNTY,$vo_county);
    $vo_query->condition(NV_VANID,$vo_vanids,'IN');
    $vo_result = $vo_query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
  $vo_ol_vtrs = $vo_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  // If none, exit as we have a problem.
  if(empty($vo_ol_vtrs)) {return '';}
  // Build an array of overlapped voters and an associate array to ease access.
  $vo_ol_vanids = array();
  foreach ($vo_ol_vtrs as $vo_ol_vtr) {  
    $vo_ol_vanid = $vo_ol_vtr[NV_VANID];
    $vo_ol_vanids[] = $vo_ol_vanid;
    $vo_ol_grp_vtr[$vo_ol_vanid] = $vo_ol_vtr;
  }
  // Now get the voter information for all the overlapped voters.
  db_set_active('nlp_voterdb');
    try {
    $vo_vquery = db_select(DB_NLPVOTER_TBL, 'v');
    $vo_vquery->fields('v');
    $vo_vquery->condition(NV_VANID,$vo_ol_vanids,'IN');
    $vo_vresult = $vo_vquery->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
  $vo_dup_vtrs = $vo_vresult->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  // Build the associative array for information about the overlapped voters,
  // includes info about both the existing assignment of the voter to a turf
  // and the new attempt.
  foreach ($vo_dup_vtrs as $vo_dup_vtr) {  
    $vo_dup_vanid = $vo_dup_vtr[NV_VANID];
    $vo_dup_voters[$vo_dup_vanid] = $vo_dup_vtr;
    $vo_dup_voters[$vo_dup_vanid][NV_MCID] = $vo_ol_grp_vtr[$vo_dup_vanid][NV_MCID];
    $vo_dup_voters[$vo_dup_vanid][NV_NLTURFINDEX] = $vo_ol_grp_vtr[$vo_dup_vanid][NV_NLTURFINDEX];
    $vo_dup_voters[$vo_dup_vanid][NV_VOTERSTATUS] = $vo_ol_grp_vtr[$vo_dup_vanid][NV_VOTERSTATUS];
  }
  return $vo_dup_voters;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_overlap_test
 * 
 * Check if a voter record is in the voter grp table more than once.  Two 
 * occurances means there is an overlap.  The option selects a test if there 
 * is any overlap, or an overlap with another NL, or an overlap with this NL 
 * for multiple turfs.  In the case of  TURF, all turf indexes are returned,
 * one for each with overlapping voters.  The function returns an array of
 * distinct VANIDs of voters in the database twice.  Voters are marked as
 * "moved" are ignored and can be in the database twice.   The voter at a
 * new address may be assigned to another NL.
 * 
 * @param type $ot_type - ANY, NL or TURF.
 * @param type $ot_county - group name.
 * @return object - query result or FALSE if database error.
 */
function voterdb_overlap_test($ot_type,&$form_state) {
  $ot_duplicates = $form_state['voterdb']['duplicates'];
  switch ($ot_type) {
  case 'NL':  // Check if a replacement turf or overlap with some other NL.
    $ot_mcid = $form_state['voterdb']['mcid']; // MCID of the NL for the new turf.
    foreach ($ot_duplicates as $ot_dup_voter) {
      // Return if some other NL is assigned to a voter.
      if($ot_dup_voter[NV_MCID] != $ot_mcid) {return TRUE;}
    }
    //  Else, only this NL is overlapped with another turf.
    break;
  case 'TURF':  
    $ot_turfindex = "";  // Inintial condition, turf index not known.
    foreach ($ot_duplicates as $ot_dup_voter) {
      // Get the first turf index.
      if($ot_turfindex==='') {$ot_turfindex=$ot_dup_voter[NV_NLTURFINDEX];}
      // Return if a different turf index seen, ie we have multiple turfs in the list of overlapping voters.
      if($ot_dup_voter[NV_NLTURFINDEX] != $ot_turfindex) {return TRUE;}
    }
    // Return the index of the single overlapping turf (so it can be deleted).
    $form_state['voterdb']['overlapped-turf-index'] = $ot_turfindex;
    break;
  }
  return FALSE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_overlap
 *
 * Check if there is any overlap with an existing turf.  If not, we have
 * a new turf.   With overlap, we first check if the overlap is with some
 * other NL and if so, report the error.  The turf has to be corrected or the
 * other turf deleted.   If the overlap is with just this one NL, then we check
 * if there is overlap with more than one turf.   In this case, we return 
 * an error.   For the case of just one turf with overlap, we return the 
 * turf index so the older turf can be deleted.   We assume that the older
 * turf is being replaced with a newer one.
 *
 * @param $to_county - group name.
 * @param $to_new_turf_index - the index of the newly created turf.
 * @return string - status of overlap
 *       'OK' no overlap
 *       'err' one or more turfs overlap this one
 *       turfindex - index of the older turf
 *       FALSE if database error.
 */
function voterdb_turf_overlap(&$form_state) {
  $to_county = $form_state['voterdb']['county'];
  // Search for any duplicate VANIDs in the GRP Table - impies overlap.
  $to_duplicates = voterdb_get_duplicates($form_state);
  if($to_duplicates == '') {return 'OK';}
  $form_state['voterdb']['duplicates'] = $to_duplicates;
  // At least one voter is already assigned to a turf.
  // Now check if this overlap is with another NL.
  if (voterdb_overlap_test('NL',$form_state)) {
    drupal_set_message("This turf overlaps another turf for another NL.","warning");
    $to_overlap = 'multiple NLs';
  } else {
    if (!voterdb_overlap_test('TURF',$form_state)) {
      // An overlap exists just for this NL.    
      drupal_set_message("The existing turf for this NL will be deleted.","warning");
      $to_overlap = 'one turf';
    } else {
      drupal_set_message("This turf overlaps more than one turf for this NL.","warning");
      $to_overlap = 'multiple turfs';
    }
  }
  // Report the name of the voters that are in conflict with an assignment
  // to another turf.
  switch ($to_overlap) {
    case 'multiple NLs':
      // We have an overlap with turfs for different NLs.  Now check if the 
      // problem is due to voters moving to new addresses.
      $to_vtr_array = voterdb_moved_check($form_state);  // func4.
      if(isset($to_vtr_array['moved'])) {
        voterdb_set_moved($to_county,$to_vtr_array['moved']);  // func4.
        voterdb_display_names($form_state,$to_vtr_array['moved'],'moved');  // func4.
      }
      // If we have some voters left who have not moved, we have an error.
      if(isset($to_vtr_array['overlap'])) {
        voterdb_display_names($form_state,$to_vtr_array['overlap'],'overlap'); // func4.
        return 'err';
      }
      return 'OK';
    case 'one turf':
      // We have one turf and seem to be replacing it.
      $to_ol_turf_index = $form_state['voterdb']['overlapped-turf-index'];

      return $to_ol_turf_index;
    case 'multiple turfs':
      
      voterdb_display_names($form_state,$to_duplicates,'overlap'); //func4
      return 'err';
  }
}
  
   /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
   * voterdb_create_drupal_account
   * 
   * @param type $form_state
   */
  function  voterdb_create_drupal_account($userInfo) {
    //voterdb_debug_msg('userinfo', $userInfo, __FILE__, __LINE__);
    $userObj = new NlpDrupalUser();
    //voterdb_debug_msg('userobj', $userObj, __FILE__, __LINE__);
    $query = new EntityFieldQuery();
    //voterdb_debug_msg('query', $query, __FILE__, __LINE__);
    $user = $userObj->getUserByMcid($query,$userInfo['mcid']);
    //voterdb_debug_msg('user', $user, __FILE__, __LINE__);
    if(empty($user)) {
      drupal_set_message('This NL does not have an account to use to get the turf. '
              //. ' An account will be created and an email sent to the NL with instructions.','status');
              . ' An account will be created.  If you use the NLP Service admin function '
              . 'to sen the turf to this NL, that email will include login instructions.','status');

      $account = array(
        'mail' => $userInfo['email'],
        'firstName' => $userInfo['firstName'],
        'lastName' => $userInfo['lastName'],
        'phone' => $userInfo['phone'],
        'county' => $userInfo['county'],
        'mcid' => $userInfo['mcid'],
        'magicWord' => $userInfo['magicWord'],
      );
      $newUser = $userObj->createUser($account);
      switch ($newUser['status']) {
        case 'error':
          drupal_set_message('Something went wrong with creating an account.  '
                  . 'Please contact NLP tech support','error');
          break;
        case 'exists':
          drupal_set_message("The NL's name is already in use.  "
                  . 'Please contact NLP tech support','error');
          break;
        case 'complete':
          drupal_set_message('An account was created for this NL.'
                  . '<br>Username: '.$newUser['userName']
                  . '<br>Password: '.$userInfo['magicWord'],'status');
          
          if(empty($userInfo['email'])) {
            drupal_set_message("The NL doesn't have an email so you will have to help with the login.",'warning'); 
          }
          break;
      }
      return TRUE;

    } else {
      $fieldCheck = array('mcid'=>$userInfo['mcid'],'email'=>$userInfo['email'],'phone'=>$userInfo['phone'],
          'county'=>$userInfo['county'],'firstName'=>$userInfo['firstName'],'lastName'=>$userInfo['lastName']);
      $updateUser = FALSE;
      $nameChanged = $emailChanged = FALSE;
      //voterdb_debug_msg('fields', $fieldCheck, __FILE__, __LINE__);
      //voterdb_debug_msg('user', $user, __FILE__, __LINE__);
      $update['uid'] = $user['uid'];
      foreach ($fieldCheck as $nlpKey => $nlpValue) {
        //voterdb_debug_msg('user: '.$user[$nlpKey].' value: '.$nlpValue, '', __FILE__, __LINE__);
        if($user[$nlpKey] != $nlpValue){
          $updateUser = TRUE;
          //voterdb_debug_msg('nlpkey: '.$nlpKey, '', __FILE__, __LINE__);
          switch ($nlpKey) {
            case 'mcid':
              $update['mcid'] = $nlpValue;
            break;
            case 'email':
              $update['mail'] = $nlpValue;
              $emailChanged = TRUE;
            break;
            case 'phone':
              $update['phone'] = $nlpValue;
            break;
            case 'county':
              $update['county'] = $nlpValue;
              drupal_set_message("The county for this NL was changed.",'warning');
            break;
            case 'firstName':
              $update['firstName'] = $nlpValue;
              $nameChanged = FALSE;
              drupal_set_message("The first name of this NL was changed.",'warning');
            break;
            case 'lastName':
              $update['lastName'] = $nlpValue;
              $nameChanged = FALSE;
              drupal_set_message("The last name of this NL was changed.",'warning');
            break;
          }
        }
      }
      if($nameChanged) {
        drupal_set_message("A name change was made for this NL but the username "
                . "for the login was not changed,  Contact the NLP tech support "
                . "to change the login.",'warning');
      }
      if($emailChanged) {
        if(empty($update['firstName'])) {
          $update['firstName'] = $user['firstName'];
        }
        if(empty($update['lastName'])) {
          $update['lastName'] = $user['lastName'];
        }
      }
      //voterdb_debug_msg('update', $update, __FILE__, __LINE__);
      if($updateUser) {
        $userObj->updateUser($update);
      }
      return FALSE;
    }
  }

