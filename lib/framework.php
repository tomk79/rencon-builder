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
			header("Content-type: ".$this->mimetype($resource));
			$bin = $this->resource($resource);
			echo $bin;
			exit();

		}
		if( !isset( $_REQUEST ) || array_key_exists( 'a', $_REQUEST ) ){
			$action = $_REQUEST['a'];
		}
		if( array_key_exists( $action, $route ) ){
			$controller = $route[$action];
			ob_start();
			call_user_func($controller->page);
			$contents = ob_get_clean();
			$html = $this->theme( $contents );
			echo $html;

		}
		exit();
	}



	public function resource($path){
		$resources = array(

/* function resource() */

		);
		if( !array_key_exists($path, $resources) ){ return false; }
		return base64_decode($resources[$path]);
	}

	public function mimetype( $path ){
		$ext = preg_replace('/^.*\.([a-z0-9]+)$/i', '$1', $path);
		$ext = strtolower( $ext );

		$mimetype = 'text/plain';
		switch($ext){
			case 'htm':
			case 'html':
				$mimetype = 'text/html';
				break;
			case 'gif':
				$mimetype = 'image/gif';
				break;
			case 'png':
				$mimetype = 'image/png';
				break;
			case 'jpg':
			case 'jpe':
			case 'jpeg':
				$mimetype = 'image/jpeg';
				break;
			case 'css':
				$mimetype = 'text/css';
				break;
			case 'js':
				$mimetype = 'text/javascript';
				break;
		}
		return $mimetype;
	}


	public function theme( $contents ){
		ob_start();
?>
/* theme template */
<?php
		$html = ob_get_clean();
		return $html;
	}

}
?>