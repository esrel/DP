<?php
/***
 * Generate Match & Cartesian Product Features
 *
 * -p sentence pairs   : as docID, relID, arg1_sentID, arg2_sentID
 * -d dependency roles : output of gen_feats_sent_dependency.php
 * -l tagged tokens    : brown clusters/lemmas/etc.
 * -t feature ID to take
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/IdMapper.php';
require 'lib/ConllReader.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('p:d:l:t:');

// Constants
$sep  = "\t";
$nov  = 'NULL';
$glue = '#';

// Classes
$IDM = new IdMapper();
$CFR = new ConllReader();

// Definitions
// feature matrix: {root,subj,dobj,iobj}
$mat = array(
		array(0,0),
		array(1,1), array(2,2), array(3,3),
		array(1,2), array(1,3),
		array(2,1), array(2,3),
		array(3,1), array(3,2)
	   );

//----------------------------------------------------------------------
// read feature column (first, if not set)
$id = (isset($args['t'])) ? intval($args['t']) : 0;

// read tagged data
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['l']));
// docID, sentID, tokID
$val_arr  = $IDM->remap($row_arr, array(0, 1, 2));

// read sentence pairs
$lines = array_map('trim', file($args['p']));
$pairs = array();
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$pairs[$la[0]][$la[1]] = array_slice($la, 2);
	}
}

// read dependency roles
$lines = array_map('trim', file($args['d']));
$roles = array();
foreach ($lines as $line) {
	if ($line != '') {
		$la = explode($sep, $line);
		$roles[$la[0]][$la[1]] = array_slice($la, 2);
	}
}

// Generate features
foreach ($pairs as $docID => $doc) {
	foreach ($doc as $relID => $rel) {
		$a1ID = $rel[0];
		$a2ID = $rel[1];
		// get role values
		$a1 = get_values(array_column($val_arr[$docID][$a1ID], $id),
						 $roles[$docID][$a1ID], $nov);
		$a2 = get_values(array_column($val_arr[$docID][$a2ID], $id),
						 $roles[$docID][$a2ID], $nov);
		// generate cartesian product
		$cp = gen_cproduct($a1, $a2, $mat, $nov, $glue);
		// generate match features
		$mp = gen_matches($a1, $a2, $mat, $nov);

		echo $docID . $sep . $relID . $sep;
		echo $a1ID  . $sep . $a2ID  . $sep;
		echo implode($sep, $a1) . $sep;
		echo implode($sep, $a2) . $sep;
		echo implode($sep, $cp) . $sep;
		echo implode($sep, $mp) . "\n";
	}
}

//----------------------------------------------------------------------
// Functions
//----------------------------------------------------------------------
/**
 * get values for roles
 *
 * @param array  $sent
 * @param array  $ids
 * @param string $nov
 *
 * @return array $out
 */
function get_values($sent, $ids, $nov) {

	$out = array();
	foreach ($ids as $id) {
		$out[] = (isset($sent[$id])) ? $sent[$id] : $nov;
	}
	return $out;
}

/**
 * generate cartesian product (NULL is used)
 *
 * @param array  $arg1
 * @param array  $arg2
 * @param array  $mat
 * @param string $nov
 * @param string $glue
 *
 * @return array $out
 */
function gen_cproduct($arg1, $arg2, $mat, $nov, $glue) {
	$out = array();
	foreach ($mat as $p) {
		if ($arg1[$p[0]] == $nov && $arg2[$p[1]] == $nov) {
			$out[] = $nov;
		}
		else {
			$out[] = $arg1[$p[0]] . $glue . $arg2[$p[1]];
		}
	}
	return $out;
}

/**
 * generate match matrix
 *
 * @param array  $arg1
 * @param array  $arg2
 * @param array  $mat
 * @param string $nov
 *
 * @return array $out
 */
function gen_matches($arg1, $arg2, $mat, $nov) {
	$out = array();
	foreach ($mat as $p) {
		if ($arg1[$p[0]] == $nov && $arg2[$p[1]] == $nov) {
			$out[] = 0;
		}
		else {
			$out[] = ($arg1[$p[0]] == $arg2[$p[1]]) ? 1 : 0;
		}
	}
	return $out;
}

