<?php
/***
 * Generate MPQA features for each token
 *
 * Input (lemmas)
 *  docID
 *  sentID
 *  tokID
 *  token
 *  POS-tag
 *  lemma
 *
 * -f token-per-line file (CoNLL) : lemma
 * -l MPQA file
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/MpqaSubjLex.php';
require 'lib/IdMapper.php';
require 'lib/ConllReader.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('f:l:');

// Constants
$nov = 'NULL';
$sep = "\t";

// Classes
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$LEX = new MpqaSubjLex($args['l']);

//----------------------------------------------------------------------
// Data
// Read Document/Token IDs into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['f']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// Tag & print
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$flag = FALSE; // reset flag per sentence
		foreach ($sent as $tokID => $tok) {
			$tmp    = array_slice($tok, 0, 3); // get IDs
			$ltoken = strtolower($tok[3]);
			$lemma  = $tok[5];
			$neg    = $LEX->isNegation($ltoken);

			// set polarity changing to TRUE, if token is a negation
			if ($neg) {
				$flag = TRUE;
			}
			// remove flag is token is punctuation
			if (preg_match('/^[[:punct:]]+$/u', $ltoken)) {
				$flag = FALSE;
			}

			$sub = $LEX->getSubjectivity($lemma);

			if ($flag) {
				$pol = $LEX->changePolarity($lemma);
			}
			else {
				$pol = $LEX->getPolarity($lemma);
			}

			$tmp[] = ($sub) ? $sub : $nov;
			$tmp[] = ($pol) ? $pol : $nov;

			echo implode($sep, $tmp) . "\n";
		}
		echo "\n";
	}
}
