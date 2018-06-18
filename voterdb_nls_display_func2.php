<?php
/*
 * Name: voterdb_nls_display_func2.php   V4.2   6/13/18
 *
 */
/*
 * voterdb_build_cell, voterdb_build_column, voterdb_build_row,
 * voterdb_get_progress
 */

use Drupal\voterdb\NlpReports;

define('DD_CSV_FILE','nl_tbl_content');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_cell
 * 
 * Construct the array for a table cell.  Mostly just to make code readable.
 *
 * @param type $bc_value
 * @param type $bc_align : center or left
 * @return type
 */
function voterdb_build_cell($bc_value,$bc_align) {
  if ($bc_align=='center') {
    $bc_cell = array('data'=>$bc_value,
      'class'=>'gl_cell nowhite',
      'style'=>'margin-top:2px; padding-bottom:2px;');
  } else {
    $bc_cell = array('data'=>$bc_value,
      'class'=>'gl_lbl nowhite',
      'style'=>'margin-top:2px; padding-bottom:2px;');
  }
  return $bc_cell;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_row
 *
 * Create the array for a row in a table.   The fist column is the text label
 * and the following columns are the values for the other columns.  Mostly
 * used for code readability.
 * 
 * @param type $br_values_array
 * @return type - array of columns for a row in a table.
 */
function voterdb_build_row($br_values_array,$br_label) {
  $br_row = array();
  $br_row[] = voterdb_build_cell($br_label,'left');
  foreach ($br_values_array as $br_value) {
    $bc_cell = voterdb_build_cell($br_value,'center');
    $br_row[] = $bc_cell;
  }
  return $br_row;
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
function voterdb_get_progress($nlRecord,$reportsObj) {
  $gp_progress['attempts'] = '';  // Voter contact attempts.
  $gp_progress['contacts'] = ''; // Voter contacts.
  $gp_progress['done'] = '';  // Every voter contacted. 
  if($nlRecord['status']['turfDelivered'] != 'Y') {
    return $gp_progress;
  }
  // Get the voters assigned to this NL for all the turfs.
  $gp_mcid = $nlRecord['mcid'];
  db_set_active('nlp_voterdb');
  try {
    $gp_vquery = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $gp_vquery->addField('g',NV_VANID);
    $gp_vquery->condition(NV_MCID,$gp_mcid);
    $gp_vquery->condition(NV_VOTERSTATUS,'A');
    $gp_tvoters = $gp_vquery->execute()->fetchAll(PDO::FETCH_ASSOC);
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
    return $gp_progress;
  }
  db_set_active('default');
  if(empty($gp_tvoters)) {return $gp_progress;}
  $gp_voter_cnt = count($gp_tvoters);
  // Initialize array.
  $gp_voter['attempt'] = 0;
  $gp_voter['contact'] = 0;
  foreach ($gp_tvoters as $gp_tvoter) {
    $gp_voters[$gp_tvoter[NV_VANID]] = $gp_tvoter;
  }
  // Now get all the reports from this NL for this cycle.
  $gp_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  $gp_reports = $reportsObj->getNlpVoterReports($gp_mcid,$gp_cycle);

  // Flag the voters with whom the NL had a face-to-face contact.  (There 
  // could have been more than one.)
  foreach ($gp_reports as $gp_report) {
    $gp_vanid = $gp_report['vanid'];
    if(!empty($gp_voters[$gp_vanid])) {
      $gp_voters[$gp_vanid]['attempt'] = TRUE;
      if($gp_report['type']==$reportsObj::CONTACT AND $gp_report['value']==$reportsObj::F2F) {
        $gp_voters[$gp_vanid]['contact'] = TRUE;
      }
    }
  }
  //  Now count the voters with a f2f contact.
  $gp_ccnt = $gp_acnt = 0;
  if(!empty($gp_voters)) {
    foreach ($gp_voters as $gp_cvoter) {
      if(!empty($gp_cvoter['attempt'])) {
        $gp_acnt++;
        if(!empty($gp_cvoter['contact'])) {
          $gp_ccnt++;
        }
      }
    }
  }
  // Return the strings for display of voter contact attempts and actual 
  // f2f contacts.
  if ($gp_voter_cnt == 0) {
    $gp_progress['attempts'] = $gp_progress['contacts'] = $gp_progress['done'] = '';
    return $gp_progress;
  }
  $gp_progress['attempts'] = $gp_acnt.'/'.$gp_voter_cnt;  // Voter contact attempts.
  $gp_progress['contacts'] = $gp_ccnt.'/'.$gp_voter_cnt; // Voter contacts.
  if($gp_ccnt == $gp_voter_cnt AND $gp_voter_cnt != 0) {
    $gp_progress['done'] = 'Done';  // Every voter was contacted.
  }
  return $gp_progress;
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_create_csv
 * 
 * 
 * @param type $cc_county
 * @param type $cc_hd
 * @param type $nlRecords
 * @return type
 */
function voterdb_create_csv($cc_county,$cc_hd,$nlRecords) {

   
  $cc_hdr = array(
      'mcid'=>'MCID',
      'hd'=>'HD',
      'pct'=>'Pct',
      'lastName'=>'LastName',
      'nickname'=>'NickName',
      'address'=>'Address',
      'email'=>'Email',
      'phone'=>'Phone',
      'asked'=>'NL',
      'turfCut'=>'TC',
      'turfDelivered'=>'TD',
      'contact'=>'CO',
      'loginDate'=>'LI',
      'atmps'=>'Atmps',
      'conts'=>'Conts',
  );
  
  $cc_temp_dir = 'public://temp';
  $cc_cdate = date('Y-m-d-H-i-s',time());
  $cc_csv_uri = $cc_temp_dir.'/'.DD_CSV_FILE.'-'.$cc_county.'-'.$cc_cdate.'.csv';
  $cc_csv_object = file_save_data('', $cc_csv_uri, FILE_EXISTS_REPLACE);
  $cc_csv_object->status = 0;
  file_save($cc_csv_object);
  $cc_csv_fh = fopen($cc_csv_uri,"w");
  
  
  $cc_hdr_record = implode(',', $cc_hdr).",Voters\n";
  fwrite($cc_csv_fh,$cc_hdr_record);
  //voterdb_debug_msg('hdr', $cc_hdr, __FILE__, __LINE__);
  $cc_keys = array_keys($cc_hdr);
  foreach ($nlRecords as $nlRecord) {
    foreach ($cc_keys as $cc_key) {
      
      switch ($cc_key) {
        case 'atmps':
        case 'conts':
          $voterCnt = '';
          if(!empty($nlRecord['results'])) {
            $cnt = explode('/', $nlRecord['results'][$cc_key]);
            $cc_ordered_fields[$cc_key] = $cnt[0];
            if(!empty($cnt[1])) {
              $voterCnt = $cnt[1];
            }
          } else {
            $cc_ordered_fields[$cc_key] = '';
          }
          break;
        case 'asked':
        case 'turfCut':
        case 'turfDelivered':
        case 'loginDate':
          if(!empty($nlRecord['status'])) {
            $cc_ordered_fields[$cc_key] = $nlRecord['status'][$cc_key];
          } else {
            $cc_ordered_fields[$cc_key] = '';
          }
          break;
        case 'contact':
          if(!empty($nlRecord['status'])) {
            $newField = str_replace(',', ' ', $nlRecord['status'][$cc_key]);
            $cc_ordered_fields[$cc_key] = $newField;
          } else {
            $cc_ordered_fields[$cc_key] = '';
          }
          break;
        default:
          $newField = str_replace(',', ' ', $nlRecord[$cc_key]);
          $cc_ordered_fields[$cc_key] = $newField;
          break;
      }
    }
       
    $cc_ordered_fields[$cc_key+1] = $voterCnt;
    $cc_string = '"'.implode('","',$cc_ordered_fields).'"'."\n";
    $cc_record = str_replace("&#039;","'", $cc_string);
    fwrite($cc_csv_fh,$cc_record);
  }
  fclose($cc_csv_fh);
  $cc_csv_url = file_create_url($cc_csv_uri);
  
  $cc_info = "House District Exported: ".$cc_hd;
  voterdb_login_tracking('export',$cc_county,'NL table was exported. ',$cc_info);
  
  return $cc_csv_url;
  
}
