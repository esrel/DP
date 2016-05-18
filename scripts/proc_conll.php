<?php
/***
 * Convert CoNLL file format (CRF) into span per line format
 *
 * -f input file
 * -c list of columns to extract (e.g. -c 2,5)   [optional]
 * -u list of columns to unique  (e.g. -u 2,5)   [optional]
 * -l labels to extract                          [optional]
 * -s separator                                  [optional]
 * -r character replacement table (for icsi)     [optional]
 *
 * -- last column is the label column
 */
require 'IobTagger.php';
require 'IobReader.php';
require 'IdMapper.php';
require 'ConllReader.php';
require 'CharNormalizer.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:c:u:l:s:r:');

// set output separator
$sep = (isset($args['s'])) ? $args['s'] : "\t";
// set label to extract
$lbl = (isset($args['l'])) ? $args['l'] : NULL;

$IOB = new IobTagger('IOBE', FALSE);
$ITR = new IobReader();
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$CNR = new CharNormalizer();

// set character normalization if provided
if (isset($args['r'])) {
	$CNR->readPairs($args['r']);
}
else {
	$CNR->setPairs(array());
}

// Read Data as array of segments (no interpretation of ids)
$seg_arr = $CFR->conllRead($args['f']);
$col_num = $CFR->columnCount($seg_arr);

// get array of column ids to extract
$val = (isset($args['c']))
	   ? explode(',', $args['c'])
	   : range(0, $col_num - 1);

// get array of column ids to unique
$uni = (isset($args['u']))
	   ? explode(',', $args['u'])
	   : array();

// Get Span Values
$out  = array();
foreach ($seg_arr as $segID => $seg) {
	$val_arr = array();
	$lbl_arr = array();
	foreach ($seg as $tokID => $tok) {
		$lbl_arr[$tokID] = $ITR->iobSplit($tok[count($tok) - 1]);
		foreach ($val as $v) {
			$val_arr[$v][$tokID] = $tok[$v];
		}
	}

	$spans = $IOB->token2span($lbl_arr);

	foreach ($spans as $k => $span) {
		$label = $span[0];
		foreach ($val_arr as $v => $a) {
			$int = array_intersect_key($a, array_flip($span[1]));
			if (in_array($v, $uni)) {
				$int = array_unique($int);
			}
			$out[$segID][$label][$k][$v] = $int;
		}
	}
}

// Printing
foreach ($out as $segID => $seg) {
	foreach ($seg as $label => $spans) {
		if ($label == $lbl || $lbl === NULL) {
			foreach ($spans as $k => $span) {
				$tmp = array();
				foreach ($span as $v => $vals) {
					$tmp[] = $CNR->normalizeChars(implode(' ', $vals));
				}
				echo implode($sep, $tmp) . "\n";
			}
		}
	}
}
