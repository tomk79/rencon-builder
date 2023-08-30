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

	/**
	 * 動的に追加されたプロパティ
	 *
	 * @access private
	 */
	private $custom_dynamic_property = array();

	private $app_id = '<!-- app_id -->';
	private $app_name = '<!-- app_name -->';

	private $route;
	private $api_route;
	private $route_params;

	private $routing_method = 'web';

	public function __construct( $conf ){
		$this->fs = new filesystem();
		$this->req = new request();
		$this->conf = new conf($this, $conf);
		$this->user = new user($this);
		$this->resources = new resources($this);
	}

	/**
	 * 動的なプロパティを登録する
	 */
	public function __set( $name, $property ){
		if( isset($this->custom_dynamic_property[$name]) ){
			trigger_error('$rencon->'.$name.' is already registered.');
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

		// 例外ハンドラを設定する
		set_exception_handler(function(\Throwable $exception) {
			$datestr = date('Y-m-d H:i:s');
			$realpath_private_data_dir = $this->conf()->realpath_private_data_dir ?? null;
			echo "Uncaught exception: ", $exception->getMessage(), "\n";
			if( $realpath_private_data_dir && is_dir($realpath_private_data_dir) ){
				if( !file_exists($realpath_private_data_dir.'/logs/') ){
					mkdir($realpath_private_data_dir.'/logs/');
				}
				error_log(
					$datestr." - Uncaught exception: ".$exception->getMessage().' on '.$exception->getFile().' line:'.$exception->getLine()."\n",
					3,
					$realpath_private_data_dir.'/logs/error_report.log'
				);
			}
		});

		// エラーハンドラを設定する
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			$datestr = date('Y-m-d H:i:s');
			$realpath_private_data_dir = $this->conf()->realpath_private_data_dir ?? null;
			if( $realpath_private_data_dir && is_dir($realpath_private_data_dir) ){
				if( !file_exists($realpath_private_data_dir.'/logs/') ){
					mkdir($realpath_private_data_dir.'/logs/');
				}
				error_log(
					$datestr.' - Error['.$errno.']: '.$errstr.' on '.$errfile.' line:'.$errline."\n",
					3,
					$realpath_private_data_dir.'/logs/error_report.log'
				);
			}

			return false;
		});

		// routing
		$this->route = array(
/* router:route */
		);

		$this->api_route = array(
/* router:api */
		);

		$middleware = array(/* middleware */);

		$action = $this->req->get_param('a') ?? '';
		$api_action = $this->req->get_param('api') ?? '';
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
		// ルーティングの方法を決める
		$this->routing_method = 'web';
		if( strlen($resource ?? '') ){
			$this->routing_method = 'resource';
		}elseif( !strlen($action ?? '') && strlen($api_action ?? '') ){
			$this->routing_method = 'api';
		}

		// --------------------------------------
		// リソースへのリクエストを処理
		if( $this->routing_method == 'resource' ){
			$this->resources->echo_resource( $resource );
			exit();

		}

		// --------------------------------------
		// 初期化処理
		$initializer = new initializer( $this );
		$initializer->initialize();


		// --------------------------------------
		// 認証処理
		if( $this->routing_method == 'web' ){
			if( $action == 'logout' ){
				$this->auth()->logout();
				exit;
			}

			$this->auth()->auth();

			if( $action == 'logout' || $action == 'login' ){
				$this->req()->set_param('a', '');
			}
		}


		// --------------------------------------
		// middleware
		foreach( $middleware as $method ){
			list( $className, $funcName ) = explode('::', $method);
			$tmp_obj = new $className();
			call_user_func( array($tmp_obj, $funcName), $this );
		}


		// --------------------------------------
		// コンテンツを処理
		$controller = $this->routing( $action, $this->route );
		if( $this->routing_method == 'api' ){
			header("Content-type: text/json");
			$controller = $this->routing( $api_action, $this->api_route );
		}
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

			$this->route_params = $controller->params ?? null;
			if( $this->routing_method == 'web' ){
				$page_info['title'] = $controller->title ?? null;
				$this->theme()->set_current_page_info( $page_info );

				ob_start();
				call_user_func( $controller->page, $this );
				$content = ob_get_clean();

				$html = $this->theme()->bind( $content );
				echo $html;

			}elseif( $this->routing_method == 'api' ){
				ob_start();
				$content = call_user_func( $controller->page, $this );
				$stdout = ob_get_clean();

				echo json_encode($content);
			}

		}else{
			$this->notfound();
		}
		exit();
	}

	/**
	 * ルーティング処理
	 */
	private function routing( $action, $route ){
		$action = (string) $action;

		// 静的固定ルート
		if( !preg_match('/\{([a-zA-Z][a-zA-Z0-9]*)\?\}/', $action) && array_key_exists( $action, $route ) ){
			$controller = $route[$action];
			return $controller;
		}

		// 動的ルート
		foreach( $route as $action_key => $controller ){
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
		if( is_string( $this->conf->realpath_private_data_dir ?? null ) ){
			$realpath_private_data_dir = $this->fs()->get_realpath($this->conf->realpath_private_data_dir.$localpath);
		}
		return $realpath_private_data_dir;
	}


	/**
	 * Not Found ページを表示して終了する
	 */
	public function notfound(){
		header("HTTP/1.0 404 Not Found");
		if( $this->routing_method == 'web' ){
			$page_info['title'] = 'Not Found';
			$this->theme()->set_current_page_info( $page_info );

			$content = '<p>404: Not Found</p>';
			$html = $this->theme()->bind( $content );
			echo $html;
		}elseif( $this->routing_method == 'api' ){
			header("Content-type: text/json");
			echo json_encode(array(
				"result" => false,
				"message" => 'Not Found',
			));
		}
		exit;
	}

	/**
	 * Method Not Allowed ページを表示して終了する
	 */
	public function method_not_allowed(){
		header("HTTP/1.0 405 Method Not Allowed");
		if( $this->routing_method == 'web' ){
			$page_info['title'] = 'Method Not Allowed';
			$this->theme()->set_current_page_info( $page_info );

			$content = '<p>405: Method Not Allowed</p>';
			$html = $this->theme()->bind( $content );
			echo $html;
		}elseif( $this->routing_method == 'api' ){
			header("Content-type: text/json");
			echo json_encode(array(
				"result" => false,
				"message" => 'Method Not Allowed',
			));
		}
		exit;
	}

	/**
	 * Forbidden ページを表示して終了する
	 */
	public function forbidden(){
		header("HTTP/1.0 403 Forbidden");
		if( $this->routing_method == 'web' ){
			$page_info['title'] = 'Forbidden';
			$this->theme()->set_current_page_info( $page_info );

			$content = '<p>403: Forbidden</p>';
			$html = $this->theme()->bind( $content );
			echo $html;
		}elseif( $this->routing_method == 'api' ){
			header("Content-type: text/json");
			echo json_encode(array(
				"result" => false,
				"message" => 'Forbidden',
			));
		}
		exit;
	}

}
?>