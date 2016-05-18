<?php
/**
 * Reads PDTB-parse JSON into array
 *
 * =====
 * JSON Schema for parses
 * =====
 * {
 * "DocID":
 * 	{
 * 	 "sentences":
 * 	 	{
 * 	 	"dependencies": [[
 *                        dependency_type,
 *                        HEAD_str-HEAD-position,
 *                        DEPENDENT_str-DEPENDENT-position << TOKEN
 *                       ], ...],
 * 	 	"parsetree": "( (S (...)))",
 * 	 	"words": [
 * 	 		  [
 *			   token, {
 *			   	"CharacterOffsetBegin": int,
 *			   	"CharacterOffsetEnd": int,
 *			   	"Linkers": [arg1_3207]  // role_relationID
 *			   	"PartOfSpeech": NNP
 *			   	}
 *			   ],
 *			   ...
 *			  ]
 *		}
 * 	},
 * "DocID": {...}
 * }
 */
class JsonReaderParses {

	/*
	 * Document Array as
	 *     [DocID][SentenceID][TokenID] (all int)
	 *         ['TokenString']          => string
	 *         ['PartOfSpeech']         => string
	 *         ['CharacterOffsetBegin'] => int
	 *         ['CharacterOffsetEnd']   => int
	 *         ['Linkers']              => array of strings
	 */
	private $doc_arr;
	private $par_arr;	// Constiturency Parse Trees as [DocID][SentenceID]
	private $dep_arr;	// Dependency Parses         as [DocID][SentenceID]

	function __construct() {
		$this->doc_arr = array();
		$this->par_arr = array();
		$this->dep_arr = array();
	}

	/**
	 * Parser json string and populates class arrays
	 * @param string $json_str
	 */
	public function parseJson($json_str) {
		// parse json into associative array
		$arr = json_decode(trim($json_str), TRUE);

		foreach ($arr as $docID => $doc) {
			$dtID = 0;
			foreach ($doc['sentences'] as $sentID => $sent) {
				// Tokens
				foreach ($sent['words'] as $tokID => $tok) {
					$tok_arr = array();
					$tok_arr['TokenString']  = $tok[0];
					$tok_arr['PartOfSpeech'] = $tok[1]['PartOfSpeech'];
					$tok_arr['CharacterOffsetBegin'] = $tok[1]['CharacterOffsetBegin'];
					$tok_arr['CharacterOffsetEnd']   = $tok[1]['CharacterOffsetEnd'];
					$tok_arr['DocTokID'] = $dtID;
					$this->doc_arr[$docID][$sentID][$tokID] = $tok_arr;
					$dtID++;
				}

				// Constituency Parse Trees
				$this->par_arr[$docID][$sentID] = trim($sent['parsetree']);

				//Dependency Parse
				$this->dep_arr[$docID][$sentID] = $sent['dependencies'];
			}
		}
	}

	/**
	 * Returns parse tree array
	 * @return array $this->par_arr
	 */
	public function getParseTrees() {
		return $this->par_arr;
	}

	/**
	 * Returns dependency array
	 * @return array $this->dep_arr
	 */
	public function getParseDep() {
		return $this->dep_arr;
	}

	/**
	 * Returns document array
	 * @return array $this->doc_arr
	 */
	public function getDocArr() {
		return $this->doc_arr;
	}
}
