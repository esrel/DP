<?php
/**
 * @author	: Evgeny A. Stepanov
 * @e-mail	: stepanov.evgeny.a@gmail.com
 *
 * Various utilities for syntactic parse tree processing
 *
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

// Test Cases:
/*
$args = getopt('f:');
$PR   = new ParseTreeUtilities('((', '))');
$arr  = explode("\n", $PR->flatten(file_get_contents($args['f'])));
print_r($arr);
*/
