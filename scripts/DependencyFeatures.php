<?php
/**
 * Class to generate dependency features from sentence dependency array
 *
 * Dependency Chains = paths from ROOT till the token
 * Similar to IOB-chain, but uses dependency relations
 *
 * Input:
 * 	array( $tokID => array(
 * 					'token' => $token,
 * 					'head'  => $headTokenID,
 * 					'type'  => $function
 *             		),
 *        array(), ...,
 *      );
 *
 * Note: Punctuation is not part of dependency!
 */
class DependencyFeatures {

	private $sep    = '/';
	private $root   = 'root'; // for Main Verb
	private $rootID = -1;
	private $nov    = '_';
	private $null   = 'NULL';

	// column ids
	private $sID = 0; // token string column
	private $hID = 1; // head ID column
	private $fID = 2; // type/function column

	// functions
	private $subj = array('nsubj', 'nsubjpass');
	private $iobj = array('iobj');
	private $dobj = array('dobj');

	/**
	 * Constructor: if provided, sets
	 *   token string column index
	 *   head ID column index
	 *   type/function column index
	 *
	 * @param array $ids
	 */
	public function __construct($ids = NULL) {
		if ($ids) {
			$this->sID = $ids[0];
			$this->hID = $ids[1];
			$this->fID = $ids[2];
		}
	}

	/**
	 * Generate Dependency Chains
	 * @param  array $dep_arr    array of dependency relations
	 * @return array $dep_chains array of dependency chains
	 */
	public function mkDependencyChains($dep_arr) {
		$dep_chains = array();

		if (empty($dep_arr)) {
			return $dep_chains;
		}

		$val_arr = array_column($dep_arr, $this->fID);

		foreach ($dep_arr as $tokID => $tok) {
			$path = $this->getRootPath($tokID, $dep_arr);
			if (empty($path)) {
				$dep_chains[$tokID] = $this->nov;
			}
			else {
				$dep_chains[$tokID] = implode($this->sep,
								$this->mapRootPath($path, $val_arr));
			}
		}

		return $dep_chains;
	}

	/**
	 * Generate Mapped Chains
	 * @param  array $arr        array of dependency relations
	 * @return array $map_chains array of mapped chains
	 */
	public function mkMappedChains($dep_arr, $val_arr) {
		$map_chains = array();

		if (empty($dep_arr)) {
			return $map_chains;
		}

		foreach ($dep_arr as $tokID => $tok) {
			$path = $this->getRootPath($tokID, $dep_arr);
			if (empty($path)) {
				$map_chains[$tokID] = $this->nov;
			}
			else {
				$map_chains[$tokID] = implode($this->sep,
								$this->mapRootPath($path, $val_arr));
			}
		}

		return $map_chains;
	}

	/**
	 * Creates a path of ids from the root
	 *
	 * @param  int   $id
	 * @param  array $arr
	 * @return array
	 */
	private function getRootPath($id, $dep_arr) {
		$path_arr = array();
		$pos = $id;

		if ($dep_arr[$pos][$this->fID] == $this->nov) {
			return $path_arr;
		}

		while (!$this->isRoot($dep_arr[$pos][$this->fID])) {

			$path_arr[] = $pos;
			if (isset($dep_arr[$dep_arr[$pos][$this->hID]])) {
				$pos = $dep_arr[$pos][$this->hID];
			}
			else {
				return array_reverse($path_arr);
			}
		}
		$path_arr[] = $pos; // add root
		return array_values(array_reverse($path_arr));
	}

	/**
	 * Maps Dependency Path to provided array (using array keys)
	 *
	 * @param  array $path     root path array
	 * @param  array $arr      array of values for mapping
	 * @return array $map_arr  array of mapped path
	 */
	private function mapRootPath($path, $arr) {
		$map_arr = array();

		if (empty($path) || empty($arr)) {
			return $map_arr;
		}

		foreach ($path as $tokID) {
			$map_arr[] = $arr[$tokID];
		}

		return $map_arr;
	}

	/**
	 * Tag dependency array with root verb information
	 *
	 * @param  array  $dep_arr
	 * @param  string $mode
	 * @return array  $out_arr
	 */
	public function tagRoot($dep_arr, $mode = 'bool') {
		$out_arr = array();
		if (empty($dep_arr)) {
			return $out_arr;
		}

		foreach ($dep_arr as $tokID => $tok) {
			$val = $this->isRoot($tok[$this->fID]);

			if ($mode == 'bool') {
				$out_arr[$tokID] = ($val) ? 1 : 0;
			}
			elseif ($mode == 'string') {
				$out_arr[$tokID] = ($val)
									? strtolower($tok[$this->sID])
									: $this->nov;
			}
			else {
				$out_arr[$tokID] = ($val) ? 1 : 0;
			}
		}
		return $out_arr;
	}

	// Dependency relation type/Function methods

	/**
	 * Get token by dependency function
	 *
	 * @param  array  $dep_arr
	 * @param  string $func
	 * @param  bool   $id      whether to output ids
	 * @return string $str
	 */
	private function getRoleToken($dep_arr, $func, $id = FALSE) {
		$str = $this->null;
		if (empty($dep_arr)) {
			return $str;
		}

		// generate array of chains for the provided function
		$farr = array();
		if ($func == $this->root) {
			$farr[] = $this->root;
		}
		else {
			foreach ($func as $s) {
				$farr[] = $this->root . $this->sep . $s;
			}
		}

		$dep_chains = $this->mkDependencyChains($dep_arr);

		foreach ($dep_chains as $tokID => $chain) {
			if (in_array($chain, $farr)) {
				if ($id) {
					$str = $tokID;
				}
				else {
					$str = $dep_arr[$tokID][$this->sID];
				}
			}
		}

		return $str;
	}

	/**
	 * Returns main verb of a dependency parse tree = root
	 *
	 * @param  array  $dep_arr dependency
	 * @param  bool   $id      whether to output ids
	 * @return string
	 */
	public function getMainVerb($dep_arr, $id = FALSE) {
		return $this->getRoleToken($dep_arr, $this->root, $id);
	}

	/**
	 * Returns subject of a dependency parse tree = nsubj
	 * @param  array  $dep_arr dependency
	 * @param  bool   $id      whether to output ids
	 * @return string
	 */
	public function getSubject($dep_arr, $id = FALSE) {
		return $this->getRoleToken($dep_arr, $this->subj, $id);
	}

	/**
	 * Returns direct object of a dependency parse tree = dobj
	 * @param  array  $dep_arr dependency array
	 * @param  bool   $id      whether to output ids
	 * @return string
	 */
	public function getDObject($dep_arr, $id = FALSE) {
		return $this->getRoleToken($dep_arr, $this->dobj, $id);
	}

	/**
	 * Returns indirect object of a dependency parse tree = iobj
	 *
	 * @param  array  $dep_arr dependency array
	 * @param  bool   $id      whether to output ids
	 * @return string
	 */
	public function getIObject($dep_arr, $id = FALSE) {
		return $this->getRoleToken($dep_arr, $this->iobj, $id);
	}

	/**
	 * Checks if token's function/type is root
	 *
	 * @param  string $str
	 * @return boolean
	 */
	private function isRoot($str) {
		if ($str == $this->root) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
}

// Test Cases
/*
$DEP = new DependencyFeatures();

$dep_arr = array(
	 0 => array('Another', 1, 'det'),
	 1 => array('$', 6, 'nsubjpass'),
	 2 => array('30', 3, 'number'),
	 3 => array('billion', 1, 'num'),
	 4 => array('would', 6, 'aux'),
	 5 => array('be', 6, 'auxpass'),
	 6 => array('raised', -1, 'root'),
	 7 => array('through', 6, 'prep'),
	 8 => array('Treasury', 9, 'nn'),
	 9 => array('bonds', 7, 'pobj'),
	10 => array(',', '_', '_'),
	11 => array('which', 12, 'nsubj'),
	12 => array('pay', 9, 'rcmod'),
	13 => array('lower', 15, 'amod'),
	14 => array('interest', 15, 'nn'),
	15 => array('rates', 12, 'dobj'),
	16 => array('.', '_', '_'),
);
*/
//print_r($DEP->tagRoot($dep_arr));
//print_r($DEP->mkDependencyChains($dep_arr));
//print_r($DEP->mkMappedChains($dep_arr, array_column($dep_arr, 0)));
//echo $DEP->getMainVerb($dep_arr) . "\n";
//echo $DEP->getMainVerb($dep_arr, TRUE) . "\n";
//echo $DEP->getSubject($dep_arr) . "\n";
//echo $DEP->getSubject($dep_arr, TRUE) . "\n";
//echo $DEP->getDObject($dep_arr) . "\n";
//echo $DEP->getDObject($dep_arr, TRUE) . "\n";
//echo $DEP->getIObject($dep_arr) . "\n";
//echo $DEP->getIObject($dep_arr, TRUE) . "\n";
