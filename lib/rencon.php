<?php
/* ---------------------

  <!-- app_name --> v<!-- version -->

--------------------- */

namespace renconFramework;

// =-=-=-=-=-=-=-=-=-=-=-= Configuration START =-=-=-=-=-=-=-=-=-=-=-=

/*-- config --*/

// =-=-=-=-=-=-=-=-=-=-=-= / Configuration END =-=-=-=-=-=-=-=-=-=-=-=


if( !isset($conf) ){
	$conf = new \stdClass();
}
$conf = (object) $conf;
$rencon = new rencon( $conf );
$rencon->run();
exit();

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
		header('Content-type: text/html'); // default

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

		// --------------------------------------
		// リソースへのリクエストを処理
		if( strlen($resource ?? '') ){
			$this->resources->echo_resource( $resource );
			exit();

		}

		// --------------------------------------
		// ログイン処理
		$login = new login($this, $app_info);

		if( $action == 'logout' ){
			$login->logout();
			exit;
		}

		if( !$login->check() ){
			if( $action == 'logout' || $action == 'login' ){
				$this->req()->set_param('a', null);
			}
			$login->please_login();
			exit;
		}

		if( $action == 'logout' || $action == 'login' ){
			$this->req()->set_param('a', null);
		}

		// --------------------------------------
		// middleware

		$middleware = array(/* middleware */);

		foreach( $middleware as $method ){
			list( $className, $funcName ) = explode('::', $method);
			$tmp_obj = new $className();
			call_user_func( array($tmp_obj, $funcName), $this );
		}



		// --------------------------------------
		// コンテンツを処理
		if( array_key_exists( $action, $route ) ){
			$controller = $route[$action];
			$page_info['title'] = $controller->title;
			$this->theme()->set_current_page_info( $page_info );

			ob_start();
			call_user_func( $controller->page, $this );
			$content = ob_get_clean();


			$html = $this->theme()->bind( $content );
			echo $html;

		}else{
			$this->notfound();
		}
		exit();
	}


	/**
	 * Not Found ページを表示して終了する
	 */
	public function notfound(){
		$page_info['title'] = 'Not Found';
		$this->theme()->set_current_page_info( $page_info );

		$content = '<p>404: Not Found</p>';
		$html = $this->theme()->bind( $content );
		echo $html;
		exit;
	}

	/**
	 * Forbidden ページを表示して終了する
	 */
	public function forbidden(){
		$page_info['title'] = 'Forbidden';
		$this->theme()->set_current_page_info( $page_info );

		$content = '<p>403: Forbidden</p>';
		$html = $this->theme()->bind( $content );
		echo $html;
		exit;
	}

}
?>