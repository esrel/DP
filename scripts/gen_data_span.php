<?php
/**
 * Generate data in CoNLL format
 *
 * -f file with token-level features
 * -l file with relation-level labels                 [optional]
 * -s file with doc/rel/sent IDs to extract           [optional]
 */
require 'IdMapper.php';
require 'ConllReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:l:s:');

$sep  = "\t";

$IDM = new IdMapper();
$CFR = new ConllReader();

// Read feature file into array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// DCD/ASE: Training/Testing Data
if (isset($args['l'])) {
	$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['l']));
	$rel_arr = $IDM->remap($row_arr, array(0, 1, 2, 3));
	foreach ($rel_arr as $docID => $doc) {
		foreach ($doc as $relID => $rel) {
			foreach ($rel as $sentID => $sent) {
				foreach ($sent as $tokID => $tok) {
					echo implode($sep, array($docID, $relID, $sentID, $tokID));
					echo $sep;
					echo implode($sep, $doc_arr[$docID][$sentID][$tokID]);
					echo $sep;
					echo implode($sep, $tok);
					echo "\n";
				}
				echo "\n";
			}
		}
	}
}
// ASE: Testing Data
elseif (isset($args['s'])) {
	$lines = array_map('trim', file($args['s']));
	foreach ($lines as $line) {
		if ($line != '') {
			list($docID, $relID, $sentID) = explode($sep, $line);
			if (isset($doc_arr[$docID][$sentID])) {
				foreach ($doc_arr[$docID][$sentID] as $tokID => $tok) {
					echo implode($sep, array($docID, $relID, $sentID, $tokID));
					echo $sep;
					echo implode($sep, $tok);
					echo "\n";
				}
				echo "\n";
			}
		}
	}
}
// DCD: Testing Data (unused)
else {
	foreach ($doc_arr as $docID => $doc) {
		foreach ($doc as $sentID => $sent) {
			foreach ($sent as $tokID => $tok) {
				echo implode($sep, array($docID, $sentID, $sentID, $tokID));
				echo $sep;
				echo implode($sep, $tok);
				echo "\n";
			}
			echo "\n";
		}
	}

}
