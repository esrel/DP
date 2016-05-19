<?php
/***
 * Generate sentence-level features: MPQA
 *
 * -f mpqa feature file: output of gen_feats_mpqa.php
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
$args = getopt('f:');

// Constants
$sep = "\t";

// Classes
$IDM = new IdMapper();
$CFR = new ConllReader();

//----------------------------------------------------------------------
// read data
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
// docID, sentID, tokID
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		// get polarity column
		$pol_arr = array_column($sent, 1);
		$val_arr = array_count_values($pol_arr);
		$pc = (isset($val_arr['positive'])) ? $val_arr['positive'] : 0;
		$nc = (isset($val_arr['negative'])) ? $val_arr['negative'] : 0;

		$pol = $pc - $nc;
		$str = ($pol > 0)
			 ? 'positive'
			 : (($pol < 0) ? 'negative' : 'neutral');

		echo $docID . $sep . $sentID . $sep;
		echo $pol   . $sep . $str . "\n";
	}
}
