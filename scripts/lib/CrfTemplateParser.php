<?php
/***
 * CRF++ Template Parser class
 *
 * Example:
 * [row,column] indices
 *
 * #F07: Token
 * U07_1b2:%x[-2,7]
 * U07_1b1:%x[-1,7]
 * U07_1c0:%x[0,7]
 * U07_1a1:%x[1,7]
 * U07_1a2:%x[2,7]
 * U07_2b2:%x[-2,7]/%x[-1,7]
 * U07_2b1:%x[-1,7]/%x[0,7]
 * U07_2c0:%x[0,7]/%x[1,7]
 * U07_2a1:%x[1,7]/%x[2,7]
 * U07_3b2:%x[-2,7]/%x[-1,7]/%x[0,7]
 * U07_3b1:%x[-1,7]/%x[0,7]/%x[1,7]
 * U07_3c0:%x[0,7]/%x[1,7]/%x[2,7]
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class CrfTemplateParser {

	private $template;
	private $fstr = '%x'; // feature string
	private $psep = '/';  // part separator
	private $nsep = ':';  // name separator
	private $isep = ',';  // row & column separator
	private $nov  = 'NULL'; // no value

	/**
	 * Constructor: sets
	 *  - row & column separator
	 *  - feature string
	 *  - part separator
	 *  - name separator
	 */
	public function __construct($isep = NULL, $psep = NULL,
								$nsep = NULL, $fstr = NULL) {

		$this->isep = ($isep) ? $isep : $this->isep;
		$this->psep = ($psep) ? $psep : $this->psep;
		$this->nsep = ($nsep) ? $nsep : $this->nsep;
		$this->fstr = ($fstr) ? $fstr : $this->fstr;
	}

	/**
	 * Parse template file
	 *
	 * @param file $file
	 */
	public function parseTemplate($file) {
		$lines = array_map('trim', file($file));
		foreach ($lines as $line) {
			if ($line != '' && !preg_match('/^#/u', $line)) {
				$la = explode($this->nsep, $line);
				if (count($la) > 1) {
					$name  = $la[0];
					$parts = explode($this->psep, $la[1]);
					$feats = array();
					foreach ($parts as $part) {
						$str = str_replace(
							array($this->fstr, '[', ']'),
							array('', '', ''),
							$part
						);
						$feats[] = explode($this->isep, $str);
					}
					$this->template[$name] = $feats;
				}
			}
		}
	}

	/**
	 * Generate features using the template
	 *
	 * @param  array $data
	 * @return array $out
	 */
	public function generateFeatures($data, $glue = NULL) {

		$glue = ($glue) ? $glue : $this->psep;

		$out = array();
		foreach ($data as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				foreach ($this->template as $parts) {
					$tmp = array();
					foreach ($parts as $a) {
						$colID = $a[1];
						$rowID = intval($tokID) + intval($a[0]);
						$tmp[] = (isset($data[$segID][$rowID][$colID]))
								 ? $data[$segID][$rowID][$colID]
								 : $this->nov;
					}
					$out[$segID][$tokID][] = implode($glue, $tmp);
				}
			}
		}
		return $out;
	}

	/**
	 * get template array
	 *
	 * @return array $this->template
	 */
	public function getTemplate() {
		return $this->template;
	}

	/**
	 * Set missing value string
	 *
	 * @param string $str
	 */
	public function setNoValue($str) {
		$this->nov = $str;
	}
}
//======================================================================
// Example Usage
//======================================================================
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('t:d:');

require 'ConllReader.php';

$CR = new ConllReader();
$data = $CR->conllRead($args['d']);

$TP = new CrfTemplateParser();
$TP->parseTemplate($args['t']);

//$template = $TP->getTemplate();
//print_r($template);

$feats = $TP->generateFeatures($data, '+');
$CR->conllWrite($feats);
*/
