<?php
/***
 * Generate Connective Head Features
 *
 * -f token file
 * -l file with the list of connectives
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/LexiconTagger.php';
require 'lib/IdMapper.php';
require 'lib/ConllReader.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:l:');

// Constants
$sep = "\t";

// Classes
$LEX = new LexiconTagger($args['l']);
$IDM = new IdMapper();
$CFR = new ConllReader();

//----------------------------------------------------------------------
// Read Document/Token IDs into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// Generate Connective Head Features (boolean)
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$tagged = $LEX->tag(array_column($sent, 0));
		$string = $LEX->tag(array_column($sent, 0), FALSE);
		foreach ($tagged as $tokID => $tok) {
				echo $docID . $sep . $sentID . $sep . $tokID . $sep;
				echo implode('|', $string[$tokID]) . $sep;
				echo $tok . "\n";
			}
			echo "\n";
	}
}
