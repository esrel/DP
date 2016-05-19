<?php
/***
 * Generate VerbNet type for each token
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
 * -l VerbNet file
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
require 'lib/VerbNet.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:l:');

// Constants
$sep  = "\t";
$nov  = 'NULL';
$isep = '|';

// Classes
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$LEX = new VerbNet($args['l']);

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
			$postag = $tok[4];
			$lemma  = $tok[5];

			$vnc    = $LEX->getVerbClass($lemma);

			$tmp[]  = ($vnc && substr($postag, 0, 2) == 'VB')
			          ? implode($isep, $vnc) : $nov;

			echo implode($sep, $tmp) . "\n";
		}
		echo "\n";
	}
}
