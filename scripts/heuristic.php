<?php
/**
 * Heuristics for Inter-sentential Discourse Relation Argument Spans:
 *
 * PS Arg1 and Arg2
 *
 * -f file with connective labels (post-processed DCD)
 * -s PS.A1 ids
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
$rel_arr = $IDM->remap($row_arr, array(0, 2, 3));


$lines = array_map('trim', file($args['s']));
foreach ($lines as $line) {
	if ($line != '') {
		list($docID, $relID, $sentID) = explode($sep, $line);
		if (isset($rel_arr[$docID][$sentID])) {
			$sent = $rel_arr[$docID][$sentID];

			$tmp = array($docID, $relID, $sentID);
			$tmp[] = implode(' ', array_column($sent, 3));
			$tmp[] = implode(' ', array_column($sent, 4));
			$tmp[] = implode(' ', array_column($sent, 7));

			echo implode($sep, $tmp) . "\n";
		}
	}
}
