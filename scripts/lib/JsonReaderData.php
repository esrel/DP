<?php
/**
 * (CoNLL 2015/2016 Shared Task on Shallow Discourse Parsing)
 *
 * Class to convert annotation to array &
 *
 *  1. Extract by Relation Type
 *  2. By Argument Configuration as SS or PS
 *
 * =====
 * JSON Schema for PDTB-data
 * =====
 * {
 * "Arg1":
 * 	{
 * 	 "CharacterSpanList": [[253, 261], [323, 498]],
 * 	 "RawText": "...",
 * 	 "TokenList": [[253, 256, 37, 1, 0], ...]
 * 	},
 * "Arg2":
 * 	{
 * 	 "CharacterSpanList": [[517, 629]],
 * 	 "RawText": "...",
 * 	 "TokenList": [[517, 519, 85, 2, 3], ...]
 *                  character offset begin,
 *                  character offset end,
 *                  token offset within the document,
 *                  sentence offset,
 *                  token offset within the sentence
 * 	},
 * "Connective":
 * 	{
 * 	 "CharacterSpanList": [],
 * 	 "RawText": "specifically"
 * 	},
 * "DocID": "wsj_2200",
 * "ID": 35708,
 * "Sense": ["Expansion.Restatement"],
 * "Type": "Implicit"
 * }
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class JsonReaderData {

	// Annotated Data
	public $types  = array('Explicit', 'Implicit', 'AltLex', 'EntRel', 'NoRel');
	public $spans  = array('Connective', 'Arg1', 'Arg2');
	public $offset = array(
		'b'      => 0,
		'e'      => 1,
		'dtID'   => 2,
		'sentID' => 3,
		'tokID'  => 4
	);
	private $ncfg = 0; // non-explicit argument configuration

	private $rel_arr;
	private $STDERR;

	public function __construct() {
		$this->rel_arr = array();
		$this->STDERR = fopen('php://stderr', 'w+');
	}

	/**
	 * Read array JSON objects into array of relations
	 * & Tag them as SS|PS|FS
	 * @param array $json_arr
	 */
	public function extractRelations($json_arr) {
		// parse json object into associative array
		$cnt = 0;
		foreach ($json_arr as $json_obj) {
			$arr = json_decode(trim($json_obj), TRUE);

			// Tag Explicit Relations
			if ($arr['Type'] == 'Explicit') {
				$cfg = $this->getArgConfiguration($arr);
				$arr['CFG'] = $cfg;
			}
			else {
				$arr['CFG'] = $this->ncfg; // to avoid errors
			}

			$docID = $arr['DocID'];
			$relID = $arr['ID'];
			$this->rel_arr[$docID . '-' . $relID] = $arr;
			$cnt++;
		}
		//fwrite($this->STDERR, 'T: ' . $cnt . "\n");
		//fwrite($this->STDERR, 'A: ' . count($this->rel_arr) . "\n");

		return $this->rel_arr;
	}

	/**
	 * Tag relation as SS|PS|FS
	 * @param  array  $rel
	 * @return string $cfg
	 */
	private function getArgConfiguration($rel) {
		$cfg = '__';

		$sent_ids = $this->getSentIds($rel);

		$ca1a2 = array_intersect(
				$sent_ids['Connective'],
				$sent_ids['Arg1'],
				$sent_ids['Arg2']);
		$a1a2 = array_intersect(
				$sent_ids['Arg1'],
				$sent_ids['Arg2']);

		// sentences
		$a1b = $sent_ids['Arg1'][0];
		$a1e = $sent_ids['Arg1'][count($sent_ids['Arg1']) - 1];
		$a2b = $sent_ids['Arg2'][0];
		$a2e = $sent_ids['Arg2'][count($sent_ids['Arg2']) - 1];


		if (!empty($ca1a2)) {
			$cfg = 'SS';
		}
		elseif (empty($a1a2) && $a1e < $a2b) {
			$cfg = 'PS';
		}
		elseif (empty($a1a2) && $a1b > $a2e) {
			$cfg = 'FS';
		}
		else {
			$cfg = '__';
		}

		return $cfg;
	}

	/**
	 * Get specific relations by
	 *    Type = Explicit|Implicit|etc.
	 *    CFG  = SS|PS|FS (Explicits only)
	 * @param string $type
	 * @param string $cfg
	 * @return array $arr
	 */
	public function getRelations($type = NULL, $cfg = NULL) {
		$arr = array();

		if ($type === NULL && $cfg === NULL) {
			return $this->rel_arr;
		}
		elseif ($type === NULL && $cfg !== NULL) {
			// Explicit Relation: shouldn't happen
			$type = 'Explicit';
			foreach ($this->rel_arr as $id => $rel) {
				if ($rel['Type'] == $type && $rel['CFG'] == $cfg) {
					$arr[$id] = $rel;
				}
			}
		}
		elseif ($type !== NULL && $cfg === NULL) {
			foreach ($this->rel_arr as $id => $rel) {
				if ($rel['Type'] == $type) {
					$arr[$id] = $rel;
				}
			}
		}
		else {
			foreach ($this->rel_arr as $id => $rel) {
				if ($rel['Type'] == $type && $rel['CFG'] == $cfg) {
					$arr[$id] = $rel;
				}
			}
		}
		return $arr;
	}

	/**
	 * Get relation in window
	 * @param array $rel_arr relation array
	 * @param int   $b       preceding sentence window
	 * @param int   $a       following sentence window
	 * @param bool  $c       connective sentence
	 */
	public function windowRelation($rel_arr, $b = 0, $a = 0, $c = TRUE) {
		$win_ids = array();
		// get connective sentence ID
		$sent_ids = $this->getSentIds($rel_arr);
		$conn_sent_id = $sent_ids['Connective'][0]; // first as a reference

		if ($c) {
			$win_ids[] = $conn_sent_id;
		}
		// make window
		while ($b > 0) {
			$win_ids[] = $conn_sent_id - $b;
			$b--;
		}

		while ($a > 0) {
			$win_ids[] = $conn_sent_id + $a;
			$a--;
		}

		// clean array
		sort($win_ids);
		return array_filter($win_ids, function ($v) { return $v >= 0; });
	}

	/**
	 * Get Unique array of sentence IDs, per span
	 * @param  array $rel
	 * @return array $ids
	 */
	public function getSentIds($rel) {
		$ids = array();
		foreach ($this->spans as $span) {
			$ids[$span] = array();
			if (isset($rel[$span]['TokenList'])) {
				foreach ($rel[$span]['TokenList'] as $tok) {
					$sentID = $tok[$this->offset['sentID']];
					$ids[$span][] = $sentID;
				}
			}
		}

		foreach ($ids as $span => $arr) {
			$ids[$span] = array_values(array_unique($arr));
		}
		return $ids;
	}

	/**
	 * Get Relation Argument spans as document-level token IDs
	 * @param  array $rel
	 * @return array $spans
	 */
	public function getSpans($rel) {
		$spans = array();
		foreach ($this->spans as $span) {
			$spans[$span] = array();
			if (isset($rel[$span]['TokenList'])) {
				foreach ($rel[$span]['TokenList'] as $tok) {
					$spans[$span][] = $tok[$this->offset['dtID']];
				}
			}
		}
		return $spans;
	}
}
