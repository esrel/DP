<?php
/***
 * Generate Connective Head Features
 *
 * -f token file
 * -l file with the list of connectives
 */
require 'LexiconTagger.php';
require 'IdMapper.php';
require 'ConllReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:l:');

// constants
$sep = "\t";

$LEX = new LexiconTagger($args['l']);
$IDM = new IdMapper();
$CFR = new ConllReader();

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
