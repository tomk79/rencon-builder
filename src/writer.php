<?php
namespace tomk79\renconBuilder;

class writer {

	/** $utils */
	private $utils;

	/** $renconBuilderJson */
	private $renconBuilderJson;

	/** version */
	private $version;

	/** appname */
	private $appname;

	/** $require_files */
	private $require_files;

	/** $resource_files */
	private $resource_files;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $renconBuilderJson ){
		$this->utils = $utils;
		$this->renconBuilderJson = $renconBuilderJson;
		$this->require_files = array();
		$this->resource_files = array();
	}


	/**
	 * バージョン番号をセット
	 */
	public function set_version($version){
		$this->version = $version;
	}

	/**
	 * アプリケーション名をセット
	 */
	public function set_appname($appname){
		$this->appname = $appname;
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
		array_push( $this->resource_files, $realpath );
		return true;
	}


	/**
	 * 保存する
	 */
	public function save(){
		$framework_files = new framework_files();

		$rtn = '';

		$src_function_resource = '';
		foreach( $this->resource_files as $file ){
			$bin = file_get_contents($file);
			$src_function_resource .= ''.var_export($file, true).' => '.var_export(base64_encode( $bin ), true).','."\n";
		}


		$src_route = '';
		if( $this->renconBuilderJson->route ){
			foreach( $this->renconBuilderJson->route as $route => $func_name ){
				$src_route .= ''.var_export($route, true).' => (object) array('."\n";
				$src_route .= '	"title" => '.var_export($func_name->title, true).','."\n";
				if( is_file( $func_name->page ) ){
					$src_route .= '	"page" => function(){ ?'.'>'."\n";
					$src_route .= file_get_contents( $func_name->page );
					$src_route .= '<'.'?php return; },'."\n";
				}else{
					$src_route .= '	"page" => '.var_export($func_name->page, true).','."\n";
				}
				$src_route .= '),'."\n";
			}

		}


		$framework = $framework_files->get_framework();
		$framework = str_replace('<!-- appname -->', $this->appname.' v'.$this->version, $framework);
		$framework = str_replace('/* router */', $src_route, $framework);
		$framework = str_replace('/* function resource() */', $src_function_resource, $framework);

		$rtn .= $framework;

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

		return $this->utils->fs()->save_file( $this->renconBuilderJson->dist, $rtn );
	}

}
