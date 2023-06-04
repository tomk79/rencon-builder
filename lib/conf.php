<?php
namespace renconFramework;

/**
 * rencon conf class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class conf {
	private $rencon;
	private $conf;
	private $custom_dynamic_property = array();
	public $users;
	public $realpath_private_data_dir;
	public $databases;

	/**
	 * Constructor
	 */
	public function __construct( $rencon, $conf ){
		$this->rencon = $rencon;
		$this->conf = (object) $conf;
		foreach( $this->conf as $key=>$val ){
			$this->{$key} = $val;
		}

		// --------------------------------------
		// $conf->users
		$this->users = null;
		if( property_exists( $conf, 'users' ) && !is_null( $conf->users ) ){
			$this->users = (array) $conf->users;
		}

		// --------------------------------------
		// $conf->realpath_private_data_dir
		$this->realpath_private_data_dir = null;
		if( property_exists( $conf, 'realpath_private_data_dir' ) && is_string( $conf->realpath_private_data_dir ) ){
			$this->realpath_private_data_dir = $this->rencon->fs()->get_realpath($conf->realpath_private_data_dir);
		}

		// --------------------------------------
		// $conf->databases
		$this->databases = null;
		if( property_exists( $conf, 'databases' ) && !is_null( $conf->databases ) ){
			$this->databases = (array) $conf->databases;
		}
	}

	/**
	 * 動的なプロパティを登録する
	 */
	public function __set( $name, $property ){
		if( isset($this->custom_dynamic_property[$name]) ){
			$this->error('$conf->'.$name.' is already registered.');
			return;
		}
		$this->custom_dynamic_property[$name] = $property;
		return;
	}

	/**
	 * 動的に追加されたプロパティを取り出す
	 */
	public function __get( $name ){
		return $this->custom_dynamic_property[$name] ?? null;
	}

	/**
	 * コンフィグ値を取得する
	 */
	public function get( $key = null ){
		if( is_null( $key ) ){
			return $this->conf;
		}
		if( property_exists( $this->conf, $key ) ){
			return $this->conf->{$key};
		}
		return false;
	}
}
?>