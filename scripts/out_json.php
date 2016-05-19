<?php
/**
 * Converts Output files to json
 *
 * Output Format:
 * {u'Arg1':       {u'TokenList': [443, 444, ...]},
 *  u'Arg2':       {u'TokenList': [422, 423, ...]},
 *  u'Connective': {u'TokenList': [421]},
 *  u'DocID':       u'wsj_1000',
 *  u'Sense':      [u'Comparison.Concession'],
 *  u'Type':        u'Explicit'}
 *
 * Input files:
 *  -a SS.A1.span
 *  -b SS.A2.span
 *  -c PS.A1.span
 *  -d PS.A2.span
 *  -e NE.A1.span
 *  -f NE.A2.span
 *  -g CONN.span
 *  -r rsc.senses
 *  -s csc.senses
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/RmPunct.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('a:b:c:d:e:f:g:r:s:l:');

// Constants
$sep = "\t";

// Variables


$RMP = new RmPunct($args['l']);

// zero array
$ea = array(
		'Arg1'       => array('TokenList' => array()),
		'Arg2'       => array('TokenList' => array()),
		'Connective' => array('TokenList' => array()),
		'DocID'      => '',
		'Sense'      => array(),
		'Type'       => 'Explicit',
		'ID'         => ''
);

// read explicit senses
$re_sense_arr = read_sense_file($args['s']);
// read non-explicit senses
$rn_sense_arr = read_sense_file($args['r'], TRUE);

// read spans
$conn_arr = read_span_file($args['g'], $RMP);
$ss_arr['Arg1'] = read_span_file($args['a'], $RMP);
$ss_arr['Arg2'] = read_span_file($args['b'], $RMP);
$ps_arr['Arg1'] = read_span_file($args['c'], $RMP);
$ps_arr['Arg2'] = read_span_file($args['d'], $RMP);
$ne_arr['Arg1'] = read_span_file($args['e'], $RMP);
$ne_arr['Arg2'] = read_span_file($args['f'], $RMP);

foreach ($conn_arr as $docID => $doc) {
	$ssa1_ids = (isset($ss_arr['Arg1'][$docID])) ? array_keys($ss_arr['Arg1'][$docID]) : array();
	$ssa2_ids = (isset($ss_arr['Arg2'][$docID])) ? array_keys($ss_arr['Arg2'][$docID]) : array();
	$psa1_ids = (isset($ps_arr['Arg1'][$docID])) ? array_keys($ps_arr['Arg1'][$docID]) : array();
	$psa2_ids = (isset($ps_arr['Arg2'][$docID])) ? array_keys($ps_arr['Arg2'][$docID]) : array();
	foreach ($doc as $relID => $rel) {
		$out = $ea;
		$out['ID']    = $relID;
		$out['DocID'] = $docID;
		$out['Connective']['TokenList'] = $rel;
		// Arg1
		if (in_array($relID, $ssa1_ids)) {
			$out['Arg1']['TokenList'] = $ss_arr['Arg1'][$docID][$relID];
		}
		elseif (in_array($relID, $psa1_ids)) {
			$out['Arg1']['TokenList'] = $ps_arr['Arg1'][$docID][$relID];
		}
		else {
			$out['Arg1']['TokenList'] = array();
		}
		// Arg2
		if (in_array($relID, $ssa2_ids)) {
			$out['Arg2']['TokenList'] = $ss_arr['Arg2'][$docID][$relID];
		}
		elseif (in_array($relID, $psa2_ids)) {
			$out['Arg2']['TokenList'] = $ps_arr['Arg2'][$docID][$relID];
		}
		else {
			$out['Arg2']['TokenList'] = array();
		}
		// Sense
		$out['Sense'] = $re_sense_arr[$docID][$relID];

		echo json_encode($out) . "\n";
	}
}

$ne_out = array();
foreach ($ne_arr['Arg1'] as $docID => $doc) {
	foreach ($doc as $relID => $rel) {
		$out = $ea;
		$out['ID']    = $relID;
		$out['DocID'] = $docID;
		$out['Type']  = 'Implicit';
		// Sense
		$out['Sense'] = $rn_sense_arr[$docID][$relID];
		$out['Arg1']['TokenList'] = $rel;
		$ne_out[$docID][$relID] = $out;
	}
}

foreach ($ne_arr['Arg2'] as $docID => $doc) {
	foreach ($doc as $relID => $rel) {
		if (isset($ne_out[$docID][$relID])) {
			$out = $ne_out[$docID][$relID];
		}
		else {
			$out = $ea;
			$out['ID']    = $relID;
			$out['DocID'] = $docID;
			$out['Type']  = 'Implicit';
			// Sense
			$out['Sense'] = $rn_sense_arr[$docID][$relID];
		}

		$out['Arg2']['TokenList'] = $rel;

		echo json_encode($out) . "\n";
	}
}


/* FUNCTIONS */
/**
 * Sense Files
 * wsj_2200	RN_0	1	(2)	EntRel
 *
 * @param  file  $file
 * @return array $out
 */
function read_sense_file($file, $id2 = FALSE) {
	$out = array();
	$lines = array_map('trim', file($file));
	foreach ($lines as $line) {
		if ($line != '') {
			$la = explode("\t", $line);
			$docID = $la[0];
			$relID = $la[1];
			$sense = ($id2) ? $la[4] : $la[3];
			$sense = str_replace(array('_','-'), array('.',' '), $sense);

			$out[$docID][$relID] = array($sense);
		}
	}
	return $out;
}

/**
 * Span Files
 * wsj_2200
 * RE_1
 * 6
 * tok list
 * d-tok list
 * tokens
 */
function read_span_file($file, $RMP) {
	$out = array();
	$lines = array_map('trim', file($file));
	foreach ($lines as $line) {
		if ($line != '') {
			$la = explode("\t", $line);

			if (count($la) == 6) {

				$docID = $la[0];
				$relID = $la[1];

				$dtoks = preg_split('/\s/u', $la[4], -1, PREG_SPLIT_NO_EMPTY);

				$dtarr = array_map('intval', $dtoks);
				$toks  = preg_split('/\s/u', $la[5], -1, PREG_SPLIT_NO_EMPTY);

				if (count($dtarr) != count($toks)) {
					echo count($dtarr) . ':' . count($toks) . "\n";
				}

				$temp  = array_combine($dtarr, $toks);
				$cln   = $RMP->removePunctuation($temp, TRUE);

				$out[$docID][$relID] = array_keys($cln);
			}
		}
	}
	return $out;
}
