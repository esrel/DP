<?php
/**
 * Generate labels in CoNLL format
 *
 * -f file with connective labels (post-processed DCD)
 * -s file with doc/rel/sent IDs to extract           [optional]
 */
require 'IdMapper.php';
require 'ConllReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:s:');

$sep  = "\t";

$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();

// Read feature file into array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$rel_arr = $IDM->remap($row_arr, array(0, 1, 2, 3));


$lines = array_map('trim', file($args['s']));
foreach ($lines as $line) {
	if ($line != '') {
		list($docID, $relID, $sentID) = explode($sep, $line);
		foreach ($rel_arr[$docID][$relID][$sentID] as $tokID => $tok) {
			echo implode($sep, $tok) . "\n";
		}
		echo "\n";
	}
}
