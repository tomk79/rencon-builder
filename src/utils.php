<?php
namespace tomk79\renconBuilder;

class utils {

	/** $fs */
	private $fs;

	/** $req */
	private $req;

	/**
	 * Constructor
	 */
	public function __construct(){
		$this->fs = new filesystem();
		$this->req = new request();
	}

	/**
	 * $fs
	 */
	public function fs(){
		return $this->fs;
	}

	/**
	 * $req
	 */
	public function req(){
		return $this->req;
	}

	/**
	 * JSONを読み取る
	 */
	public function load_json( $realpath_json ){
		if( !$this->fs->is_file( $realpath_json ) ){
			return false;
		}
		$bin = $this->fs->read_file( $realpath_json );
		if( !is_string( $bin ) ){
			return false;
		}
		$rtn = false;
		try{
			$rtn = json_decode($bin);
		}catch(Exception $e){
			return false;
		}
		return $rtn;
	}

}
