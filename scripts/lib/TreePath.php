<?php
/***
 * Syntactic Tree Path Methods
 *
 * - inherits constructor from Tree
 * - creates IOB-chain
 * - creates tag path between two nodes
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require_once 'Tree.php';

class TreePath extends Tree {

	private $u = '/';  // up   path glue
	private $d = '\\'; // down path glue

 	/**
 	 * Get path from root node
 	 * @param  obj   $node
 	 * @return array $arr
 	 */
 	protected function getRootPath($node) {
 		$arr = array();
 		while ($node->tag != $this->root) {
 			$arr[] = $node;
 			$node  = $node->parent;
 		}
 		return $arr;
 	}

 	/**
 	 * Get path between 2 nodes
 	 *
 	 * @param  obj   $nodef    from node
 	 * @param  obj   $nodet    to   node
 	 * @return array $path
 	 */
 	protected function getPath($nodef, $nodet) {

		// get root paths & reverse so that 0 => ROOT
		$pathf = array_reverse($this->getRootPath($nodef));
		$patht = array_reverse($this->getRootPath($nodet));

		// follow both paths till paths differ
		$i = 0;
		$cnode = $this->tree; // top common node
		$pathu = array(); // path up   from the from-node
		$pathd = array(); // path down to   the to-node
		while ($pathf[$i] == $patht[$i]) {
			$cnode = $pathf[$i]; // common node
			$i++;
			$pathu = array_slice($pathf, $i); // remaining path up
			$pathd = array_slice($patht, $i); // remaining path down
		}

		$path = array('C' => $cnode,
					  'U' => array_reverse($pathu),
					  'D' => $pathd
					  );
		return $path;
	}

	/**
	 * Get Lowest Common Parent
	 *
	 * @param  obj $node1
	 * @param  obj $node2
	 * @return obj
	 */
	protected function getCommonParent($node1, $node2) {
		$path = $this->getPath($node1, $node2);

		return $path['C'];
	}

	/**
 	 * Get path between 2 nodes
 	 *
 	 * @param  obj    $nodef    from node
 	 * @param  obj    $nodet    to   node
 	 * @return string $str
 	 */
 	public function getTagPath($nodef, $nodet) {

		$path = $this->getPath($nodef, $nodet);

		$pathu = array_map(array($this, 'getTag'), $path['U']);
		$pathd = array_map(array($this, 'getTag'), $path['D']);
		$str  = '';
		$str .= implode($this->u, $pathu);
		$str .= $this->u . $path['C']->tag . $this->d;
		$str .= implode($this->d, $pathd);

		return $str;
	}

 	/**
 	 * Get path from root node as IOB
 	 *  -B if 1st token of a span
 	 *  -I otherwise
 	 *
 	 * @param  obj    $node
 	 * @return string $arr
 	 */
	public function getIobChain($node) {
		// get path
		$path = $this->getRootPath($node);

		$arr = array();
		$iob = array();
		foreach ($path as $node) {
			$sib = $this->getSiblings($node);
			$iob[] = array_search($node, $sib);
			$arr[] = $node->tag;
		}
		// tag is parent's tag, thus remove last key
		$iob = array_slice($iob, 0, -1);
		// remove POS-tag
		$arr = array_slice($arr, 1);
		// add 'B' as long as key is 0, 'I' otherwise (for the rest)
		$flag = FALSE;
		foreach ($iob as $k => $key) {
			if (!$flag && $key == 0) {
				$arr[$k] = 'B-' . $arr[$k];
			}
			else {
				$flag = TRUE;
				$arr[$k] = 'I-' . $arr[$k];
			}
		}
		return implode($this->u, array_reverse($arr));
	}

	/**
	 * Set up and down glues for paths
	 *
	 * @param string $up
	 * @param string $down
	 */
	public function setPathIndicators($up, $down) {
		$this->u = $up;
		$this->d = $down;
	}

}
//======================================================================
// Example Usage
//======================================================================
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Test class for protected methods
class TestTreePath extends TreePath {
	public function test() {
		// get all terminals
		$tnodes = $this->getTerminals();
		$node1  = $tnodes[6];
		$node2  = $tnodes[11];

		echo '* NODE: ';
		echo $node1->children . '/'; // and
		echo $node1->tag . "\n";     // CC

		// iob chain
		echo '* IOB-CHAIN: ';
		echo $this->getIobChain($node1) . "\n";
		// path
		echo '* TAG PATH: ';
		echo $this->getTagPath($node1, $node2) . "\n";
		// common parent
		echo '* LOWEST COMMON PARENT: ';
		echo $this->getCommonParent($node1, $node2)->tag . "\n";

		// all IOB
		echo '* IOB-CHAINS: ' . "\n";
		foreach ($tnodes as $tn) {
			echo $tn->children . "\t";
			echo $tn->tag . "\t";
			echo $this->getIobChain($tn) . "\n";
		}

	}
}

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

$TT     = new TestTreePath($parse);
$TT->test();
*/
