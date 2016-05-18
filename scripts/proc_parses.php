<?php
/**
 * Extracts Basic Features from Parses JSON
 *
 * Parameters:
 * -p (pdtb-)parse.json
 * -n output file names
 */
require 'JsonReaderParses.php';
require 'DependencyReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('p:n:');

// Variables
$sep = "\t";	// column separator
$nov = '_';		// no value string

$JRP = new JsonReaderParses();
$DRR = new DependencyReader();

// Output File Name
$fn = (isset($args['n'])) ? $args['n'] : 'tmp';

// Set Output Extensions
$fn_pt      = $fn . '.parse';
$fn_pt_ids  = $fn . '.parse.ids';
$fn_tok     = $fn . '.tok';
$fn_tok_off = $fn . '.tok.offsets';
$fn_dep     = $fn . '.dep';

// Read Json
$par_str = trim(file_get_contents($args['p']));

$JRP->parseJson($par_str);
$doc_arr = $JRP->getDocArr();
$par_arr = $JRP->getParseTrees();
$dep_arr = $JRP->getParseDep();

// write parse trees
$fhp = fopen($fn_pt, 'w');
$fhr = fopen($fn_pt_ids, 'w');
foreach ($par_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		fwrite($fhp, clean_parse_tree($sent) . "\n");
		fwrite($fhr, $docID . $sep . $sentID . "\n");
	}
}
fclose($fhp);
fclose($fhr);

// write basic features
$fhf = fopen($fn_tok, 'w');
$fhi = fopen($fn_tok_off, 'w');
$ids_arr = array();
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		foreach ($sent as $tokID => $tok) {
			$ids = array($docID, $sentID, $tokID);
			$ids_arr[$docID][$sentID][$tokID] = $ids;
			$off_arr = $ids; // offsets
			$tok_arr = $ids; // token + pos

			$off_arr[] = $tok['DocTokID'];
			$off_arr[] = $tok['CharacterOffsetBegin'];
			$off_arr[] = $tok['CharacterOffsetEnd'];

			$tok_arr[] = $tok['TokenString'];
			$tok_arr[] = $tok['PartOfSpeech'];

			fwrite($fhf, implode($sep, $tok_arr) . "\n");
			fwrite($fhi, implode($sep, $off_arr) . "\n");
		}
		fwrite($fhf, "\n");
		fwrite($fhi, "\n");
	}
}
fclose($fhf);
fclose($fhi);

// write dependency
$fhd = fopen($fn_dep, 'w');
foreach ($dep_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$dep = $DRR->readDependency($sent);
		foreach ($doc_arr[$docID][$sentID] as $tokID => $tok) {
			$tmp_arr = $ids_arr[$docID][$sentID][$tokID];
			$tmp_arr[] = $tok['TokenString'];
			if (isset($dep[$tokID])) {
				$tmp_arr[] = $dep[$tokID]['head'];
				$tmp_arr[] = $dep[$tokID]['type'];
			}
			else {
				$tmp_arr[] = $nov;
				$tmp_arr[] = $nov;
			}

			fwrite($fhd, implode($sep, $tmp_arr) . "\n");
		}
		fwrite($fhd, "\n");
	}
}
fclose($fhd);

/**
 * FIX empty parse trees
 */
function clean_parse_tree($str) {
	if (preg_match('/^ *\( *\( *\) *\) *$/u', trim($str))) {
		return '((S (. .)))';
	}
	else {
		return trim($str);
	}
}
