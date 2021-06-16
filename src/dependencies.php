<?php
namespace tomk79\renconBuilder;

class dependencies {

	/** $utils */
	private $utils;

	/** $writer */
	private $writer;

	/** $package_name */
	private $package_name;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $writer, $package_name = null ){
		$this->utils = $utils;
		$this->writer = $writer;
		$this->package_name = $package_name;

		if( strlen($this->package_name) ){
			echo ' - '.$this->package_name."\n";
		}
	}


	/**
	 * 走査を始める
	 */
	public function scan(){

		if( $this->writer->is_scaned($this->package_name) ){
			// すでにスキャン済みのパッケージならスキップ
			return;
		}
		if( strtolower($this->package_name) == 'php' ){
			// PHPのバージョン制約はスキップ
			return;
		}
		if( preg_match('/^ext\-[a-z0-9\_\-]*$/i', $this->package_name) ){
			// PHP拡張のバージョン制約はスキップ
			return;
		}

		$path_basedir = './';
		if( strlen($this->package_name) ){
			$path_basedir = './vendor/'.$this->package_name.'/';
		}

		$json = $this->load_composer_json();
		// var_dump($json);


		// --------------------------------------
		// autoload をスキャン
		if( !property_exists($json, 'autoload') ){
			$json->autoload = new \stdClass();
		}

		if( property_exists( $json->autoload, 'psr-4' ) ){
			foreach( $json->autoload->{'psr-4'} as $namespace_prefix => $ary_dirs ){
				foreach( $ary_dirs as $dirname ){
					$this->require_php_in_directory( $dirname );
				}
			}
		}
		if( property_exists( $json->autoload, 'files' ) ){
			foreach( $json->autoload->{'files'} as $path_php_file ){
				$this->writer->require( $this->package_name, $path_basedir.$path_php_file );
			}
		}


		// --------------------------------------
		// require をスキャン
		if( !property_exists($json, 'require') ){
			$json->require = new \stdClass();
		}
		foreach( $json->require as $package_name => $package_version ){
			// var_dump($package_name);
			$dependencies = new dependencies( $this->utils, $this->writer, $package_name );
			$dependencies->scan();
		}

		return;
	}


	/**
	 * ディレクトリ内のPHPを再帰的にロードする
	 */
	private function require_php_in_directory( $rootdir ){
		$path_basedir = './';
		if( strlen($this->package_name) ){
			$path_basedir = './vendor/'.$this->package_name.'/';
		}

		$rootdir = preg_replace( '/\/+$/', '', $rootdir ).'/';

		$path = $path_basedir.$rootdir;
		$ls = $this->utils->fs()->ls( $path );
		foreach( $ls as $basename ){
			if( is_file( $path.$basename ) ){
				$this->writer->require( $this->package_name, $path.$basename );
			}elseif( is_dir( $path.$basename.'/' ) ){
				$this->require_php_in_directory( $rootdir.$basename );
			}
		}

		return;
	}


	/**
	 * composer.json を読み取る
	 */
	private function load_composer_json(){
		$path_json = './composer.json';
		if( strlen($this->package_name) ){
			$path_json = './vendor/'.$this->package_name.'/composer.json';
		}

		$json_str = $this->utils->fs()->read_file( $path_json );
		$json = json_decode( $json_str );

		return $json;
	}

}
