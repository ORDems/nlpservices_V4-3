<?php
/*
 * Name: voterdb_nls_validate.php   V4.0 12/14/17
 */
require_once "voterdb_constants_nls_tbl.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nls_validate
 *
 * Validate that we know the NL.
 *
 * @param type $vn_fname  // First name of the NL
 * @param type $vn_lname  // Last name of the NL
 * @param type $vn_county  // Either a County name or a Campaign name
 * @return boolean or string
 *       False if the NL is not in the database
 *       MCID as a string if the NL is known
 */
function voterdb_nls_validate($vn_fname, $vn_lname, $vn_county) {
  // Replace the apostrophe with the HTML code for MySQL.
  // This lets us have names like O'Brian in the database.
  $vn_NLlname = str_replace("'", "&#039;", trim ( $vn_lname , " \t\n\r\0\x0B" ));
  $vn_NLfname = str_replace("'", "&#039;", trim ( $vn_fname , " \t\n\r\0\x0B" ));
  db_set_active('nlp_voterdb');
  try {
  $vn_query = db_select(DB_NLS_TBL, 'n');
  $vn_query->join(DB_NLS_GRP_TBL, 'g', 'g.'.NG_MCID.' = n.'.NH_MCID );
  $vn_query->addField('n', NH_MCID);
  $vn_query->addField('n', NH_HD);
  $vn_query->addField('n', NH_PCT);
  $vn_query->condition(NH_NICKNAME,$vn_NLfname);
  $vn_query->condition(NH_LNAME,$vn_NLlname);
  $vn_query->condition('g.'.NG_COUNTY,$vn_county);
  $vn_result = $vn_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  $vn_nl = $vn_result->fetchAssoc();
  if(empty($vn_nl)) {return FALSE;}  // NL not known.
  return $vn_nl;  //return the MCID and HD.
}