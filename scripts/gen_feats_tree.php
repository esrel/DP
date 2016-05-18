<?php
/***
 * Generate "Syntactic Tree Features" for tokens
 *
 * -t input file token
 * -p parse tree file
 * -i parse tree id file
 */
require 'SyntacticTreeFeatures.php';
require 'IdMapper.php';
require 'ConllReader.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('t:p:i:');

$sep = "\t";

// Class Initializations
$IDM = new IdMapper();
$CFR = new ConllReader();

/*--------------------------------------------------------------------*/
// read parse trees
$parses = array_map('trim', file($args['p']));

// read parse tree ids
$lines  = array_map('trim', file($args['i']));
$par_arr = array();
foreach ($lines as $k => $line) {
	if ($line != '') {
		$la = preg_split('/\s/u', $line, -1, PREG_SPLIT_NO_EMPTY);
		$par_arr[$la[0]][$la[1]] = $parses[$k];
	}
}

// read token file
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['t']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		$ptree = new SyntacticTreeFeatures($par_arr[$docID][$sentID]);
		foreach ($sent as $tokID => $tok) {
			$str = $tok[0];
			$tmp = array($docID, $sentID, $tokID, $str);
			$fa  = get_syntactic_features($ptree, array($str), array($tokID));
			$tmp = array_merge($tmp, $fa);
			echo implode($sep, $tmp) . "\n";
		}
		echo "\n";
	}
}

/**
 * Generate tree features
 */
function get_syntactic_features($ptree, $tok_arr, $tid_arr) {
	$out = array();
	$matches = $ptree->getNodes($tok_arr, $tid_arr);
	// only one match due to $tid_arr
	$lcp = $ptree->getCommonParent($matches[0], TRUE);
	$hcp = $ptree->getHighestSelfNode($lcp);
	$sl  = $ptree->getSiblingLeft($hcp);
	$sr  = $ptree->getSiblingRight($hcp);
	$out[] = ($hcp !== NULL) ? $hcp->tag : 'NULL';
	$out[] = (isset($hcp->parent)) ? $hcp->parent->tag : 'NULL';
	$out[] = ($sl !== NULL) ? $sl->tag : 'NULL';
	$out[] = ($sr !== NULL) ? $sr->tag : 'NULL';
	return $out;
}
