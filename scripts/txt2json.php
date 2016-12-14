<?php
/**
 * Convert raw text to json for Discourse Parsing
 *
 * -r raw txt file
 * -t tokenized txt file (sentence per line, space separated)
 * -p constituency parse tree 
 * -d dependency parse tree
 */

/*
   Output file format
   {"docID": 
      {"sentences": 
         [
            {"dependencies": 
               [
                  ["amod", "members-2", "Influential-1"],
                  ["root", "ROOT-0", "introduced-10"]
               ], 
             "parsetree": "( (S (, ,)) )\n",
             "words": 
               [
                  ["Influential", 
                     {"CharacterOffsetBegin": 9, 
                      "CharacterOffsetEnd": 20, 
                      "Linkers": [], 
                      "PartOfSpeech": "JJ"
                     }
                  ],
               ]
            }]}
*/

ini_set('memory_limit', -1);
$args = getopt('r:t:p:d:');

require 'lib/FileReader.php';
require 'lib/TokenIndexer.php';

$FR = new FileReader();
$TI = new TokenIndexer();

// read input
$raw_str = file_get_contents($args['r']);
$par_arr = array_map('trim', file($args['p']));
$dep_arr = array_map('trim', file($args['d']));
$tok_arr = $FR->readFile($args['t'], 'doc');

$untok_arr = PTB2norm($tok_arr); // TODO: test properly
$ind_arr = $TI->indexArr2Str($untok_arr, $raw_str);

// Get POS-tags
$jpos_arr = array();
$jtok_arr = array();
foreach ($par_arr as $k => $ptree) {
	$a = preg_split('/ *\( *| *\) */u', $ptree, -1, PREG_SPLIT_NO_EMPTY);
	foreach ($a as $m) {
		if (preg_match('/\s+/u', $m)) {
			$w = preg_split('/\s+/u', $m, -1, PREG_SPLIT_NO_EMPTY);
			$jpos_arr[$k][] = trim($w[0]);
			$jtok_arr[$k][] = trim($w[1]);
		}
	}
}

// parse dependencies
$sentID = 0;
$jdep_arr = array();
foreach ($dep_arr as $k => $dtree) {
	if ($dtree == '') {
		$sentID++;
	}
	else {
		// compound(members-3, START-1) ==> array: "amod", "members-2", "Influential-1"
		$tmp0 = array_map('trim', explode('(', substr($dtree, 0, -1)));
		$tmp1 = array_map('trim', explode(',', $tmp0[1]));
		$jdep_arr[$sentID][] = array($tmp0[0], $tmp1[0], $tmp1[1]);
	}
}

$sent_arr = array();
foreach ($tok_arr as $sentID => $sent) {
	$sent_arr[$sentID]['dependencies'] = $jdep_arr[$sentID];
	$sent_arr[$sentID]['parsetree']    = $par_arr[$sentID];
	$word_arr = array();
	foreach ($sent as $tokID => $tok) {
		$word_arr[] = array($tok, array(
				'CharacterOffsetBegin' => $ind_arr[$sentID][$tokID]['b'],
				'CharacterOffsetEnd'   => $ind_arr[$sentID][$tokID]['e'],
				'PartOfSpeech'         => $jpos_arr[$sentID][$tokID]
			));
	}
	$sent_arr[$sentID]['words'] = $word_arr;
}
$json_arr = array($args['r'] => array('sentences' => $sent_arr));
echo json_encode($json_arr) . "\n";


// DEBUG:
//print_r($par_arr);
//print_r($jtok_arr);
//print_r($jpos_arr);
//print_r(($jtok_arr == $tok_arr));
//print_r($jdep_arr);
//print_r($ind_arr);

function PTB2norm($arr) {
	$safe_arr = array(
			'-LRB-', '-RRB-',
			'-LSB-', '-RSB-',
			'-LCB-', '-RCB-',
			"''", "``",
			'\\*', '\/',
			"`", 
	);
	
	$unsafe_arr = array(
			'(', ')',
			'[', ']',
			'{', '}',
			'"', '"',
			'*', '/',
			"'",
	);
	$out = array();
	foreach ($arr as $sentID => $sent) {
		foreach ($sent as $tokID => $tok) {
			if (in_array($tok, $safe_arr)) {
				$out[$sentID][$tokID] = str_replace($safe_arr, $unsafe_arr, $tok);
			}
			else {
				$out[$sentID][$tokID] = $tok;
			}
		}
	}
	return $out;
}