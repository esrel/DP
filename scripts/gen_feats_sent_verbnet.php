<?php
/***
 * Generate sentence-level features: MPQA
 *
 * -f verbnet feature file: output of gen_feats_verbnet.php
 * -d dependency features : output of gen_feats_sent_dependency.php
 */
require 'IdMapper.php';
require 'ConllReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:d:');

// Constants
$osep = "\t";
$nov  = 'NULL';

$IDM = new IdMapper();
$CFR = new ConllReader();

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
		$la = explode("\t", $line);
		$docID  = $la[0];
		$sentID = $la[1];
		$tokID  = $la[2]; // root token ID

		echo $docID . $osep . $sentID . $osep;

		if (isset($vn_arr[$docID][$sentID][$tokID])) {
			echo $vn_arr[$docID][$sentID][$tokID][0];
		}
		else {
			echo $nov;
		}
		echo "\n";
	}
}
