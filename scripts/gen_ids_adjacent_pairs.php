<?php
/**
 * Generate Adjacent Pairs for Non-Explicit Relations
 *    as pairs of sentenceIDs w.r.t. paragraph boundaries
 *
 * -p paragraph   offset file
 * -t token-level offset file
 */
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('p:t:');

// Variables
$sep = "\t";

// Read token IDs
$ids_tmp = read_col_file($args['t']);
$par_tmp = read_col_file($args['p']);

// Extract sentence boundaries as character offsets
$doc_arr = array();
foreach ($ids_tmp as $a) {
	$doc_arr[$a[0]][$a[1]][$a[2]] = array('b' => $a[4], 'e' => $a[5]);
}
$sent_arr = array();
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$sent_arr[$docID][$sentID]['b'] = $sent[0]['b'];
		$sent_arr[$docID][$sentID]['e'] = $sent[count($sent) - 1]['e'];
	}
}

// Paragraph
$par_arr = array();
foreach ($par_tmp as $a) {
	$par_arr[$a[0]][$a[1]] = array('b' => $a[2], 'e' => $a[3]);
}

// Generate Pairs
$pairs = array();
foreach ($par_arr as $docID => $doc) {
	foreach ($doc as $parID => $par) {
		// get sentences within boundaries
		if (isset($sent_arr[$docID])) {
			$par_sent = array();
			foreach ($sent_arr[$docID] as $sentID => $sent) {
				if ($sent['b'] >= $par['b'] && $sent['e'] <= $par['e']) {
					$par_sent[] = $sentID;
				}
			}
			// generate pairs
			foreach ($par_sent as $sentID) {
				if (in_array($sentID + 1, $par_sent)) {
					$pairs[$docID][] = array($sentID, $sentID + 1);
				}
			}
		}
	}
}
// print pairs
foreach ($pairs as $docID => $doc) {
	foreach ($doc as $a) {
		echo $docID . $sep;
		echo implode($sep, $a) . "\n";
	}
}

/**
 * Read IDs file
 */
function read_col_file($file, $sep = "\t") {
	$lines = array_map('trim', file($file));
	$arr   = array();
	foreach ($lines as $line) {
		if ($line != '') {
			$la = explode($sep, $line);
			$arr[] = $la;
		}
	}
	return $arr;
}
