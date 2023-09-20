<?php
namespace tomk79\renconBuilder;

class main {

	/** version number */
	private $version = '0.2.0';

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
		echo '--------------------------------------'."\n";
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

		$version = '--.--.--';
		if( $renconBuilderJson->version ){
			$version = trim( $renconBuilderJson->version );
		}

		$this->writer = new writer($this->utils, $renconBuilderJson);
		$this->writer->set_version( $version );
		$app_name = $renconBuilderJson->name;
		if( !strlen($app_name ?? '') ){
			$app_name = $composerJson->name;
		}
		$this->writer->set_app_name( $app_name );
		$app_id = $renconBuilderJson->app_id;
		if( !strlen($app_id ?? '') ){
			$app_id = preg_replace('/^.*\/(.*?)$/', '$1', $composerJson->name ?? '');
		}
		$this->writer->set_app_id( $app_id );



		echo ''."\n";
		echo '--------------------------------------'."\n";
		echo 'scaning dependencies'."\n";
		$dependencies = new dependencies($this->utils, $this->writer);
		$dependencies->scan();

		echo ''."\n";
		echo ''."\n";

		echo '--------------------------------------'."\n";
		echo 'scaning resources'."\n";
		$resources = new resources($this->utils, $this->writer, $renconBuilderJson);
		$resources->scan();


		echo ''."\n";
		echo ''."\n";
		echo ''."\n";

		echo 'saving files...';
		$this->writer->save();
		echo 'done.'."\n";

		echo ''."\n";
		echo ''."\n";
		echo ''."\n";
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
		if( !property_exists( $json, 'name' ) ){
			$json->name = false;
		}
		if( !property_exists( $json, 'app_id' ) ){
			$json->app_id = false;
		}
		if( !property_exists( $json, 'version' ) ){
			$json->version = false;
		}
		if( !property_exists( $json, 'dist' ) ){
			$json->dist = false;
		}
		if( !property_exists( $json, 'resources' ) ){
			$json->resources = false;
		}
		if( !property_exists( $json, 'route' ) ){
			$json->route = false;
		}
		if( !property_exists( $json, 'theme' ) ){
			$json->theme = false;
		}
		if( !property_exists( $json, 'middleware' ) ){
			$json->middleware = false;
		}
		return $json;
	}

}
