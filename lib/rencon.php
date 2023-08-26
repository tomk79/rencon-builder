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
	private $auth;
	private $theme;
	private $resources;

	private $app_id = '<!-- app_id -->';
	private $app_name = '<!-- app_name -->';

	private $route;
	private $route_params;

	public function __construct( $conf ){
		$this->fs = new filesystem();
		$this->req = new request();
		$this->conf = new conf($this, $conf);
		$this->user = new user($this);
		$this->resources = new resources($this);
	}

	public function conf(){ return $this->conf; }
	public function fs(){ return $this->fs; }
	public function req(){ return $this->req; }
	public function auth(){ return $this->auth; }
	public function user(){ return $this->user; }
	public function theme(){ return $this->theme; }
	public function resources(){ return $this->resources; }

	public function app_id(){ return $this->app_id; }
	public function app_name(){ return $this->app_name; }

	public function run(){
		header('Content-type: text/html'); // default

		$this->route = array(

/* router */

		);

		$action = $this->req->get_param('a') ?? null;
		$resource = $this->req->get_param('res') ?? null;
		$controller = null;
		$app_info = array(
			'id' => $this->app_id,
			'name' => $this->app_name,
			'pages' => $this->route,
		);
		$page_info = array(
			'id' => $action,
			'title' => 'Home',
		);
		$this->theme = new theme( $this, $app_info, $page_info );
		$this->auth = new auth( $this, $app_info );

		// --------------------------------------
		// リソースへのリクエストを処理
		if( strlen($resource ?? '') ){
			$this->resources->echo_resource( $resource );
			exit();

		}

		// --------------------------------------
		// 初期化処理
		$initializer = new initializer( $this );
		$initializer->initialize();


		// --------------------------------------
		// ログイン処理
		if( $action == 'logout' ){
			$this->auth()->logout();
			exit;
		}

		$this->auth()->auth();

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
		$controller = $this->routing($action);
		if( $controller ){

			// method を検査する
			$allow_methods = array("get");
			if(is_string($controller->allow_methods ?? null)){
				$allow_methods = array(strtolower($controller->allow_methods));
			}elseif(is_array($controller->allow_methods ?? null)){
				$allow_methods = array();
				foreach($controller->allow_methods as $method){
					array_push($allow_methods, strtolower($method));
				}
			}

			if( array_search( $this->req->get_method(), $allow_methods ) === false ){
				$this->method_not_allowed();
				exit();
			}


			$page_info['title'] = $controller->title;
			$this->route_params = $controller->params ?? null;
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
	 * ルーティング処理
	 */
	private function routing( $action ){

		// 静的固定ルート
		if( !preg_match('/\{([a-zA-Z][a-zA-Z0-9]*)\?\}/', $action) && array_key_exists( $action, $this->route ) ){
			$controller = $this->route[$action];
			return $controller;
		}

		// 動的ルート
		foreach( $this->route as $action_key => $controller ){
			$dynamicKeys = array();
			$action_key = preg_replace('/\./', '\\\\.', $action_key);
			$action_ptn = '';
			while(1){
				if( !preg_match('/^(.*?)\{([a-zA-Z][a-zA-Z0-9]*)\?\}(.*)$/', $action_key, $matched) ){
					$action_ptn .= $action_key;
					break;
				}
				$action_ptn .= $matched[1];
				array_push($dynamicKeys, $matched[2]);
				$action_ptn .= '([^\.]*)';
				$action_key = $matched[3];
			}
			if( preg_match('/^'.$action_ptn.'$/', $action, $matched) ){
				$routeParams = array();
				foreach( $dynamicKeys as $index => $key ){
					$routeParams[$key] = $matched[$index + 1];
				}
				$controller->params = (object) $routeParams;
				return $controller;
			}
		}

		return null;
	}


	/**
	 * ルーティングパラメータをすべて取得する
	 */
	public function get_route_params(){
		return $this->route_params;
	}

	/**
	 * ルーティングパラメータを取得する
	 */
	public function get_route_param( $key ){
		return $this->route_params->{$key} ?? null;
	}


	/**
	 * プラグイン専有の非公開データディレクトリの内部パスを取得する
	 */
	public function realpath_private_data_dir( $localpath = null ){
		$realpath_private_data_dir = null;
		if( property_exists( $this->conf, 'realpath_private_data_dir' ) && is_string( $this->conf->realpath_private_data_dir ) ){
			$realpath_private_data_dir = $this->fs()->get_realpath($this->conf->realpath_private_data_dir.$localpath);
		}
		return $realpath_private_data_dir;
	}


	/**
	 * Not Found ページを表示して終了する
	 */
	public function notfound(){
		header("HTTP/1.0 404 Not Found");
		$page_info['title'] = 'Not Found';
		$this->theme()->set_current_page_info( $page_info );

		$content = '<p>404: Not Found</p>';
		$html = $this->theme()->bind( $content );
		echo $html;
		exit;
	}

	/**
	 * Method Not Allowed ページを表示して終了する
	 */
	private function method_not_allowed(){
		header("HTTP/1.0 405 Method Not Allowed");
		$page_info['title'] = 'Method Not Allowed';
		$this->theme()->set_current_page_info( $page_info );

		$content = '<p>405: Method Not Allowed</p>';
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