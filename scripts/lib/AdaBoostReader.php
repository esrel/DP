<?php
/***
 * AdaBoost (icsiboost/boostexter) output reader class
 *
 * TODO:
 *  1. integrate expert definition for names file (icsiboost)
 *  2. validation for data & names
 *  3. ngram, sgram, fgram features
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL License v. 3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class AdaBoostReader {

	private $classes;   // classes
	private $ref;       // references
	private $hyp;       // hypotheses
	private $max;       // scores
	private $sep = ','; // field separator

	private $ftypes;    // feature types (by name)
	private $fdefs;     // feature definitions
	private $feats;     // feature values
	private $fmax = 0;   // maximum number of features per type
	// feature types
	private $types = array('ignore', 'text', 'continuous');
	private $nulls = array(0, '0', 'NULL', 'NONE');


	/**
	 * Parse names file
	 *
	 * @param file $file
	 */
	public function parseNames($file) {
		$arr = array_map('trim', file($file));
		// get classes
		$cl  = substr($arr[0], 0, -1); // class line
		$this->classes = array_map('trim', explode($this->sep, $cl));
		// parse features
		$this->parseFeatures(array_slice($arr, 1));

	}

	/**
	 * Parse feature lines in names file
	 *
	 * @param array $arr
	 */
	private function parseFeatures($arr) {
		foreach ($arr as $line) {
			list($name, $type) = $this->parseFeature($line);
			$this->ftypes[$name] = $type;
			$this->feats[$name]  = ($type == 'text') ? array() : TRUE;
			$this->fdefs[]       = array($name, $type);
		}
	}

	/**
	 * parse feature line in AdaBoost names file
	 *
	 *  e.g.: a1_a2_vn_m: continuous.
	 *
	 * @param  string $str
	 * @return array  $arr
	 */
	private function parseFeature($str) {
		$str = substr($str, 0, -1); // remove period
		$arr = array_map('trim', explode(':', $str));
		return $arr;
	}

	/**
	 * Parse data file into array
	 *
	 * @param  file $file
	 * @return array $data+$labels
	 */
	public function parseData($file, $lex = FALSE) {
		$data   = array();
		$labels = array();
		$lines = array_map('trim', file($file));
		foreach ($lines as $line) {
			if ($line != '') {
				$str = substr($line, 0, -1); // remove period
				$la  = array_map('trim', explode(',', $str));
				$fa  = array_slice($la, 0, -1);  // remove label
				$tmp = array();
				foreach ($fa as $k => $f) {
					if ($f != '') {
						list($name, $type) = $this->fdefs[$k];
						if ($type == 'text') {
							$vals = preg_split('/\s/u', $f,
											   -1, PREG_SPLIT_NO_EMPTY);
							$tmp[$name] = $vals;
							if ($lex) { // learn features
								$this->feats[$name] = array_merge($this->feats[$name], $vals);
							}
						}
						else {
							$tmp[$name] = $f;
						}
					}
				}
				$data[]   = $tmp;
				$labels[] = $la[count($la) - 1];
			}
		}

		// create lexicon arrays in $this->feats
		if ($lex) {
			$this->makeLex();
		}

		return array($data, $labels);
	}

	/**
	 * Make lexions in $this->feats
	 */
	private function makeLex() {
		foreach ($this->feats as $name => $e) {
			if (is_array($e)) {
				$a = array_unique($e);
				sort($a);
				$num = round(count($a), -strlen(count($a)));
				$this->fmax = ($num > $this->fmax) ? $num : $this->fmax;
				$this->feats[$name] = array_flip($a);
			}
		}
	}

	/**
	 * Parse output file
	 *
	 * @param file $file
	 */
	public function parseOutput($file) {
		$arr = array_map('trim', file($file));
		$row_arr = array();
		foreach ($arr as $ls) {
			if ($ls != '') {
				$la = preg_split('/\s/u', $ls, -1, PREG_SPLIT_NO_EMPTY);
				$half   = count($la) / 2;
				$chunks = array_chunk($la, $half);
				// get references
				$ref = array_keys(array_filter($chunks[0]));
				if (!empty($ref)) {
					$this->ref[] = $ref[0];
				}
				// get hypotheses
				$max = max($chunks[1]);
				$hyp = array_search($max, $chunks[1]);
				$this->hyp[] = $hyp;
				$this->max[] = $max;
			}
		}
	}

	/**
	 * Process output & get class decisions
	 *
	 * @param  file  $out
	 * @param  file  $names
	 * @return array $dec_arr
	 */
	public function getDecisions($out, $names) {
		$this->parseNames($names);
		$this->parseOutput($out);
		$dec_arr = array();
		foreach ($this->hyp as $hyp) {
			$dec_arr[] = $this->classes[$hyp];
		}
		return $dec_arr;
	}

	/**
	 * Get references array
	 *
	 * @return array
	 */
	public function getReferences() {
		$ref_arr = array();
		foreach ($this->ref as $ref) {
			$ref_arr[] = $this->classes[$ref];
		}
		return $ref_arr;
	}

	/**
	 * Get classes array
	 *
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}

	/**
	 * Get features array
	 *
	 * @return array
	 */
	public function getFeatureTypes() {
		return $this->ftypes;
	}

	/**
	 * Get feature lexicons
	 *
	 * @return array
	 */
	public function getFeatures() {
		return $this->feats;
	}

	public function getMax() {
		return $this->fmax;
	}

	/**
	 * Transform data set to sparse vector format
	 *
	 * @param  array $data
	 * @param  array $labels
	 * @return array $vectors
	 */
	public function vectorize($data, $labels) {
		if (count($data) != count($labels)) {
			return FALSE;
		}

		// vectorize labels
		$lout = array();
		$classes = array_flip($this->classes);
		foreach ($labels as $l) {
			$lout[] = $classes[$l] + 1;
		}

		// vectorize data
		$vids = array_flip(array_keys($this->ftypes));
		$dout = array();
		foreach ($data as $doc) {
			$fout = array();
			foreach ($doc as $name => $f) {
				if ($this->ftypes[$name] == 'text') {
					foreach ($f as $e) {
						if (isset($this->feats[$name][$e])) {
							$ind = $this->fmax * $vids[$name]
							     + $this->feats[$name][$e] + 1;
							$fout[$ind] = 1;
						}

					}
				}
				elseif ($this->ftypes[$name] == 'continuous') {
					$ind = $this->fmax * $vids[$name] + 1;
					if (!in_array($f, $this->nulls)) {
						$fout[$ind] = $f;
					}
				}
			}
			ksort($fout);
			$dout[] = $fout;
		}
		return array($dout, $lout);
	}

	/**
	 * Convert icsiboost file format to svmlight/libsvm sparse vectors
	 *
	 * @param file $names names file
	 * @param file $data  data  file
	 * @param file $dev   dev   file
	 * @param file $test  test  file
	 */
	public function adaBoost2svm($names, $data, $dev = NULL,
	                             $test = NULL, $ext = '.svm') {

		$this->parseNames($names);

		foreach (array($data, $dev, $test) as $d) {
			$learn = ($d == $data) ? TRUE : FALSE;
			if ($d) {
				list($feats, $lbls) = $this->parseData($d, $learn);
				list($vects, $lbln) = $this->vectorize($feats, $lbls);
				$fh = fopen($d.$ext, 'w');
				foreach ($vects as $k => $v) {
					$a = array($lbln[$k]);
					$a = array_merge($a, $this->joinkv($v));
					fwrite($fh, implode(' ', $a) . "\n");
				}
				fclose($fh);
			}
		}
	}

	/**
	 * Join key & value in an array
	 *
	 * @param  array $arr
	 * @return array $out
	 */
	private function joinkv($arr) {
		$out = array();
		foreach ($arr as $k => $v) {
			$out[] = $k . ':' . $v;
		}
		return $out;
	}

}
//======================================================================
// Example Usage
//======================================================================
/*
    -n names  file
    -o output file
    -d data   file
    -t test   file (for SVM)
    -e dev    file (for SVM)
*/
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('n:o:d:e:t');

$AB = new AdaBoostReader();

$AB->parseNames($args['n']);
//print_r($AB->getFeatureTypes());
//print_r($AB->getDecisions($args['o'], $args['n']));

list($data, $lbl) = $AB->parseData($args['d'], TRUE);
//print_r($AB->getFeatures());
echo $AB->getMax() . "\n";
list($vec, $lvec) = $AB->vectorize($data, $lbl);
//print_r($lbl);
//print_r($data);
echo json_encode($lvec) . "\n";
print_r($vec);

//$AB->adaBoost2svm($args['n'], $args['d'], $args['e'], $args['t']);
*/
