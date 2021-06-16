<?php
namespace tomk79\renconBuilder;

class resources {

	/** $utils */
	private $utils;

	/** $writer */
	private $writer;

	/** $package_name */
	private $package_name;

	/** $renconBuilderJson */
	private $renconBuilderJson;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $writer, $renconBuilderJson ){
		$this->utils = $utils;
		$this->writer = $writer;
		$this->renconBuilderJson = $renconBuilderJson;
	}


	/**
	 * 走査を始める
	 */
	public function scan( $localdir = null ){
		$basedir = $this->renconBuilderJson->resources;

		if( !$basedir ){
			return false;
		}
		if( !is_dir($basedir) ){
			return false;
		}


		$ls = $this->utils->fs()->ls( $basedir.$localdir );
		foreach( $ls as $basename ){
			if( $basename == '.DS_Store' || $basename == 'Thumbs.db' ){
				continue;
			}
			if( is_file( $basedir.$localdir.$basename ) ){
				$this->writer->resource( $localdir.$basename );
			}elseif( is_dir( $basedir.$localdir.$basename ) ){
				$this->scan( $localdir.$basename.'/' );
			}
		}
		return;
	}

}
