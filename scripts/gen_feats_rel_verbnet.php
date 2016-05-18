<?php
/***
 * Generate Match Features for sentence pairs
 *
 * -p sentence pairs   : as docID, relID, arg1_sentID, arg2_sentID
 * -l tagged sentences : verbnet features
 */
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('p:l:');

// Constants
$isep = '|';
$sep = "\t";
$nov  = 'NULL';
$glue = '#';

// read sentence pairs
$lines = array_map('trim', file($args['p']));
$pairs = array();
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$pairs[$la[0]][$la[1]] = array_slice($la, 2);
	}
}

// read verbnet features
$lines  = array_map('trim', file($args['l']));
$vn_arr = array();
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$vn_arr[$la[0]][$la[1]] = explode($isep, $la[2]);
	}
}

// Generate features
foreach ($pairs as $docID => $doc) {
	foreach ($doc as $relID => $rel) {
		$a1ID = $rel[0];
		$a2ID = $rel[1];

		echo $docID . $sep . $relID . $sep;
		echo $a1ID  . $sep . $a2ID  . $sep;
		// verbnet classes
		$a1vn = $vn_arr[$docID][$a1ID][0];
		$a2vn = $vn_arr[$docID][$a2ID][0];
		// product & match
		if ($a1vn == $nov && $a2vn == $nov) {
			$cpvn = $nov;
			$mpvn = 0;
		}
		else {
			$cpvn = $a1vn . $glue . $a2vn;
			$int  = array_intersect($vn_arr[$docID][$a1ID], $vn_arr[$docID][$a2ID]);
			$mpvn = (!empty($int)) ? 1 : 0;
		}

		echo $a1vn . $sep;
		echo $a2vn . $sep;
		echo $cpvn . $sep;
		echo $mpvn . "\n";
	}
}
