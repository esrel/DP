<?php
/**
 * Extracts IDs, types and senses for training Discourse Parsing Subtasks
 *
 * Parameters:
 * -f pdtb-data.json/relations.json
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/JsonReaderData.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:');

// Constants
$sep  = "\t";
$asep = ',';
$nov  = '_';

$JRD = new JsonReaderData();

// Extract Relations
$lines   = array_map('trim', file($args['f']));
$rel_arr = $JRD->extractRelations($lines);

foreach ($rel_arr as $id => $rel) {
	echo $rel['DocID'];
	echo $sep;
	echo $rel['ID'];
	echo $sep;

	// Sentence IDs for spans
	$sent_ids_arr = $JRD->getSentIds($rel);
	foreach ($JRD->spans as $span) {
		if (!empty($sent_ids_arr[$span])) {
			echo implode($asep, $sent_ids_arr[$span]);
		}
		else {
			echo $nov;
		}
		echo $sep;
	}

	// Connective Token Ids (document-level)
	$rspans = $JRD->getSpans($rel);
	if (!empty($rspans['Connective'])) {
		echo implode($asep, $rspans['Connective']);
	}
	else {
		echo $nov;
	}
	echo $sep;

	echo $rel['Type'];
	echo $sep;
	echo implode($asep, array_map('join_sense', $rel['Sense']));
	echo $sep;
	echo $rel['CFG'];
	echo "\n";
}

function join_sense($str) {
	return str_replace(' ', '-', $str);
}
