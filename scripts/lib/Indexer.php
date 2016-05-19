<?php
/**
 * Character Offset indexing
 *
 * (1) Indexes array of tokens by aligning to the raw string
 * (2) Indexes string w.r.t. spaces
 * (3) Indexes paragraphs w.r.t. separator
 *
 * e.g. array("The", "cat", ",") is mapped to "The cat," as
 *
 * array(
 * 	[0] array(
 * 		['tok'] => "The",		// surface string (from array)
 * 		['b']	=> 0,			// beginning index
 * 		['e'] 	=> 3,			// end index
 * 	),
 * 	[1] array("cat", 5, 8 ),
 * 	[2] array( "," , 9, 10),
 * );
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class Indexer {

	private $encoding = 'UTF-8'; // default encoding
	//private $re_space = '/[[:space:]]/u';
	private $re_space = '/\s/u'; // default token separator RegEx

	/**
	 * Constructor: sets encoding
	 *
	 * @param string $encoding
	 */
	public function __construct($encoding = 'UTF-8') {
		$this->encoding = $encoding;
	}

	/**
	 * Indexes array with begin and end character offsets w.r.t. string
	 *  OR Index string w.r.t. spaces
	 *
	 * @param  string $str
	 * @param  array  $arr
	 * @return array  $out
	 */
	public function indexTokens($str, $arr = NULL) {
		mb_internal_encoding($this->encoding);

		if ($arr == NULL) {
			$arr = preg_split($this->re_space, $str, -1, PREG_SPLIT_NO_EMPTY);
		}
		else {
			// Check Equality without spaces
			$arr_str = implode('', $arr);
			$str_str = preg_replace($this->re_space, '', $str);

			if ($arr_str !== $str_str) {
				return FALSE;
			}
		}

		$out = array();
		$b   = 0;

		// Iteratively get token offsets
		foreach ($arr as $k => $tok) {
			$out[$k]['tok'] = $tok;
			$tok = trim($tok);

			// consume all spaces
			while (preg_match($this->re_space, mb_substr($str,$b,1))) {
				$b += mb_strlen(mb_substr($str,$b,1));
			}

			// index token w.r.t. matched substring
			if (mb_substr($str, $b, mb_strlen($tok)) === $tok) {
				$out[$k]['b'] = $b;
				$out[$k]['e'] = $b + mb_strlen($tok);
				$b += mb_strlen($tok);
			}
			else {
				$stok = mb_substr($str, $b, mb_strlen($tok));
				die('ERROR: ' . $tok . ' != ' . $stok . "\n");
			}
		}

		return $out;
	}

	/**
	 * Creates array with begin and end character offsets for paragraphs
	 *
	 * @param  string $str
	 * @param  string $sep
	 * @return array  $out
	 */
	public function indexParagraphs($str, $sep = "\n") {
		mb_internal_encoding($this->encoding);

		$slen = mb_strlen($sep); // length of paragraph separator
		$out  = array();
		$b    = 0;

		$arr = explode($sep, $str);
		foreach ($arr as $seg) {
			$len = mb_strlen($seg);
			// skip empty segments (i.e. empty lines)
			if ($len > 0) {
				$out[] = array('b' => $b, 'e' => $b + $len);
			}
			// increment begin index by lengths of segment & separator
			$b = $b + $len + $slen;
		}
		return $out;
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

$IND = new Indexer();

// Token Indexing
echo 'Token Indexing:' . "\n";
$arr = array('the', 'cat', "'s", 'nest', ',');
$str = "the\tcat's nest,\n";
print_r($IND->indexTokens($str, $arr));
echo 'Token Indexing w/o array:' . "\n";
print_r($IND->indexTokens($str));

// Paragraph Indexing
echo 'Paragraph Indexing:' . "\n";
$par_sep = "\n\n"; // paragraph separator
$str = (isset($args['f'])) ? file_get_contents($args['f']) : $str;
print_r($IND->indexParagraphs($str, $par_sep));
*/
