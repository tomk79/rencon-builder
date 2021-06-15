<?php
/* ---------------------

  vendor/application v0.0.1-alpha.1+dev

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

'' => (object) array(
	"title" => 'Home',
	"page" => function(){ ?>
<p>トップページ</p>

<p><?php
var_dump( $_REQUEST );
?></p>
<?php return; },
),
'test' => (object) array(
	"title" => 'Test',
	"page" => 'app01\\test::start',
),


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
			call_user_func($controller->page);
		}
		exit();
	}



	public function resource($path){
		$resources = array(

'resources/test.txt' => 'VGVzdCBUZXh0IEZpbGUuCg==',


		);
		if( !array_key_exists($path, $resources) ){ return false; }
		return base64_decode($resources[$path]);
	}

}
?><?php

namespace app01;

class test {
    static public function start(){
        echo "test::start()"."\n";
        return;
    }
}
?><?php
namespace tomk79\filesystem;
// This is a dummy library.

class filesystem {}
?>