<?php
/***
 * Generate Match Features for sentence pairs
 *
 * -p sentence pairs   : as docID, relID, arg1_sentID, arg2_sentID
 * -l tagged sentences : mpqa sentence level features
 * -t feature ID
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('p:l:t:');

// Constants
$sep  = "\t";
$glue = '#';

//----------------------------------------------------------------------
// set feature ID
$id = (isset($args['t'])) ? intval($args['t']) : 0;

// read sentence pairs
$lines = array_map('trim', file($args['p']));
$pairs = array();
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$pairs[$la[0]][$la[1]] = array_slice($la, 2);
	}
}

// read features
$lines   = array_map('trim', file($args['l']));
$pol_arr = array();
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$pol_arr[$la[0]][$la[1]] = $la[$id];
	}
}

// Generate features
foreach ($pairs as $docID => $doc) {
	foreach ($doc as $relID => $rel) {
		$a1ID = $rel[0];
		$a2ID = $rel[1];

		echo $docID . $sep . $relID . $sep;
		echo $a1ID  . $sep . $a2ID  . $sep;
		// verbnet classes
		$a1p = $pol_arr[$docID][$a1ID];
		$a2p = $pol_arr[$docID][$a2ID];
		$cpp = $a1p . $glue . $a2p;
		$mpp = ($a1p == $a2p) ? 1 : 0;

		echo $a1p . $sep;
		echo $a2p . $sep;
		echo $cpp . $sep;
		echo $mpp . "\n";
	}
}
