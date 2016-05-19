<?php
/***
 * Extract data from AdaBoost files
 *
 * -d data   file
 * -n names  file
 * -o output file
 * -c list of columns to extract
 *    (comma separated, without spaces, starting from 0)
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/AdaBoostReader.php';
require 'lib/ArrayUtilities.php';

// Error Reporting
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('d:n:o:c:');

// Constants
$isep = ',';
$osep = "\t";

// Classes
$ABR = new AdaBoostReader();
$ARU = new ArrayUtilities();

$labels  = array(); // labels array
$result  = array(); // output array

// feature list
$columns = (isset($args['c'])) ? explode($isep, $args['c']) : NULL;

// read data file
if (isset($args['d'])) {
	$lines = array_map('trim', file($args['d']));
	foreach ($lines as $k => $line) {
		if ($line != '') {
			// remove final period
			$la = explode($isep, substr($line, 0, -1));

			if ($columns) {
				foreach ($columns as $key) {
					$result[$k][] = split_join($la[$key], ' ', $isep);
				}
			}
			else {
				// take whole vector as is
				$result[$k] = $la;
			}
		}
	}
}

// Get decisions
if (isset($args['n']) && isset($args['o'])) {
	$labels = $ABR->getDecisions($args['o'], $args['n']);
}

// PRINTING
$out = array_merge_recursive(
		$ARU->arrayKeyToString($result),
		$ARU->arrayKeyToString($ARU->arrayExpand($labels)));

foreach ($out as $vec) {
	echo implode($osep, $vec) . "\n";
}

//----------------------------------------------------------------------
// Functions
//----------------------------------------------------------------------

/**
 * replace list separator in string
 *
 * @param string $str
 * @param string $isep
 * @param string $osep
 *
 * @return string
 */
function split_join($str, $isep, $osep) {
	return implode($osep, array_map('trim', explode($isep, $str)));
}
