<?php
/***
 * Generate sentence-level dependency features:
 *
 * Input:
 *  -t token-file (CoNLL) : output of proc_parses.php
 *  -d dependency parses  : output of proc_parses.php
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/IdMapper.php';
require 'lib/ConllReader.php';
require 'lib/DependencyFeatures.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('t:d:');

// Constants
$sep = "\t";

// Classes
$IDM = new IdMapper();
$CFR = new ConllReader();
$DEP = new DependencyFeatures();

//----------------------------------------------------------------------
// Read Document into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['t']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// Read dependencies into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['d']));
$dep_arr = $IDM->remap($row_arr, array(0, 1, 2));

foreach ($dep_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$tmp = array($docID, $sentID);
		$tmp[] = $DEP->getMainVerb($sent, TRUE);
		$tmp[] = $DEP->getSubject($sent, TRUE);
		$tmp[] = $DEP->getDObject($sent, TRUE);
		$tmp[] = $DEP->getIObject($sent, TRUE);
		echo implode($sep, $tmp) . "\n";
	}
}
