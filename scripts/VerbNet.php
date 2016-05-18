<?php
/**
 * Class to tag words with VerbNet classes
 * 
 * Input: VerbNet.json
 */
class VerbNet {
	
	private $classes;
	private $map;
	
	/**
	 * Json 2 array
	 * create verb 2 class mapping
	 * @param file $json
	 */
	function __construct($json) {
		$str = trim(file_get_contents($json));
		$this->classes = json_decode($str, TRUE);
		
		foreach ($this->classes as $class => $members) {
			foreach ($members as $verb) {
				$this->map[$verb][] = $class;
			}
		}
	}
	
	/**
	 * Returns array of classes for the verb
	 * @param  string $str
	 * @return array or boolean
	 */
	public function getVerbClass($str) {
		$str = strtolower($str);
		// for phrasal verbs
		$str = str_replace(' ', '_', $str);
		if (isset($this->map[$str])) {
			return $this->map[$str];
		}
		else {
			return NULL;
		}
	}
	
	/**
	 * Returns array of verbs for the class
	 * @param  string $str
	 * @return array
	 */
	public function getClass($str) {
		return $this->classes[$str];
	}
}

