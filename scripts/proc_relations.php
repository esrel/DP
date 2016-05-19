<?php
/**
 * Extracts Features for training Discourse Parsing Subtasks
 *
 * 1. Discourse Connective Detection     [DCD] Explicit
 * 2. Argument Span Extraction           [ASE] Explicit: SS|PS
 *
 * Parameters:
 * -f pdtb-data.json/relations.json
 * -t token-level IDs file
 * -n output file name (default 'tmp')
 *
 * --ips extact data for evaluation, including all IPS spans
 * --rmm remove multi-sentence spans
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */

// requires & includes
require 'lib/JsonReaderData.php';
require 'lib/IobTagger.php';
require 'lib/IobReader.php';
require 'lib/IdMapper.php';
require 'lib/ConllReader.php';

// Error Reporting & Memory Limit
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$longopts = array('ips', 'rmm');
$args = getopt('f:n:t:', $longopts);

// Class Initializations
$JRD = new JsonReaderData();
$IOB = new IobTagger('IOBE');
$ITR = new IobReader();
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();

/*--------------------------------------------------------------------*/
// Constants
$lbls = array(
	'Connective' => 'CONN',
	'Arg1'       => 'ARG1',
    'Arg2'       => 'ARG2'
);

// output file name & array keys
$keys = array(
	'CONN',
	'SS.A1', 'SS.A2',
	'PS.A1', 'PS.A2',
	'NE.A1', 'NE.A2'
);

/* output arrays & file names */
$fn = (isset($args['n'])) ? $args['n'] : 'tmp';
$fn_arr = array(); // output file names
foreach ($keys as $k) {
	$fn_arr[$k] = $fn . '.' . $k . '.lbl';
}

/*--------------------------------------------------------------------*/
$ot_arr = array(); // output array for tags

// Read Document/Token IDs into document-level array
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['t']));
$doc_arr = $IDM->remap($row_arr, array(0, 1, 2));

// Extract Relations
$lines   = array_map('trim', file($args['f']));
$rel_arr = $JRD->extractRelations($lines);

foreach ($rel_arr as $id => $rel) {
	foreach ($JRD->spans as $span) {
		// check if span is set
		if (!empty($rel[$span]['TokenList'])) {
			$sent_lst = array();
			$span_lst = array();
			foreach ($rel[$span]['TokenList'] as $tok) {
				// span document-level token IDs
				$span_lst[] = $tok[$JRD->offset['dtID']];
				// map sentence/token IDs to document-level token IDs
				$sent_lst[$tok[$JRD->offset['sentID']]][$tok[$JRD->offset['tokID']]] =
						  $tok[$JRD->offset['dtID']];
			}
			// IOB-tag the span
			$tagged = array_map(
						array($ITR, 'iobJoin'),
						$IOB->tagSpan($lbls[$span], $span_lst, 'IOBE'));

			// Add Labels to Document Array as token-level links
			foreach ($sent_lst as $sentID => $sent) {
				foreach ($sent as $tokID => $dtID) {
					$doc_arr[$rel['DocID']][$sentID][$tokID]['Links'][$id][$span] = $tagged[$dtID];
				}
			}
		}
	}
}

// DEBUG:
//print_r($doc_arr);
//die();
/*****
 Generate Training Data
 1. Discourse Connective Detection
    a. Whole Document
    b. Explicits only
 2. Argument Span Extraction

*****/
// CONN Data
$conn_rel_arr = $JRD->getRelations('Explicit');
$conn_arr = array(); // array for connective relation IDs
foreach ($doc_arr as $docID => $doc) {
	foreach ($doc as $sentID => $sent) {
		foreach ($sent as $tokID => $tok) {

			$tag   = $otag;
			$relID = $nov;
			$str   = $nov;

			if (isset($tok['Links'])) {
				foreach ($tok['Links'] as $id => $rel) {
					if (isset($conn_rel_arr[$id]) && isset($rel['Connective'])) {
						$tag   = $rel['Connective'];
						$relID = $conn_rel_arr[$id]['ID'];
						$str   = $conn_rel_arr[$id]['Connective']['RawText'];
					}
				}
			}

			$ot_arr['CONN'][$docID][$sentID][$sentID][$tokID] =
				array($docID, $sentID, $sentID, $tokID, $tag);
			$conn_arr[$docID][$sentID][$tokID] =
				array($docID, $sentID, $tokID, $relID, $str, $tag);
		}
	}
}

// SS Explicit Arg1 & Arg2
$ss_rel_arr = $JRD->getRelations('Explicit', 'SS');
foreach ($ss_rel_arr as $id => $rel) {
	$ids    = $JRD->getSentIds($rel);

	// check for multi-sentence spans
	if (isset($args['rmm'])
		&& (count($ids['Arg1']) > 1 || count($ids['Arg2']) > 1)) {
		// SKIP both Arg1 & Arg2
	}
	else {
		$sentID = $ids['Connective'][0];
		// Arg1
		$ot_arr['SS.A1'][$rel['DocID']][$rel['ID']][$sentID] =
				tag_segment($doc_arr[$rel['DocID']][$sentID],
					$id, $otag,
					array($rel['DocID'], $rel['ID'], $sentID),
					array('Connective', 'Arg2', 'Arg1'));
		// Arg2
		$ot_arr['SS.A2'][$rel['DocID']][$rel['ID']][$sentID] =
				tag_segment($doc_arr[$rel['DocID']][$sentID],
					$id, $otag,
					array($rel['DocID'], $rel['ID'], $sentID),
					array('Connective', 'Arg2'));
	}
}

// PS Explicit:
// Arg2 --> connective sentence
// Arg1 --> immediately previous sentence
$ps_rel_arr = $JRD->getRelations('Explicit', 'PS');
foreach ($ps_rel_arr as $id => $rel) {
	$ids = $JRD->getSentIds($rel);
	$arg2_ids = $JRD->windowRelation($rel);
	$arg1_ids = $JRD->windowRelation($rel, 1, 0, FALSE);
	$A2ID = $arg2_ids[0];
	$A1ID = $arg1_ids[0];

	// Arg1
	// check for multi-sentence spans
	if (isset($args['rmm']) && count($ids['Arg1']) > 1) {
		// SKIP
	}
	// check for empty ips & output only if --ips is set
	elseif (isset($args['ips']) || in_array($A1ID, $ids['Arg1'])) {
		$ot_arr['PS.A1'][$rel['DocID']][$rel['ID']][$A1ID] =
			tag_segment($doc_arr[$rel['DocID']][$A1ID],
				$id, $otag,
				array($rel['DocID'], $rel['ID'], $A1ID),
				array('Arg1'));
	}

	// Arg2
	// check for multi-sentence spans
	if (isset($args['rmm']) && count($ids['Arg2']) > 1) {
		// SKIP
	}
	else {
		$ot_arr['PS.A2'][$rel['DocID']][$rel['ID']][$A2ID] =
			tag_segment($doc_arr[$rel['DocID']][$A2ID],
				$id, $otag,
				array($rel['DocID'], $rel['ID'], $A2ID),
				array('Connective', 'Arg2'));
	}
}

// Non-Explicit Arg1 & Arg2
foreach ($rel_arr as $id => $rel) {

	if ($rel['Type'] == 'Explicit') {
		continue;
	}

	$ids = $JRD->getSentIds($rel);

	// NE Arg1:
	// check for multi-sentence spans
	if (isset($args['rmm']) && count($ids['Arg1']) > 1) {
		// skip
	}
	else {
		// get sentence IDs: last for Arg1
		$A1ID = $ids['Arg1'][count($ids['Arg1']) - 1];
		$ot_arr['NE.A1'][$rel['DocID']][$rel['ID']][$A1ID] =
			tag_segment($doc_arr[$rel['DocID']][$A1ID],
				$id, $otag,
				array($rel['DocID'], $rel['ID'], $A1ID),
				array('Arg1'));
	}

	// NE Arg2:
	// check for multi-sentence spans
	if (isset($args['rmm']) && count($ids['Arg2']) > 1) {
		// skip
	}
	else {
		// get sentence IDs: 1st for Arg2
		$A2ID = $ids['Arg2'][0];
		$ot_arr['NE.A2'][$rel['DocID']][$rel['ID']][$A2ID] =
			tag_segment($doc_arr[$rel['DocID']][$A2ID],
				$id, $otag,
				array($rel['DocID'], $rel['ID'], $A2ID),
				array('Arg2'));
	}
}

// Write CRF arrays
foreach ($keys as $k) {
	write_data_conll($ot_arr[$k], $fn_arr[$k]);
}

// Write Connective relation IDs
write_data_conn($conn_arr, $fn . '.CONN.ids');

/* FUNCTIONS */
/**
 * Tag segment/sentense with IOB-tagged labels
 *
 * @param  array  $sent    sentence array
 * @param  string $id      relation array index
 * @param  string $otag    out-of-chunk tag
 * @param  array  $arr     array of IDs
 * @param  array  $spans   array of spans to tag
 *
 * @return array  $out
 */
function tag_segment($sent, $id, $otag, $arr, $spans) {
	$out = array();
	foreach ($sent as $tokID => $tok) {
		$tmp   = $arr;
		$tmp[] = $tokID;
		foreach ($spans as $span) {
			if (isset($tok['Links'][$id][$span])) {
				$tmp[] = $tok['Links'][$id][$span];
			}
			else {
				$tmp[] = $otag;
			}
			$out[$tokID] = $tmp;
		}
	}
	return $out;
}

// Print data
function print_data_conll($arr) {
	$sep = "\t";
	foreach ($arr as $docID => $doc) {
		foreach ($doc as $relID => $rel) {
			foreach ($rel as $sentID => $sent) {
				foreach ($sent as $tokID => $tok) {
					echo implode($sep, $tok) . "\n";
				}
				echo "\n";
			}
		}
	}
}

// Write Data
function write_data_conll($arr, $fn) {
	$fh = fopen($fn, 'w');
	$sep = "\t";
	foreach ($arr as $docID => $doc) {
		foreach ($doc as $relID => $rel) {
			foreach ($rel as $sentID => $sent) {
				foreach ($sent as $tokID => $tok) {
					fwrite($fh, implode($sep, $tok) . "\n");
				}
				fwrite($fh, "\n");
			}
		}
	}
	fclose($fh);
}

function write_data_conn($arr, $fn) {
	$fh = fopen($fn, 'w');
	$sep = "\t";
	foreach ($arr as $docID => $doc) {
		foreach ($doc as $sentID => $sent) {
			foreach ($sent as $tokID => $tok) {
				fwrite($fh, implode($sep, $tok) . "\n");
			}
			fwrite($fh, "\n");
		}
	}
	fclose($fh);
}
