<?php
/* ---------------------

  <!-- appname --> v<!-- version -->

--------------------- */

namespace renconFramework;

// =-=-=-=-=-=-=-=-=-=-=-= Configuration START =-=-=-=-=-=-=-=-=-=-=-=

/*-- config --*/

// =-=-=-=-=-=-=-=-=-=-=-= / Configuration END =-=-=-=-=-=-=-=-=-=-=-=


$app = new app( $conf );
$app->run();

class app {

	private $conf;
	private $fs;
	private $req;
	private $resources;

	private $app_id = '<!-- app_id -->';
	private $app_name = '<!-- app_name -->';

	public function __construct( $conf ){
		$this->conf = new conf( $conf );
		$this->fs = new filesystem();
		$this->req = new request();
		$this->resources = new resources($this);
	}

	public function conf(){ return $this->conf; }
	public function fs(){ return $this->fs; }
	public function req(){ return $this->req; }

	public function run(){
		$route = array(

/* router */

		);

		$action = $this->req->get_param('a');
		$resource = $this->req->get_param('res');
		$controller = null;

		if( strlen($resource) ){
			header("Content-type: ".$this->resources->get_mime_type($resource));
			$bin = $this->resources->get($resource);
			echo $bin;
			exit();

		}

		header('Content-type: text/html'); // default

		$login = new login($this);
		if( !$login->check() ){
			$login->please_login();
			exit;
		}

		if( $action == 'logout' ){
			$login->logout();
			exit;
		}


		if( array_key_exists( $action, $route ) ){
			$controller = $route[$action];
			ob_start();
			call_user_func($controller->page);
			$content = ob_get_clean();

			$page_info = array(
				'id' => $action,
				'title' => $controller->title,
			);
			$app_info = array(
				'id' => $this->app_id,
				'name' => $this->app_name,
				'pages' => $route,
			);

			$theme = new theme( $this, $login, $app_info, $page_info );
			$html = $theme->bind( $content );
			echo $html;

		}
		exit();
	}

}
?>