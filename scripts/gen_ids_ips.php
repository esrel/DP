<?php
/***
 * Immediately Previous Sentence Heuristic
 * Generate Sentence IDs for ASE:PS.A1
 *
 * -f ids from PS.A2
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
$args = getopt('f:');

// Constants
$sep = "\t";
$nov = 'NULL';

//----------------------------------------------------------------------
$lines = array_map('trim', file($args['f']));
foreach ($lines as $line) {
	if ($line != '') {
		list($docID, $relID, $sentID) = explode($sep, $line);
		$IPS = intval($sentID) - 1;
		if ($IPS >= 0) {
			echo implode($sep, array($docID, $relID, $IPS)) . "\n";
		}
		else {
			// Error in classification?
			echo implode($sep, array($docID, $relID, $nov)) . "\n";
		}
	}
}
