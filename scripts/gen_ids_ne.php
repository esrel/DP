<?php
/***
 * Pipeline: Generate Non-Explicit Pairs using:
 * 	- Argument Position Classification output
 *  - Paragraph sentence pairs
 *
 * (removes all PS sentence pairs from sent.pairs)
 *
 * -f PS ID pairs (joined output of gen_ids_ips & PS.A2)
 * -p sent.pairs  (from gen_ids_adjacent_pairs.php)
 */
// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:p:');

// Constants
$sep  = "\t";
$pref = 'RN_';

// Argument Parameters
// Class Initializations
// Variables
/*--------------------------------------------------------------------*/

// read adjacent sentence pairs
// wsj_2278  docID
// 23        Arg1 sentID
// 24        Arg2 sentID
$lines = array_map('trim', file($args['p']));
$pairs = array();
foreach ($lines as $line) {
	if ($line != '') {
		list($docID, $A1ID, $A2ID) = explode($sep, $line);
		$pairs[$docID][] = array($A1ID, $A2ID);
	}
}

// read PS sentence pairs
// wsj_2200  docID
// 35709     relID
// 2         Arg1 sentID
// 3         Arg2 sentID
$lines    = array_map('trim', file($args['f']));
$ps_pairs = array();
foreach ($lines as $line) {
	if ($line != '') {
		list($docID, $relID, $A1ID, $A2ID) = explode($sep, $line);
		$ps_pairs[$docID][] = array($A1ID, $A2ID);
	}
}

// Generate Non-Explicit sentence pairs
$RID = 0;
foreach ($pairs as $docID => $doc) {
	foreach ($doc as $pair) {
		$doc = (isset($ps_pairs[$docID])) ? $ps_pairs[$docID] : array();
		if (!in_array($pair, $doc)) {
			$relID = $pref . $RID;
			$ne_pair = array_merge(array($docID, $relID), $pair);
			echo implode($sep, $ne_pair) . "\n";
			$RID++;
		}
	}
}
