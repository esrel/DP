<?php
/**
 * Generic Classes & Objects for Trees (syntactic constituency)
 *
 * requires flat & clean parse trees (use ParseTreeUtilities)
 *
 * @author Evgeny A. Stepanov
 * @e-mail stepanov.evgeny.a@gmail.com
 */

/**
 * Generic Node
 */
 class Node {

 	public $id;       // unique ID
 	public $tag;      // string
 	public $parent;   // string
 	public $children; // array or string for terminals
 	//public $ref;      // reference to other node (for traces)

 	/**
 	 * Initialize object & return it
 	 * @param int    $id
 	 * @param string $tag
 	 * @param string $parent
 	 * @param array  $children
 	 *
 	 * @return object
 	 */
 	public function __construct($id, $tag, $parent = NULL, $children = array()) {

 		$this->id       = $id;
 		$this->tag      = $tag;
 		$this->parent   = $parent;
 		$this->children = $children;
 		//$this->ref      = NULL;

 		return $this;
 	}
 }

 /**
  * Tree Class Methods
  */
 class Tree {

 	protected $root = 'ROOT'; // default root tag
 	protected $tree;
 	//protected $stack;

 	/**
 	 * Gets constituency parse tree string & builds a tree object
 	 *
 	 * @param string $str
 	 */
 	public function __construct($str) {
 		$this->readTree($str);
 	}

	/**
	 * Read parse tree string into Tree Object
	 *
	 * @param string $parse
	 */
	public function readTree($parse) {
		// add root (empty), if not present
		if (!preg_match('/^\(\(/u', $parse)) {
			$parse = '(' . $parse . ')';
		}

		// add spaces for splitting
		$parse = preg_replace('/\(/u', ' ( ', $parse);
		$parse = preg_replace('/\)/u', ' ) ', $parse);
		$parse = preg_replace('/  */u', ' ' , $parse);
		$parse = preg_replace('/^ */u', ''  , $parse);
		$parse = preg_replace('/ *$/u', ''  , $parse);

		// parse tree string to array
		$arr   = preg_split('/\s/u', $parse, -1, PREG_SPLIT_NO_EMPTY);

		// build tree
		$node   = NULL; // current node
		$parent = NULL; // parent  node
		$id     = 0;    // node ID

		for ($i = 0; $i < count($arr);) {
			// root node: '( ('
			if ($arr[$i] == '(' && $arr[$i + 1] == '(') {
				$tag  = $this->root;
				$node = new Node($id, $tag);
				// connect
				$this->tree = $node;
				$i++;
				$id++;
			}
			// non-terminal node: '( TAG ('
			elseif ($arr[$i] == '(' && $arr[$i + 2] == '(') {
				$tag    = $arr[$i + 1];
				$parent = $node; // move down
				$node   = new Node($id, $tag, $parent);
				$parent->children[] = $node;
				$i = $i + 2;
				$id++;
			}
			// terminal node: '( TAG WORD )'
			elseif ($arr[$i] == '(' && $arr[$i + 2] != '(') {
				$tag    = $arr[$i + 1];
				$word   = $arr[$i + 2];
				$parent = $node; // don't move, just add child
				$parent->children[] = new Node($id, $tag, $parent, $word);
				$i = $i + 4;
				$id++;
			}
			// closing
			elseif ($arr[$i] == ')') {
 				// move node UP
 				if ($node->tag != $this->root) {
					$node = $node->parent;
				}
 				$i++;
 			}
 			// shouldn't exist
 			else {
				die('ERROR: Unknown!' . "\n");
			}
		}
	}

 	/* Methods */

 	/**
 	 * Make Tree string
 	 * @param  obj    $node
 	 * @return string $str
 	 */
 	public function writeTree($node = NULL) {
 		if (!$node) {
 			$node = $this->tree;
 		}
 		$str = '';

 		if (!is_array($node->children)) {
			// terminal
 			$str .= '(';
 			$str .= $node->tag;
 			$str .= ' ';
 			$str .= $node->children;
 			$str .= ')';

 			return $str;
 		}
 		else {
			// non-terminal
 			$str .= '(';
 			$str .= $node->tag;
 			$str .= ' ';
 			foreach ($node->children as $n) {
 				$str .= $this->writeTree($n);
 			}
 			$str .= ')';

 			return $str;
 		}
 	}

 	/* Checking Functions */
 	/**
 	 * Checks if node is a terminal node
 	 *
 	 * @param  obj $node
 	 * @return boolean
 	 */
 	protected function isTerminal($node) {
 		if (!is_array($node->children)) {
 			return TRUE;
 		}
 		else {
 			return FALSE;
 		}
 	}

 	/**
 	 * Checks if node is a non-terminal node
 	 *
 	 * @param  obj $node
 	 * @return boolean
 	 */
 	protected function isNonTerminal($node) {
 		if (is_array($node->children)) {
 			return TRUE;
 		}
 		else {
 			return FALSE;
 		}
 	}

 	/**
 	 * Checks if node is a root node
 	 *
 	 * @param  obj $node
 	 * @return boolean
 	 */
 	protected function isRoot($node) {
 		if (!$node->parent || $node->tag == $this->root) {
 			return TRUE;
 		}
 		else {
 			return FALSE;
 		}
 	}

 	/**
 	 * Checks if node has children, i.e. non-terminal
 	 *
 	 * @param  obj $node
 	 * @return boolean
 	 */
 	protected function hasChildren($node) {
 		if (is_array($node->children)) {
 			return TRUE;
 		}
 		else {
 			return FALSE;
 		}
 	}

 	/* Terminal Node Collection Methods */

 	/**
 	 * Collects Terminal nodes into array
 	 *
 	 * @param  obj   $node
 	 * @return array $arr
 	 */
 	protected function getTerminals($node = NULL) {
 		$arr = array();

 		if (!$node) {
 			$node = $this->tree;
 		}

 		if (!is_array($node->children)) {
 			return array($node);
 		}
 		else {
 			foreach ($node->children as $n) {
 				$arr = array_merge($arr, $this->getTerminals($n));
 			}
 			return $arr;
 		}
 	}

 	/**
 	 * Collects terminal node words
 	 *
 	 * @param  obj   $node
 	 * @return array $arr
 	 */
 	protected function getTerminalWords($node = NULL) {
 		$arr = array();

 		if (!$node) {
 			$node = $this->tree;
 		}

 		$nodes = $this->getTerminals($node);
 		foreach ($nodes as $n) {
 			$arr[] = $n->children;
 		}
 		return $arr;
 	}

 	/**
 	 * Collects terminal node tags
 	 *
 	 * @param  obj   $node
 	 * @return array $arr
 	 */
 	protected function getTerminalTags($node = NULL) {
		$arr = array();

 		if (!$node) {
 			$node = $this->tree;
 		}

 		$nodes = $this->getTerminals($node);
 		foreach ($nodes as $n) {
 			$arr[] = $n->tag;
 		}
 		return $arr;
 	}

 	/* Property Getters */

 	/**
 	 * Retrieve parent node
 	 * @param  obj $node
 	 * @return obj
 	 */
 	protected function getParent($node) {
 		return $node->parent;
 	}

 	/**
 	 * Retrieve child nodes
 	 * @param  obj $node
 	 * @return arr
 	 */
 	protected function getChildren($node) {
 		return $node->children;
 	}

 	/**
 	 * Retrieve tag of a node
 	 * @param  obj $node
 	 * @return string
 	 */
 	protected function getTag($node) {
 		return $node->tag;
 	}

 	/* Sibling Getters */

 	/**
 	 * Retrieve siblings array
 	 * @param  obj $node
 	 * @return array
 	 */
 	protected function getSiblings($node) {
		if (isset($node->parent->children)) {
			return $node->parent->children;
		}
		else {
			return NULL;
		}
 	}

 	/* Public Setters & Getters */

 	/**
 	 * Set $this->root
 	 * @param string $str
 	 */
 	public function setRoot($str) {
 		$this->root = $str;
 	}

 	/**
 	 * Get Tree Object
 	 *
 	 * @return $this->tree
 	 */
 	protected function getTree() {
 		return $this->tree;
 	}

 	/**
 	 * Get Tree String
 	 *
 	 * @return string
 	 */
 	public function getTreeStr() {
 		return $this->writeTree();
 	}

 	/**
 	 * Get Tree Words (Tokens)
 	 *
 	 * @return array
 	 */
 	public function getWords() {
 		return $this->getTerminalWords();
 	}

 	/**
 	 * Get Tree Tags (POS Tags)
 	 *
 	 * @return array
 	 */
 	public function getTags() {
 		return $this->getTerminalTags();
 	}
 }

// Test Cases:
/*
// For testing protected methods
class TestTree extends Tree {
	public function test() {
		// get all terminals
		$tnodes = $this->getTerminals();

		// get specific terminal
		$tnode  = $tnodes[6];
		echo '* NODE: ';
		echo $tnode->children . '/'; // and
		echo $tnode->tag . "\n";     // CC

		// tests
		echo '* TESTS:' . "\n";
		var_dump($this->isNonTerminal($tnode)); // FALSE
		var_dump($this->isTerminal($tnode));    // TRUE
		var_dump($this->isRoot($tnode));        // FALSE
		var_dump($this->hasChildren($tnode));   // FALSE

		// get parent
		$pnode = $this->getParent($tnode);
		echo '* PARENT: ' . $pnode->tag . "\n";     // NP

		// get children
		echo '* CHILDREN:' . "\n";
		$children = $this->getChildren($pnode);
		foreach ($children as $n) {
			echo $n->children . '/' . $n->tag . "\n";
		}

		// get siblings
		echo '* SIBLING TAGS: (includes the node itself) ' . "\n";
		$sibs = $this->getSiblings($tnode);
		echo implode(' ', array_map(array($this, 'getTag'), $sibs)) . "\n";

	}
}

$parse = "( (S (NP (NP (JJ Influential) (NNS members)) (PP (IN of) (NP (DT the) (NNP House) (NNPS Ways) (CC and) (NNPS Means) (NNP Committee)))) (VP (VBD introduced) (NP (NP (NN legislation)) (SBAR (WHNP (WDT that)) (S (VP (MD would) (VP (VB restrict) (SBAR (WHADVP (WRB how)) (S (NP (DT the) (JJ new) (JJ savings-and-loan) (NN bailout) (NN agency)) (VP (MD can) (VP (VB raise) (NP (NN capital))))))))))) (, ,) (S (VP (VBG creating) (NP (NP (DT another) (JJ potential) (NN obstacle)) (PP (TO to) (NP (NP (NP (DT the) (NN government) (POS 's)) (NN sale)) (PP (IN of) (NP (JJ sick) (NNS thrifts))))))))) (. .)) )";
$tree  = new Tree($parse);

// Print parse tree: same as input + ROOT
echo $tree->getTreeStr() . "\n";
// Get terminal words
echo json_encode($tree->getWords()) . "\n";
// Get terminal tags
echo json_encode($tree->getTags()) . "\n";

// protected method tests
$TT = new TestTree($parse);
$TT->test();

*/
