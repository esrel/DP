<?php
/***
 * Class for tagging array of tokens with presence in provided lexicon
 *
 * Lexicon entries are either:
 *  - single word
 *  - space separated
 *  - '..' separated to indicate discontinuous spans
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */

require_once 'Indexer.php';

class LexiconTagger extends Indexer {

	private $lex;         // lexicon
	private $regex;       // regex
	private $tags;        // tags

	// Local
	private $dere = '.*';

	// Input Lexicon Separators
	private $mws  = ' ';  // separator/glue for multiword     entries
	private $dws  = '..'; // separator/glue for discontinuous entries

	private $tsep = '_';  // separator/glue for multiword entries (tag)

	// string tag definitions
	private $isep = '#';  // separator/glue for tagging with IDs
	private $lsep = ',';  // separator/glue for tag list
	private $nov  = 0;    // no value

	/**
	 * Constructor: sets lexicon for tagging, if provided
	 *
	 *    (without lexicon, class used for parsing tags only)
	 *
	 * @param file   $file  lexicon file
	 * @param string $mws   multi-word entity glue/separator
	 * @param string $dws   discountinuous entity glue/separator
	 * @param string $tsep  tag space separator
	 */
	public function __construct($file = NULL, $mws = ' ', $dws = '..',
								$tsep = '_') {
		if ($file) {
			// lowercase all lexicon
			$lex = array_map('strtolower', array_map('trim', file($file)));
			// sort by length
			array_multisort(array_map('strlen', $lex), $lex);
			$this->lex = $lex;
			// Generate RegEx & Tags
			$this->regex = array_map(array($this, 'mkRegEx'), $this->lex);
			$this->tags  = array_map(array($this, 'mkTag'), $this->lex);
		}

		// set separators
		$this->mws  = $mws;
		$this->dws  = $dws;
		$this->tsep = $tsep;
	}

	/**
	 * Create a regular expression from lexicon entry
	 *
	 * @param  string $str
	 * @return string $re
	 */
	private function mkRegEx($str) {

		$dre = '/' . preg_quote($this->dws) . '/u';
		$mre = '/' . preg_quote($this->mws) . '/u';
		$gsep   = '\b).*(\b';

		$re = $str;
		$re = preg_replace($mre, ' '  , $re);
		$re = preg_replace($dre, $gsep, $re);
		$re = '/\b(' . $re . '\b)/u';

		return $re;
	}

	/**
	 * Create a tag from lexicon entry
	 *
	 * @param  string $str
	 * @return string
	 */
	private function mkTag($str) {

		$mre = '/' . preg_quote($this->mws) . '/u';

		return preg_replace($mre, $this->tsep , $str);
	}

	/**
	 * Tag input string/array with lexical entries
	 *
	 * @param  mixed $input
	 * @param  bool  $bool  // boolean tagging ON
	 * @return array $arr
	 */
	public function tag($input, $bool = TRUE) {

		$reg_arr = $this->regex;
		$tag_arr = $this->tags;
		$dre     = '/' . preg_quote($this->dws) . '/u';

		// convert input to lowercased string, index tokens
		if (is_array($input)) {
			$str = implode($this->mws, $input);
			$ind = $this->indexTokens($str, $input);
		}
		else {
			$str = $input;
			$ind = $this->indexTokens($str);
		}

		$str = strtolower($str);
		// set all tokens as not in lexicon
		$arr = array_fill(0, count($ind), array());

		// get all matching lexical entries
		$match_arr = array();
		foreach ($reg_arr as $k => $regex) {
			// required to handle 'mirrored' items w/ shared parts
			if (preg_match($dre, $tag_arr[$k])) {
				preg_match_all($regex, $str, $matches,
							   PREG_OFFSET_CAPTURE);
				$cnt = 0;
				while (!empty($matches[0])) {
					$parts = array_slice($matches, 1);
					foreach ($parts as $pk => $p) {
						foreach ($p as $mk => $m) {
							list($tok, $beg) = $m;
							if ($pk == 0 && $mk == 0) {// move offset
								$off = $beg + strlen($tok);
							}
							$match_arr[$k][$cnt][$pk] = $m;
						}
					}
					preg_match_all($regex, $str, $matches,
								   PREG_OFFSET_CAPTURE, $off);
					$cnt++;
				}
			}
			else {
				preg_match_all($regex, $str, $matches,
							   PREG_OFFSET_CAPTURE);
				if (!empty($matches[0])) {
					$parts = array_slice($matches, 1);
					// remap
					foreach ($parts as $pk => $p) {
						foreach ($p as $mk => $m) {
							$match_arr[$k][$mk][$pk] = $m;
						}
					}
				}
			}
		}

		// tag array with matches
		$id = 0;
		foreach ($match_arr as $k => $e) {  // lexical entries
			foreach ($e as $km => $m) {     // matches
				$tok_ids = array();

				foreach ($m as $pk => $p) { // parts
					list($tok, $beg) = $p;
					$end = $beg + strlen($tok);
					foreach ($ind as $i => $t) {
						if ($t['b'] >= $beg && $t['e'] <= $end) {
							$tok_ids[] = $i;
						}
					}
				}

				foreach ($tok_ids as $tokID) {
					$tmp   = array($tag_arr[$k]);
					$tmp[] = implode($this->lsep, $tok_ids);
					$tmp[] = $id;
					$arr[$tokID][] = implode($this->isep, $tmp);
				}
				$id++;
			}
		}

		// convert to boolean, if set (by default)
		foreach ($arr as $tokID => $e) {
			if ($bool) {
				$arr[$tokID] = (!empty($e)) ? 1 : 0;
			}
			else {
				$arr[$tokID] = (!empty($e)) ? $e : array($this->nov);
			}
		}

		return $arr;
	}

	/**
	 * Parse string annotation into an array of components
	 *
	 * @param  string $str
	 * @return array
	 */
	public function parseTag($str) {
		$arr = explode($this->isep, $str);
		if (count($arr) == 1) {
			return $str;
		}
		else {
			$ta = explode($this->lsep, $arr[1]);
			$wa = explode(' ' ,
				str_replace(
					array($this->dws, $this->mws),
					array(' ' , ' '),
					$arr[0]));
			return array($arr[0], $ta, $wa, $arr[2]);
		}
	}

	/**
	 * Get lexicon
	 *
	 * @return array $lex
	 */
	public function getLexicon() {
		return $this->lex;
	}

	/**
	 * Set tag list separator and id separator strings
	 *
	 * @param string $lsep  list separator
	 * @param string $isep  id   separator
	 */
	public function setIndexSeparators($lsep, $isep) {
		$this->lsep = $lsep;
		$this->isep = $isep;
	}

	/**
	 * Set no value string
	 *
	 * @param string $nov
	 */
	public function setNoValue($nov) {
		$this->nov = $nov;
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

$LTG = new LexiconTagger($args['f']);

$str  = 'On the one hand ';
$str .= 'if then ';
$str .= 'The above represents a triumph of either apathy or civility .';
$str .= ' as if';
$str .= ' on the other hand';

$arr = preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);

print_r($LTG->getLexicon());
print_r($LTG->tag($arr, FALSE));
print_r($LTG->tag($str));
*/
