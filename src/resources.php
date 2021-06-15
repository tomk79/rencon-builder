<?php
namespace tomk79\renconBuilder;

class resources {

	/** $utils */
	private $utils;

	/** $writer */
	private $writer;

	/** $package_name */
	private $package_name;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $writer ){
		$this->utils = $utils;
		$this->writer = $writer;
	}


	/**
	 * 走査を始める
	 */
	public function scan( $basedir ){

		if( !$basedir ){
			return false;
		}
		if( !is_dir($basedir) ){
			return false;
		}


		$ls = $this->utils->fs()->ls( $basedir );
		foreach( $ls as $basename ){
			if( is_file( $basedir.$basename ) ){
				$this->writer->resource( $basedir.$basename );
			}elseif( is_dir( $basedir.$basename ) ){
				$this->scan( $basedir.$basename );
			}
		}
		return;
	}

}
