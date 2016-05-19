<?php
/**
 * Align IOB chain array & pdtb-parses.json data
 *
 * -t token offset file
 * -f chunklink output
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

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:t:');

// Constants
$NONE = 'NULL'; // missing values
$sep  = "\t";	// column separator
$nov  = '_';	// no value string

$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();

// read in clink_arr
$clink_arr      = array();
$clink_file_arr = file($args['f']);

// Sentence IDs start from 1
foreach ($clink_file_arr as $line) {
	$line = trim($line);
	// if not empty & not comment
	if ($line != '' && !preg_match('/^#/u', $line)) {
		$la = preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY);
		$sentID    = $la[1]; // collection-level & starts from 1
		$tokID     = $la[2]; // sentence-level & starts from 0
		$iob_tag   = $la[3];
		$iob_chain = $la[9];
		$clink_arr[$sentID][$tokID] = array($iob_tag, $iob_chain);
	}
}

// Read Document/Token IDs into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['t']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

$cID = 1;
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		if (count($sent) == count($clink_arr[$cID])) {
			foreach ($sent as $tokID => $tok) {
				$tmp_arr = array($docID, $sentID, $tokID);
				echo implode($sep, $tmp_arr) . $sep;
				echo implode($sep, $clink_arr[$cID][$tokID]) . "\n";
			}
		}
		else {
			// empty parse?
			foreach ($sent as $tokID => $tok) {
				$tmp_arr = array($docID, $sentID, $tokID);
				echo implode($sep, $tmp_arr) . $sep;
				echo implode($sep, array($NONE, $NONE)) . "\n";
			}
		}
		$cID++;
		echo "\n";
	}
}
