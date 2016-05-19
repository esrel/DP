<?php
/**
 * Various utilities for syntactic parse tree processing
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class ParseTreeUtilities {

	private $root;
	private $end;

	/**
	 * Constructor: set parse tree root and end as '((' or '(ROOT('
	 * @param string $root
	 * @param string $end
	 */
	public function __construct($root = '((', $end = '))') {
		$this->root = $root;
		$this->end  = $end;
	}

	/**
	 * Flattens parse tree string (document) into tree-per-line format
	 *
	 * @param  string $str
	 * @return string
	 */
	public function flatten($str) {
		// newlines to spaces
		$str = str_replace("\n", ' ', $str);
		// remove all extra spaces
		$str = trim($str);
		$str = preg_replace('/\( *\(/u', '((', $str);
		$str = preg_replace('/\) *\)/u', '))', $str);
		$str = preg_replace('/\) *\(/u', ')(', $str);
		$str = preg_replace('/  */u'   , ' ' , $str);
		// split trees
		$str = str_replace(
				"$this->end$this->root",
				"$this->end\n$this->root",
				$str);

		return $str;
	}

	/**
	 * Removes root tag from a single parse tree (flat or not)
	 *
	 * ... Some parsers add '(ROOT ('
	 *
	 * @param  string $parse
	 * @param  string $root
	 * @return string
	 */
	public function rmRoot($parse, $root = 'ROOT') {
		return preg_replace("/^\( *$root *\(/u", '((', trim($parse));
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
$PR   = new ParseTreeUtilities('((', '))');

$parse  = '( (S (NP (NP (JJ Influential) (NNS members)) ';
$parse .= '(PP (IN of) (NP (DT the) (NNP House) (NNPS Ways) (CC and) ';
$parse .= '(NNPS Means) (NNP Committee)))) (VP (VBD introduced) ';
$parse .= '(NP (NP (NN legislation)) (SBAR (WHNP (WDT that)) ';
$parse .= '(S (VP (MD would) (VP (VB restrict) ';
$parse .= '(SBAR (WHADVP (WRB how)) (S (NP (DT the) (JJ new) ';
$parse .= '(JJ savings-and-loan) (NN bailout) (NN agency)) ';
$parse .= '(VP (MD can) (VP (VB raise) (NP (NN capital))))))))))) ';
$parse .= '(, ,) (S (VP (VBG creating) (NP (NP (DT another) ';
$parse .= '(JJ potential) (NN obstacle)) (PP (TO to) ';
$parse .= "(NP (NP (NP (DT the) (NN government) (POS 's)) (NN sale)) ";
$parse .= '(PP (IN of) (NP (JJ sick) (NNS thrifts))))))))) (. .)) )';

$parse = (isset($args['f'])) ? file_get_contents($args['f']) : $parse;

$arr  = explode("\n", $PR->flatten($parse));
print_r($arr);
*/
