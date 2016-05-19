<?php
/**
 * Class for using Brown Clusters
 * @author estepanov
 *
 * Requires Cluster file
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class BrownClusters {

	private $map = array();

	/**
	 * Initiates class by constructing mapping
	 * @param file $file	clusters file
	 * @param int  $prefix 	size of cluster name
	 */
	function __construct($file, $prefix) {
		$file_arr = array_map('trim', file($file));

		foreach ($file_arr as $line) {
			$l = trim($line);

			if ($l != '') {
				$la = explode("\t", $l);
				$vl = str_pad($la[0], $prefix, '0', STR_PAD_RIGHT);
				$this->map[$la[1]] = 'BC-' . bindec($vl);
			}
		}
	}

	/**
	 * Get cluster for token
	 * @param  string $str
	 * @return string|NULL
	 */
	public function getBrownCluster($str) {
		if (isset($this->map[$str])) {
			return $this->map[$str];
		}
		else {
			return NULL;
		}
	}
}
