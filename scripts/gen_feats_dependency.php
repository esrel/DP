<?php
/***
 * Generate Dependency Features
 *
 * 1. Root Verb (boolean)
 * 2. Dependency Chain
 *
 * -f dependency parse in token-per-line format
 * (output of DependencyReader)
 */
require 'DependencyFeatures.php';
require 'IdMapper.php';
require 'ConllReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:');

// constants
$sep = "\t";

$DEP = new DependencyFeatures();
$IDM = new IdMapper();
$CFR = new ConllReader();

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
