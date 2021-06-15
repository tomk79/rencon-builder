<?php
namespace tomk79\renconBuilder;

class writer {

	/** $utils */
	private $utils;

	/** $path_dist */
	private $path_dist;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $path_dist ){
		$this->utils = $utils;
		$this->path_dist = $path_dist;
	}


	/**
	 * PHPコードを加える
	 */
	public function require( $realpath ){
		return true;
	}


	/**
	 * リソースファイルを加える
	 */
	public function resource( $realpath ){
		return true;
	}

	/**
	 * 保存する
	 */
	public function save(){
		$rtn = '';
		$rtn .= '<'.'?php'."\n";
		$rtn .= '?'.'>';
		return $this->utils->fs()->save_file( $this->path_dist, $rtn );
	}

}
