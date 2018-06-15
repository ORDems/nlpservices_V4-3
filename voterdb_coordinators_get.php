<?php
/*
 * Name:  voterdb_coordinators_get.php               V3.1 11/3/17
 */
require_once "voterdb_constants_coordinator_tbl.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_coordinator
 * 
 * Find the coordinator closest to the NL.  If there are more than one in 
 * a category chosen, we pop the first one.  It's a bit random but at least
 * one is chosen.
 * 
 * @param type $gc_region - contains the coordinator array and the Pct, HD, and
 *              county of the NL.
 * @return type -  either the coordinator array or an empty array.
 */
function voterdb_get_coordinator($gc_region) {
  $gc_all_cos = $gc_region['coordinators'];
  if(empty($gc_all_cos)) {
    return array();
  }
  $gc_pct = $gc_region['pct'];
  $gc_hd = $gc_region['hd'];
  $gc_county  = $gc_region['county'];
  $gc_co = array();
  // If there is a coordinator assigned to the precinct, use that person.  Else
  // chose the house district coordinator.  If there is no HD coordinator, 
  // then the county coordinator.  There should be at least one of these.  
  // If not, no one will be chosen.
  if(empty($gc_all_cos[$gc_county])) {
    return $gc_co;  // No one in the county is a coordinator.
  }
  $gc_cnty_cos = $gc_all_cos[$gc_county];
  if(isset($gc_cnty_cos[CS_PCT][$gc_pct])) {
    $gc_co = $gc_cnty_cos[CS_PCT][$gc_pct];
  } elseif(isset($gc_cnty_cos[CS_HD][$gc_hd])) {
    $gc_co = $gc_cnty_cos[CS_HD][$gc_hd];
  } elseif (isset($gc_cnty_cos[CS_COUNTY])) {
    $gc_co = $gc_cnty_cos[CS_COUNTY];
  }
  return $gc_co;
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_coordinators_getall
 * 
 * Create an array of all the known coordinators.   Organize so we can 
 * search for the one closest to the the NL.   Most counties will have just
 * one for the county.  Larger counties may have one for one or more of the
 * house districts and some of these may be assigned to just a few precincts.
 * 
 * There can be more than one in each of these categories so we pick the first
 * one we find.
 * 
 * @return array - associative array of coordinators in the county.
 */
function voterdb_coordinators_getall() {
  db_set_active('nlp_voterdb');
  $cl_tselect = "SELECT * FROM {".DB_COORDINATOR_TBL."} WHERE  1 ";
  $cl_result = db_query($cl_tselect);
  db_set_active('default');
  $cl_cos = $cl_result->fetchAll(PDO::FETCH_ASSOC);
  $cl_coordinators = array();
  foreach ($cl_cos as $cl_co) {
    $cl_county = $cl_co[CR_COUNTY];
    $cl_scope = $cl_co[CR_SCOPE];
    $cl_cindex = $cl_co[CR_CINDEX];
    switch ($cl_scope) {
      case CS_PCT:
        db_set_active('nlp_voterdb');
        $cl_tselect = "SELECT * FROM {".DB_PCT_COORDINATOR_TBL."} WHERE  ".
          PC_CINDEX. " = :index";
        $cl_targs = array(
          ':index' => $cl_cindex,);
        $cl_result = db_query($cl_tselect,$cl_targs);
        db_set_active('default');
        $cl_pcts = $cl_result->fetchAll(PDO::FETCH_ASSOC);
        $cl_pct_list = array();
        foreach ($cl_pcts as $cl_pct) {
          $cl_pctn = $cl_pct[PC_PCT];
          if(!isset($cl_coordinators[$cl_county][CS_PCT][$cl_pctn])) {
            $cl_coordinators[$cl_county][CS_PCT][$cl_pctn] = $cl_co;
          }
        } 
        break;
      case CS_HD:
        $cl_hd = $cl_co[CR_HD];
        if(!isset($cl_coordinators[$cl_county][CS_HD][$cl_hd])) {
          $cl_coordinators[$cl_county][CS_HD][$cl_hd] = $cl_co;
        }
        break;
      case CS_COUNTY:
        if(!isset($cl_coordinators[$cl_county][CS_COUNTY])) {
          $cl_coordinators[$cl_county][CS_COUNTY] = $cl_co;
        }
        break;
    }
  }
  return $cl_coordinators;
}