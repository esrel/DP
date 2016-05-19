<?php
/**
 * Paragraph Indexing
 *
 * -r raw documents
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */

require 'lib/Indexer.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('r:');

// Constants settings
$par_sep = "\n\n";
$col_sep = "\t";

$IND = new Indexer();

// Get docID from file name
$t0 = explode('/', $args['r']);
$t1 = explode('.', $t0[count($t0) - 1]);
$docID = $t1[0];

// Index Paragraphs: print boundaries
$doc_str = file_get_contents($args['r']);
$par_ind = $IND->indexParagraphs($doc_str, $par_sep);

foreach ($par_ind as $k => $par) {
	echo $docID;
	echo $col_sep;
	echo $k;
	echo $col_sep;
	echo implode($col_sep, $par);
	echo "\n";
}
