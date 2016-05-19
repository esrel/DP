<?php
/***
 * Generate Brown Clusters for each token
 *
 * Input (lemmas)
 *  docID
 *  sentID
 *  tokID
 *  token
 *  POS-tag
 *  lemma
 *
 * -f token-per-line file (CoNLL) : lemma
 * -l Brown Clusters file
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
require 'lib/BrownClusters.php';

// Error Reporting
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:l:');

// Constants
$nov = 'NULL';
$sep = "\t";

// Classes
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$LEX = new BrownClusters($args['l'], 20);

//----------------------------------------------------------------------
// Data
// Read Document/Token IDs into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// Tag & Print
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		foreach ($sent as $tokID => $tok) {
			$tmp    = array_slice($tok, 0, 3); // get IDs
			$ltoken = strtolower($tok[3]);

			$bcc    = $LEX->getBrownCluster($ltoken);

			$tmp[]  = ($bcc) ? $bcc : $nov;

			echo implode($sep, $tmp) . "\n";
		}
		echo "\n";
	}
}
