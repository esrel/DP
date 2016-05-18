<?php
/***
 * Make data file as CSV
 *
 * -t token-level feature file (no relation IDs column)
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
$args = getopt('s:t:r:l:', $longopts);

// Constants:
$isep = ',';  // separator for senses (input)
$fsep = "\t"; // field separator in senses
$osep = ',';  // output field separator
$inov = '_';  // no value in input
$onov = NULL; // no value in output
$pref = 'RE_';// relation ID prefix

// Argument Parameters
// set long option parameters
$rmp = (isset($args['rm_partial'])) ? TRUE : FALSE;
$snp = (isset($args['sense'])) ? intval($args['sense']) - 1 : FALSE;

// Class Initializations
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$CNR = new CharNormalizer();

// set character normalization, if provided
if (isset($args['r'])) {
	$CNR->readPairs($args['r']);
}
else {
	$CNR->setPairs(array());
}

/*--------------------------------------------------------------------*/
// read allowed senses list, if provided
$sense_list = (isset($args['l']))
			  ? array_map('trim', file($args['l']))
			  : NULL;

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
if (isset($args['s']) && isset($args['t'])) {
	// Read features
	$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['t']));
	// docID, sentID, doc-level-tokID
	$doc_arr = $IDM->remap($row_arr, array(0, 1, 3));

	$lines = array_map('trim', file($args['s']));
	foreach ($lines as $line) {
		if ($line != '') {
			$la   = explode($fsep, $line);
			$type = $la[6];

			if ($type == 'Explicit') {
				$docID    = $la[0];
				$relID    = $la[1];
				$sentID   = $la[2];
				$dtID_arr = explode($isep, $la[5]);
				$config   = $la[8];

				$tmp_arr  = array($docID, $relID, $sentID);

				// get token IDs
				$tmp_ids = array_values(
						array_intersect_key($doc_arr[$docID][$sentID],
											array_flip($dtID_arr)));
				// remove doc & sent IDs
				// add other features
				$keys    = array_slice(array_keys($tmp_ids[0]), 2);
				foreach ($keys as $key) {
					$tmp_arr[] = $CNR->normalizeChars(
							implode(' ', array_column($tmp_ids, $key)));
				}

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
								  ? implode(' ', $top_senses_arr)
								  : $onov;

				// Remove partial senses, if set
				if ($rmp) {
					$senses_arr = rm_partial_senses($senses_arr, $sense_list);
				}

				$senses_str = (!empty($senses_arr))
							  ? implode(' ', $senses_arr)
							  : $onov;

				// ADD labels
				$tmp_arr[] = $type;
				$tmp_arr[] = str_replace('.', '_', $top_senses_str);
				$tmp_arr[] = str_replace('.', '_', $senses_str);
				$tmp_arr[] = $config;

				echo implode($osep, $tmp_arr). "\n";
			}
		}
	}
}

/**
 * Get unique list of top senses
 * @param  array $arr
 * @return array $out
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
 * @param  array $arr
 * @param  array $senses
 * @return array $out
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
