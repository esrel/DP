<?php
/**
 * Class for using Brown Clusters
 * @author estepanov
 *
 * Requires Cluster file
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
				$this->map[$la[1]] = 'BC-' . bindec(str_pad($la[0], $prefix, '0', STR_PAD_RIGHT));
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