<?php
namespace tomk79\renconBuilder;

class writer {

	/** $utils */
	private $utils;

	/** $path_dist */
	private $path_dist;

	/** $require_files */
	private $require_files;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $path_dist ){
		$this->utils = $utils;
		$this->path_dist = $path_dist;
		$this->require_files = array();
	}


	/**
	 * スキャン済みのパッケージか調べる
	 */
	public function is_scaned( $package_name ){
		if( !strlen($package_name) ){
			$package_name = '';
		}
		if( array_key_exists( $package_name, $this->require_files ) ){
			return true;
		}
		return false;
	}


	/**
	 * PHPコードを加える
	 */
	public function require( $package_name, $realpath ){
		if( !strlen($package_name) ){
			$package_name = '';
		}
		if( !array_key_exists($package_name, $this->require_files) ){
			$this->require_files[$package_name] = array();
		}
		array_push( $this->require_files[$package_name], $realpath );
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

		foreach( $this->require_files as $package_name => $files ){
			foreach( $files as $file ){
				if( !preg_match('/\.php$/i', $file) ){
					continue;
				}
				$bin = file_get_contents($file);
				$bin = trim( $bin );
				$bin = preg_replace( '/\?\>$/si', '', $bin )."\n".'?'.'>';
				$rtn .= $bin;
			}
		}

		return $this->utils->fs()->save_file( $this->path_dist, $rtn );
	}

}
