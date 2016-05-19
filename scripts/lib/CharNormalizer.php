<?php
/***
 * Class to do character normalization
 *
 * Encoding is UTF-8
 * Class
 *  - converts unicode quotes & dashes to ASCII
 *  - converts PTB replacements back to ASCII
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class CharNormalizer {

	private $pairs = array(
		// quotes
		array('“', '"'), // U+201C : opt + [
		array('”', '"'), // U+201D : opt + shift + [
		array('‘', "'"), // U+2018 : opt + ]
		array('’', "'"), // U+2019 : opt + shift + ]
		// low quote
		array('„', '"'), // U+201E : opt + shift + w
		// guillemets
		array('«', '"'), // U+00AB : opt + \
		array('»', '"'), // U+00BB : opt + shift + \
		array('‹', "'"), // U+2039 : opt + shift + 4
		array('›', "'"), // U+203A : opt + shift + 4
		/* Single to Multiple character replacements */
		// ndash to hyphen
		array('–', '--'),  // U+2013 : opt + -
		// mdash to hyphen
		array('—', '---'), // U+2014 : opt + shift + -
		// elipsis to 3 dots
		array('…', '...'), // U+2026 : opt + ;

	);

	private $PTB_pairs = array(
		array('-LRB-', '('),
		array('-RRB-', ')'),
		array('-LSB-', '['),
		array('-RSB-', ']'),
		array('-LCB-', '{'),
		array('-RCB-', '}'),
		array('``', '"'),
		array("''", '"'),
		array('`', "'"),
	);

	private $PTB_extra = array(
		array('\/', '/'),
		array('\*', '*'),
		array(' ', ' '), // U+00A0 : opt + space --> non-breaking space
	);

	private $i_arr;
	private $o_arr;

	/**
	 * Constructor: sets input and output arrays from $this->pairs array
	 */
	public function __construct() {
		$this->setPairs($this->pairs);
	}

	/**
	 * Normalize characters in a string w.r.t. $this->pairs
	 *
	 * @param  string $str
	 * @return string
	 */
	public function normalizeChars($str) {
		return str_replace($this->i_arr, $this->o_arr, $str);
	}

	/**
	 * Normalize characters after PTB Tokenization
	 *
	 * @param  string $str
	 * @return string
	 */
	public function normalizePtb($str, $extra = FALSE) {

		if ($extra) {
			$pairs = array_merge($this->PTB_pairs, $this->PTB_extra);
		}
		else {
			$pairs = $this->PTB_pairs;
		}

		$i_arr = array_column($pairs, 0);
		$o_arr = array_column($pairs, 1);

		return str_replace($i_arr, $o_arr, $str);
	}

	/**
	 * Setter for pairs
	 * @param array $arr
	 */
	public function setPairs($arr) {
		$this->pairs = $arr;
		$this->i_arr = array_column($this->pairs, 0);
		$this->o_arr = array_column($this->pairs, 1);
	}

	/**
	 * Getter for pairs
	 * @return array $arr
	 */
	public function getPairs() {
		return $this->pairs;
	}

	/**
	 * Reader for pairs -- sets $this->pairs
	 * @return array $arr
	 */
	public function readPairs($file) {
		$lines = array_map('trim', file($file));
		$pairs = array();
		foreach ($lines as $line) {
			if ($line != '' && !preg_match('/^#/u', $line)) {
				$pairs[] = preg_split('/\s/u', $line, -1, PREG_SPLIT_NO_EMPTY);
			}
		}
		$this->setPairs($pairs);
	}
}
//======================================================================
// Example Usage
//======================================================================
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:');
$CN = new CharNormalizer();

$str = "a”a’a„a—a–a";

echo json_encode($str) . "\n";

$out = $CN->normalizeChars($str);

echo json_encode($out) . "\n";

// PTB
$ptb = '-LSB- \* is `` this aaa';
echo json_encode(explode(' ', $ptb)) . "\n";
echo $CN->normalizePtb($ptb, TRUE) . "\n";

// User Input
if (isset($args['f'])) {
	$CN->readPairs($args['f']);
}
$str = 'cat is , on the mat . ';
echo $CN->normalizeChars($str) . "\n";
*/
