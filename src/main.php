<?php
namespace tomk79\renconBuilder;

class main {

	/** version number */
	private $version = '0.0.1-alpha.1+dev';

	/** $utils */
	private $utils;

	/** $utils */
	private $writer;

	/**
	 * Constructor
	 */
	public function __construct(){
		$this->utils = new utils();
	}


	/**
	 * ビルド処理の開始
	 */
	public function start(){

		echo "\n";
		echo '-------------------'."\n";
		echo 'rencon-builder v'.$this->version."\n";
		echo "\n";


		$composerJson = $this->utils->load_json( './composer.json' );
		if( !$composerJson || !is_object($composerJson) ){
			echo '[ERROR] `composer.json` is not available.'."\n";
			return false;
		}
		$composerJson = $this->normalize_composer_json( $composerJson );


		$renconBuilderJson = $this->utils->load_json( './rencon-builder.json' );
		if( !$renconBuilderJson || !is_object($renconBuilderJson) ){
			echo '[ERROR] `rencon-builder.json` is not available.'."\n";
			return false;
		}
		$renconBuilderJson = $this->normalize_rencon_builder_json( $renconBuilderJson );

		if( !$renconBuilderJson->dist ){
			$renconBuilderJson->dist = $composerJson->name;
			$renconBuilderJson->dist = preg_replace('/^.*\/(.*?)$/', '$1', $renconBuilderJson->dist).'.phar';
		}


		$this->writer = new writer($this->utils, $renconBuilderJson->dist);
		$this->writer->save();

		echo 'done.'."\n";

		return;
	}

	/**
	 * composer.json の内容を正規化する
	 */
	private function normalize_composer_json( $json ){
		if( !property_exists( $json, 'name' ) ){
			$json->name = 'vendor/application';
		}
		return $json;
	}

	/**
	 * rencon-builder.json の内容を正規化する
	 */
	private function normalize_rencon_builder_json( $json ){
		if( !property_exists( $json, 'dist' ) ){
			$json->dist = false;
		}
		return $json;
	}

}
