<?php
/* ---------------------

  <!-- appname -->

--------------------- */

namespace renconFramework;

// =-=-=-=-=-=-=-=-=-=-=-= Configuration START =-=-=-=-=-=-=-=-=-=-=-=
$conf = new \stdClass();






// =-=-=-=-=-=-=-=-=-=-=-= / Configuration END =-=-=-=-=-=-=-=-=-=-=-=


$app = new framework( $conf );
$app->run();

class framework {

	private $conf;
	public function __construct($conf){
		$this->conf = $conf;
	}

	public function run(){
		$route = array(

/* router */

		);

		$action = '';
		$resource = '';
		$controller = null;

		if( isset( $_REQUEST ) && array_key_exists( 'res', $_REQUEST ) ){
			$resource = $_REQUEST['res'];
			$bin = $this->resource($resource);
			echo $bin;
			exit();

		}
		if( !isset( $_REQUEST ) || array_key_exists( 'a', $_REQUEST ) ){
			$action = $_REQUEST['a'];
		}
		if( array_key_exists( $action, $route ) ){
			$controller = $route[$action];
		}
		echo 'starting app'."\n";
		echo '$action = '.$action."\n";
		echo '$resource = '.$resource."\n";

		var_dump( $controller );
		exit();
	}



	public function resource($path){
		$resources = array(

/* function resource() */

		);
		if( !array_key_exists($path, $resources) ){ return false; }
		return base64_decode($resources[$path]);
	}

}
?>