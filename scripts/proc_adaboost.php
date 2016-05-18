<?php
/***
 * Extract data from AdaBoost files
 *
 * -d data   file
 * -n names  file
 * -o output file
 * -c list of columns to extract
 *    (comma separated, without spaces, starting from 0)
 */
require 'AdaBoostReader.php';
require 'ArrayUtilities.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('d:n:o:c:');

$isep = ',';
$osep = "\t";

$ABR = new AdaBoostReader();
$ARU = new ArrayUtilities();

$labels  = array();
$columns = NULL;
$result  = array(); // output array

// Feature List
if (isset($args['c'])) {
	$columns = explode($isep, $args['c']);
}

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


// replace list separator in string
function split_join($str, $isep, $osep) {
	return implode($osep, array_map('trim', explode($isep, $str)));
}
