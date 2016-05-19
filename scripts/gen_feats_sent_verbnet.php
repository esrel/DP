<?php
/***
 * Generate sentence-level features: MPQA
 *
 * -f verbnet feature file: output of gen_feats_verbnet.php
 * -d dependency features : output of gen_feats_sent_dependency.php
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
$args = getopt('f:d:');

// Constants
$sep = "\t";
$nov = 'NULL';

// Classes
$IDM = new IdMapper();
$CFR = new ConllReader();

//----------------------------------------------------------------------
// read data
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
// docID, sentID, tokID
$vn_arr  = $IDM->remap($row_arr, array(0, 1, 2));

// read dependency features
// wsj_2282 docID
// 28       sentID
// 2        tokID root
// 1        tokID subj
// NULL     tokID dobj
// NULL     tokID iobj
$lines = array_map('trim', file($args['d']));
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$docID  = $la[0];
		$sentID = $la[1];
		$tokID  = $la[2]; // root token ID

		echo $docID . $sep . $sentID . $sep;

		if (isset($vn_arr[$docID][$sentID][$tokID])) {
			echo $vn_arr[$docID][$sentID][$tokID][0];
		}
		else {
			echo $nov;
		}
		echo "\n";
	}
}
