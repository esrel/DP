<?php
/***
 * Generate Dependency Features
 *
 * 1. Root Verb (boolean)
 * 2. Dependency Chain
 *
 * -f dependency parse in token-per-line format
 * (output of DependencyReader)
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/DependencyFeatures.php';
require 'lib/IdMapper.php';
require 'lib/ConllReader.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:');

// Constants
$sep = "\t";

// Classes
$DEP = new DependencyFeatures();
$IDM = new IdMapper();
$CFR = new ConllReader();

//----------------------------------------------------------------------
// Read Document/Token IDs into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// Generate Dependency Features
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$bmv_tagged = $DEP->tagRoot($sent);
		$dep_chains = $DEP->mkDependencyChains($sent);

		foreach ($dep_chains as $tokID => $chain) {
			echo $docID . $sep . $sentID . $sep . $tokID . $sep;
			echo $bmv_tagged[$tokID] . $sep;
			echo $chain . "\n";
		}
		echo "\n";
	}
}
