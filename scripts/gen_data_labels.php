<?php
/**
 * Generate labels in CoNLL format
 *
 * -f file with connective labels (post-processed DCD)
 * -s file with doc/rel/sent IDs to extract           [optional]
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

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:s:');

// Constants
$sep  = "\t";

// Classes
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();

//----------------------------------------------------------------------
// Read feature file into array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$rel_arr = $IDM->remap($row_arr, array(0, 1, 2, 3));

// read ids file
$lines = array_map('trim', file($args['s']));
foreach ($lines as $line) {
	if ($line != '') {
		list($docID, $relID, $sentID) = explode($sep, $line);
		foreach ($rel_arr[$docID][$relID][$sentID] as $tokID => $tok) {
			echo implode($sep, $tok) . "\n";
		}
		echo "\n";
	}
}
