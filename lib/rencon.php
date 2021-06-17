<?php
/* ---------------------

  <!-- app_name --> v<!-- version -->

--------------------- */

namespace renconFramework;

// =-=-=-=-=-=-=-=-=-=-=-= Configuration START =-=-=-=-=-=-=-=-=-=-=-=

/*-- config --*/

// =-=-=-=-=-=-=-=-=-=-=-= / Configuration END =-=-=-=-=-=-=-=-=-=-=-=


$rencon = new rencon( $conf );
$rencon->run();

class rencon {

	private $conf;
	private $fs;
	private $req;
	private $user;
	private $theme;
	private $resources;

	private $app_id = '<!-- app_id -->';
	private $app_name = '<!-- app_name -->';

	public function __construct( $conf ){
		$this->conf = new conf( $conf );
		$this->fs = new filesystem();
		$this->req = new request();
		$this->user = new user($this);
		$this->resources = new resources($this);
	}

	public function conf(){ return $this->conf; }
	public function fs(){ return $this->fs; }
	public function req(){ return $this->req; }
	public function user(){ return $this->user; }
	public function theme(){ return $this->theme; }
	public function resources(){ return $this->resources; }

	public function app_id(){ return $this->app_id; }
	public function app_name(){ return $this->app_name; }

	public function run(){
		$route = array(

/* router */

		);

		$action = $this->req->get_param('a');
		$resource = $this->req->get_param('res');
		$controller = null;
		$app_info = array(
			'id' => $this->app_id,
			'name' => $this->app_name,
			'pages' => $route,
		);
		$page_info = array(
			'id' => $action,
			'title' => 'Home',
		);
		$this->theme = new theme( $this, $app_info, $page_info );

		if( strlen($resource) ){
			header("Content-type: ".$this->resources->get_mime_type($resource));
			$bin = $this->resources->get($resource);
			echo $bin;
			exit();

		}

		header('Content-type: text/html'); // default

		$login = new login($this, $app_info);
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
			$page_info['title'] = $controller->title;

			ob_start();
			call_user_func($controller->page);
			$content = ob_get_clean();


			$html = $this->theme()->bind( $content );
			echo $html;

		}
		exit();
	}

}
?>