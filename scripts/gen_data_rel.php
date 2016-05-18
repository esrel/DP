<?php
/***
 * Make data file for icsi (csv)
 *
 * -f feature file (pasted rel-level file)
 * -s sense file   (output of proc_relations_sense.php)
 * -l allowed sense list
 * -r character replacement table
 *
 * --rm_partial	remove partial senses
 * --sense		[1|2] take 1st/2nd sense only (if more than 1)
 */
require 'IdMapper.php';
require 'ConllReader.php';
require 'CharNormalizer.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$longopts = array('rm_partial', 'sense:');
$args = getopt('f:s:r:l:', $longopts);

$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$CNR = new CharNormalizer();

// Constants:
$isep = ',';     // separator for senses (input)
$fsep = "\t";    // field separator in senses
$osep = ',';     // output field separator
$inov = 'NULL';  // no value in input
$onov = NULL;    // no value in output
$pref = 'RE_';   // relation ID prefix

// set character normalization, if provided
if (isset($args['r'])) {
	$CNR->readPairs($args['r']);
}
else {
	$CNR->setPairs(array());
}

// read allowed senses list, if provided
$sense_list = (isset($args['l']))
			  ? array_map('trim', file($args['l']))
			  : NULL;

// set long option parameters
$rmp = (isset($args['rm_partial'])) ? TRUE : FALSE;
$snp = (isset($args['sense'])) ? intval($args['sense']) - 1 : FALSE;

// read sense file (labels)
/*
 * wsj_2201					docID
 * 35739					relID
 * 12						sentID_conn
 * 11						sentID_arg1
 * 12						sentID_arg2
 * 228,229					conn_doc_IDs
 * Explicit					type
 * Expansion.Instantiation	sense
 * PS						arg_config
 */
$senses = array();
if (isset($args['s'])) {
	$lines  = array_map('trim', file($args['s']));
	foreach ($lines as $line) {
		if ($line != '') {
			$la    = explode($fsep, $line);
			$docID = $la[0];
			$relID = $la[1];
			$type  = $la[6];

			// Prepare sense labels
			$senses_str = $la[7];
			$senses_arr = explode($isep, $senses_str);

			// Select sense, if set
			if ($snp !== FALSE && count($senses_arr) > 1) {
				$senses_arr = array($senses_arr[$snp]);
			}

			// Get top senses
			$top_senses_arr = get_top_senses($senses_arr);
			$top_senses_str = (!empty($top_senses_arr))
							  ? str_replace('.', '_', implode(' ', $top_senses_arr))
							  : $onov;

			// Remove partial senses, if set
			if ($rmp) {
				$senses_arr = rm_partial_senses($senses_arr, $sense_list);
			}

			$senses_str = (!empty($senses_arr))
						  ? str_replace('.', '_', implode(' ', $senses_arr))
						  : $onov;

			$senses[$docID][$relID] = array($type, $top_senses_str, $senses_str);
		}
	}
}

// read features
$lines = array_map('trim', file($args['f']));
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($fsep, $line);
		$docID = $la[0];
		$relID = $la[1];
		$tmp = array_map(array($CNR, 'normalizeChars'), $la);
		echo implode($osep, $tmp);
		echo $osep;

		if (isset($senses[$docID][$relID])) {
			echo implode($osep, $senses[$docID][$relID]);
		}
		else {
			echo implode($osep, array_fill(0, 3, $onov));
		}
		echo "\n";
	}
}


/**
 * Get unique list of top senses
 */
function get_top_senses($arr) {
	$out = array();
	foreach ($arr as $e) {
		$tmp = explode('.', $e);
		$out[] = $tmp[0];
	}
	return array_unique($out);
}

/**
 * Remove partial senses
 */
function rm_partial_senses($arr, $senses) {
	if ($senses === NULL) {
		return $arr;
	}

	$out = array();
	foreach ($arr as $e) {
		if (in_array($e, $senses)) {
			$out[] = $e;
		}
	}
	return $out;
}
