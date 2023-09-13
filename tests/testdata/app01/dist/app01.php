<?php
/* ---------------------

  Application Sample v1.0.0-alpha.1

--------------------- */

namespace renconFramework;

// =-=-=-=-=-=-=-=-=-=-=-= Configuration START =-=-=-=-=-=-=-=-=-=-=-=



$conf = new \stdClass();


/* --------------------------------------
 * 非公開データディレクトリのパス
 */
$conf->realpath_private_data_dir = __DIR__.'/'.basename(__FILE__, '.php').'__data/';


/* --------------------------------------
 * ログインユーザーのIDとパスワードの対
 * 
 * rencon の初期画面は、ログイン画面から始まります。
 * `$conf->users` に 登録されたユーザーが、ログインを許可されます。
 * ユーザーIDを キー に、sha1ハッシュ化されたパスワード文字列を 値 に持つ連想配列で設定してください。
 * ユーザーは、複数登録できます。
 */
$conf->users = array(
	"admin" => sha1("admin"),
	"admin2" => array(
		"name" => "Admin 2",
		"id" => "admin2",
		"pw" => sha1("admin2"),
	),
);


/* --------------------------------------
 * APIキー
 */
$conf->api_keys = array(
	"xxxxx-xxxxx-xxxxxxxxxxx-xxxxxxx" => array(
		"created_by" => "admin", // 作成したユーザーのID
		"permissions" => array( // このAPIキーで許可された項目
			"foo1",
			"foo2",
			"bar1",
		),
	),
);


/* --------------------------------------
 * DB接続情報
 */
$conf->databases = null;



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
	private $logger;
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

	private $app_id = 'app01';
	private $app_name = 'Application Sample';

	private $route;
	private $api_route;
	private $route_params;

	private $routing_method = 'web';

	public function __construct( $conf ){
		$this->fs = new filesystem();
		$this->req = new request();
		$this->logger = new logger($this);
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
	public function logger(){ return $this->logger; }
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
			if( strlen($realpath_private_data_dir ?? '') && is_dir($realpath_private_data_dir) ){
				if( !file_exists($realpath_private_data_dir.'/logs/') ){
					mkdir($realpath_private_data_dir.'/logs/');
				}
				if( !is_file($realpath_private_data_dir.'/logs/error_report.log.php') ){
					error_log(
						'<'.'?php header(\'HTTP/1.1 404 Not Found\'); echo(\'404 Not Found\');exit(); ?'.'>'."\n",
						3,
						$realpath_private_data_dir.'/logs/error_report.log.php'
					);
				}
				error_log(
					$datestr." - Uncaught exception: ".$exception->getMessage().' on '.$exception->getFile().' line:'.$exception->getLine()."\n",
					3,
					$realpath_private_data_dir.'/logs/error_report.log.php'
				);
			}
		});

		// エラーハンドラを設定する
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			$datestr = date('Y-m-d H:i:s');
			$realpath_private_data_dir = $this->conf()->realpath_private_data_dir ?? null;
			if( strlen($realpath_private_data_dir ?? '') && is_dir($realpath_private_data_dir) ){
				if( !file_exists($realpath_private_data_dir.'/logs/') ){
					mkdir($realpath_private_data_dir.'/logs/');
				}
				if( !is_file($realpath_private_data_dir.'/logs/error_report.log.php') ){
					error_log(
						'<'.'?php header(\'HTTP/1.1 404 Not Found\'); echo(\'404 Not Found\');exit(); ?'.'>'."\n",
						3,
						$realpath_private_data_dir.'/logs/error_report.log.php'
					);
				}
				error_log(
					$datestr.' - Error['.$errno.']: '.$errstr.' on '.$errfile.' line:'.$errline."\n",
					3,
					$realpath_private_data_dir.'/logs/error_report.log.php'
				);
			}

			return false;
		});

		// routing
		$this->route = array(
'' => (object) array(
	"title" => 'Home',
	"page" => function( $rencon ){ ?>
<p>トップページ</p>

<p><?php
var_dump( $_REQUEST );
?></p>

<p>middleware の処理を<a href="?middleware=1">確認する</a>。</p>

<p><img src="?res=images/sample-png.png" /></p>
<p><img src="?res=images/sample-jpeg.jpg" /></p>
<p><img src="?res=images/sample-gif.gif" /></p>
<?php return; },
	"allow_methods" => NULL,
),
'dynamic.{routeParam1?}.route' => (object) array(
	"title" => 'Dinamic route',
	"page" => 'app01\\dinamicRoute::start',
	"allow_methods" => NULL,
),
'api_preview' => (object) array(
	"title" => 'API Preview',
	"page" => 'app01\\test::api_preview',
	"allow_methods" => NULL,
),
'test' => (object) array(
	"title" => 'Test',
	"page" => 'app01\\test::start',
	"allow_methods" => NULL,
),
'test.post' => (object) array(
	"title" => 'Post Test',
	"page" => 'app01\\test::post',
	"allow_methods" => 'post',
),

		);

		$this->api_route = array(
'api.test.test001' => (object) array(
	"title" => NULL,
	"page" => 'app01\\api_test::test001',
	"allow_methods" => 'post',
),
'api.test.{routeParam1?}' => (object) array(
	"title" => NULL,
	"page" => 'app01\\api_test::test_route_param',
	"allow_methods" => 'post',
),

		);

		$middleware = array (
  0 => 'app01\\middleware\\sample::middleware',
);

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

		}elseif( $this->routing_method == 'api' ){
			$request_header = $this->req()->get_headers();
			$request_api_key = null;
			foreach($request_header as $key=>$val){
				$key = strtolower($key);
				if( $key == 'x-api-key' ){
					$request_api_key = $val;
					break;
				}
			}

			if( !$this->auth()->is_valid_api_key( $request_api_key ) ){
				$this->forbidden();
				exit;
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
?><?php
/**
 * tomk79/filesystem
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */

namespace renconFramework;

/**
 * tomk79/filesystem core class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class filesystem{

	/**
	 * ファイルおよびディレクトリ操作時のデフォルトパーミッション
	 */
	private $default_permission = array('dir'=>0775,'file'=>0775);
	/**
	 * ファイルシステムの文字セット
	 */
	private $filesystem_encoding = null;

	/**
	 * コンストラクタ
	 *
	 * @param object $conf 設定オブジェクト
	 */
	public function __construct($conf=null){
		$conf = json_decode( json_encode($conf), true );
		if(!is_array($conf)){
			$conf = array();
		}
		if( array_key_exists('file_default_permission', $conf) && strlen( $conf['file_default_permission'] ?? '' ) ){
			$this->default_permission['file'] = octdec( $conf['file_default_permission'] );
		}
		if( array_key_exists('dir_default_permission', $conf) && strlen( $conf['dir_default_permission'] ?? '' ) ){
			$this->default_permission['dir'] = octdec( $conf['dir_default_permission'] );
		}
	}

	/**
	 * 書き込み/上書きしてよいアイテムか検証する。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool 書き込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_writable( $path ){
		$path = $this->localize_path($path);
		if( !$this->is_file($path) ){
			return is_writable( dirname($path) );
		}
		return is_writable( $path );
	}

	/**
	 * 読み込んでよいアイテムか検証する。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool 読み込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_readable( $path ){
		$path = $this->localize_path($path);
		return is_readable( $path );
	}

	/**
	 * ファイルが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ファイルが存在する場合 `true`、存在しない場合、またはディレクトリが存在する場合に `false` を返します。
	 */
	public function is_file( $path ){
		$path = $this->localize_path($path);
		return is_file( $path );
	}

	/**
	 * シンボリックリンクかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ファイルがシンボリックリンクの場合 `true`、存在しない場合、それ以外の場合に `false` を返します。
	 */
	public function is_link( $path ){
		$path = $this->localize_path($path);
		return is_link( $path );
	}

	/**
	 * ディレクトリが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ディレクトリが存在する場合 `true`、存在しない場合、またはファイルが存在する場合に `false` を返します。
	 */
	public function is_dir( $path ){
		$path = $this->localize_path($path);
		return is_dir( $path );
	}

	/**
	 * ファイルまたはディレクトリが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ファイルまたはディレクトリが存在する場合 `true`、存在しない場合に `false` を返します。
	 */
	public function file_exists( $path ){
		$path = $this->localize_path($path);
		return file_exists( $path );
	}

	/**
	 * ディレクトリを作成する。
	 *
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir( $dirpath , $perm = null ){
		$dirpath = $this->localize_path($dirpath);

		if( $this->is_dir( $dirpath ) ){
			// 既にディレクトリがあったら、作成を試みない。
			$this->chmod( $dirpath , $perm );
			return true;
		}
		if( !$this->is_dir( dirname($dirpath) ) ){
			// 親ディレクトリが存在しない場合は、作成できない
			return false;
		}
		if( !$this->is_writable( dirname($dirpath) ) ){
			// 親ディレクトリに書き込みできない場合は、作成できない
			return false;
		}
		$result = mkdir( $dirpath );
		$this->chmod( $dirpath , $perm );
		clearstatcache();
		return	$result;
	}

	/**
	 * ディレクトリを作成する(上層ディレクトリも全て作成)
	 *
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir_r( $dirpath , $perm = null ){
		$dirpath = $this->localize_path($dirpath);
		if( $this->is_dir( $dirpath ) ){
			return true;
		}
		if( $this->is_file( $dirpath ) ){
			return false;
		}
		$patharray = explode( DIRECTORY_SEPARATOR , $this->localize_path( $this->get_realpath($dirpath) ) );
		$targetpath = '';
		foreach( $patharray as $idx=>$Line ){
			if( !strlen( $Line ?? '' ) || $Line == '.' || $Line == '..' ){ continue; }
			if(!($idx===0 && DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-zA-Z]\:$/s', $Line ?? ''))){
				$targetpath .= DIRECTORY_SEPARATOR;
			}
			$targetpath .= $Line;

			// clearstatcache();
			if( !$this->is_dir( $targetpath ) ){
				$targetpath = $this->localize_path( $targetpath );
				if( !$this->mkdir( $targetpath , $perm ) ){
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * ファイルやディレクトリを中身ごと完全に削除する。
	 *
	 * このメソッドは、ファイルやシンボリックリンクも削除します。
	 * ディレクトリを削除する場合は、中身ごと完全に削除します。
	 * シンボリックリンクは、その先を追わず、シンボリックリンク本体のみを削除します。
	 *
	 * @param string $path 対象のパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rm( $path ){
		$path = $this->localize_path($path);
		clearstatcache();

		if( !$this->is_writable( $path ) ){
			return false;
		}
		if( $this->is_file( $path ) || $this->is_link( $path ) ){
			// ファイルまたはシンボリックリンクの場合の処理
			$result = @unlink( $path );
			return	$result;

		}elseif( $this->is_dir( $path ) ){
			// ディレクトリの処理
			$flist = $this->ls( $path );
			if( is_array($flist) ){
				foreach ( $flist as $Line ){
					if( $Line == '.' || $Line == '..' ){ continue; }
					$this->rm( $path.DIRECTORY_SEPARATOR.$Line );
				}
			}
			$result = rmdir( $path );
			return	$result;

		}

		return false;
	}

	/**
	 * ディレクトリを削除する。
	 *
	 * このメソッドはディレクトリを削除します。
	 * 中身のない、空のディレクトリ以外は削除できません。
	 *
	 * @param string $path 対象ディレクトリのパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rmdir( $path ){
		$path = $this->localize_path($path);

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){
			return false;
		}
		if( $this->is_file( $path ) || $this->is_link( $path ) ){
			// ファイルまたはシンボリックリンクの場合の処理
			// ディレクトリ以外は削除できません。
			return false;

		}elseif( $this->is_dir( $path ) ){
			// ディレクトリの処理
			// rmdir() は再帰的削除を行いません。
			// 再帰的に削除したい場合は、代わりに `rm()` または `rmdir_r()` を使用します。
			return @rmdir( $path );
		}

		return false;
	}//rmdir()

	/**
	 * ディレクトリを再帰的に削除する。
	 *
	 * このメソッドはディレクトリを再帰的に削除します。
	 * 中身のない、空のディレクトリ以外は削除できません。
	 *
	 * @param string $path 対象ディレクトリのパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rmdir_r( $path ){
		$path = $this->localize_path($path);

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){
			return false;
		}
		if( $this->is_file( $path ) || $this->is_link( $path ) ){
			// ファイルまたはシンボリックリンクの場合の処理
			// ディレクトリ以外は削除できません。
			return false;

		}elseif( $this->is_dir( $path ) ){
			// ディレクトリの処理
			$filelist = $this->ls($path);
			if( is_array($filelist) ){
				foreach( $filelist as $basename ){
					if( $this->is_file( $path.DIRECTORY_SEPARATOR.$basename ) ){
						$this->rm( $path.DIRECTORY_SEPARATOR.$basename );
					}else if( !$this->rmdir_r( $path.DIRECTORY_SEPARATOR.$basename ) ){
						return false;
					}
				}
			}
			return $this->rmdir( $path );
		}

		return false;
	}//rmdir_r()


	/**
	 * ファイルを上書き保存する。
	 *
	 * このメソッドは、`$filepath` にデータを保存します。
	 * もともと保存されていた内容は破棄され、新しいデータで上書きします。
	 *
	 * @param string $filepath 保存先ファイルのパス
	 * @param string $content 保存する内容
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function save_file( $filepath , $content , $perm = null ){
		$filepath = $this->get_realpath($filepath);
		$filepath = $this->localize_path($filepath);

		if( $this->is_dir( $filepath ) ){
			return false;
		}
		if( !$this->is_writable( $filepath ) ){
			return false;
		}

		if( !strlen( $content ?? '' ) ){
			// 空白のファイルで上書きしたい場合
			if( $this->is_file( $filepath ) ){
				@unlink( $filepath );
			}
			@touch( $filepath );
			$this->chmod( $filepath , $perm );
			clearstatcache();
			return $this->is_file( $filepath );
		}

		clearstatcache();
		$fp = fopen( $filepath, 'w' );
		if( !is_resource( $fp ) ){
			return false;
		}

		for ($written = 0; $written < strlen($content); $written += $fwrite) {
			$fwrite = fwrite($fp, substr($content, $written));
			if ($fwrite === false) {
				break;
			}
		}

		fclose($fp);

		$this->chmod( $filepath , $perm );
		clearstatcache();
		return !empty( $written );
	}//save_file()

	/**
	 * ファイルの中身を文字列として取得する。
	 *
	 * @param string $path ファイルのパス
	 * @return string ファイル `$path` の内容
	 */
	public function read_file( $path ){
		$path = $this->localize_path($path);
		return file_get_contents( $path );
	}//file_get_contents()

	/**
	 * ファイルの更新日時を比較する。
	 *
	 * @param string $path_a 比較対象A
	 * @param string $path_b 比較対象B
	 * @return bool|null
	 * `$path_a` の方が新しかった場合に `true`、
	 * `$path_b` の方が新しかった場合に `false`、
	 * 同時だった場合に `null` を返します。
	 *
	 * いずれか一方、または両方のファイルが存在しない場合、次のように振る舞います。
	 * - 両方のファイルが存在しない場合 = `null`
	 * - $path_a が存在せず、$path_b は存在する場合 = `false`
	 * - $path_a が存在し、$path_b は存在しない場合 = `true`
	 */
	public function is_newer_a_than_b( $path_a , $path_b ){
		$path_a = $this->localize_path($path_a);
		$path_b = $this->localize_path($path_b);

		// 比較できない場合に
		if(!file_exists($path_a) && !file_exists($path_b)){return null;}
		if(!file_exists($path_a)){return false;}
		if(!file_exists($path_b)){return true;}

		$mtime_a = filemtime( $path_a );
		$mtime_b = filemtime( $path_b );
		if( $mtime_a > $mtime_b ){
			return true;
		}elseif( $mtime_a < $mtime_b ){
			return false;
		}
		return null;
	}//is_newer_a_than_b()

	/**
	 * ファイル名/ディレクトリ名を変更する。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename( $original , $newname ){
		$original = $this->localize_path($original);
		$newname  = $this->localize_path($newname );

		if( !file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		return rename( $original , $newname );
	}//rename()

	/**
	 * ファイル名/ディレクトリ名を強制的に変更する。
	 *
	 * 移動先の親ディレクトリが存在しない場合にも、親ディレクトリを作成して移動するよう試みます。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename_f( $original , $newname ){
		$original = $this->localize_path($original);
		$newname  = $this->localize_path($newname );

		if( !file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		$dirname = dirname( $newname );
		if( !$this->is_dir( $dirname ) ){
			if( !$this->mkdir_r( $dirname ) ){
				return false;
			}
		}
		return rename( $original , $newname );
	} // rename_f()

	/**
	 * 絶対パスを得る。
	 *
	 * パス情報を受け取り、スラッシュから始まるサーバー内部絶対パスに変換して返します。
	 *
	 * このメソッドは、PHPの `realpath()` と異なり、存在しないパスも絶対パスに変換します。
	 *
	 * @param string $path 対象のパス
	 * @param string $cd カレントディレクトリパス。
	 * 実在する有効なディレクトリのパス、または絶対パスの表現で指定される必要があります。
	 * 省略時、カレントディレクトリを自動採用します。
	 * @return string 絶対パス
	 */
	public function get_realpath( $path, $cd = '.' ){
		$is_dir = false;
		if( preg_match( '/(\/|\\\\)+$/s', $path ?? '' ) ){
			$is_dir = true;
		}
		$path = $this->localize_path($path);
		if( is_null($cd) ){ $cd = '.'; }
		$cd = $this->localize_path($cd);
		$preg_dirsep = preg_quote(DIRECTORY_SEPARATOR, '/');

		if( $this->is_dir($cd) ){
			$cd = realpath($cd);
		}elseif( !preg_match('/^((?:[A-Za-z]\\:'.$preg_dirsep.')|'.$preg_dirsep.'{1,2})(.*?)$/', $cd ?? '') ){
			$cd = false;
		}
		if( $cd === false ){
			return false;
		}

		$prefix = '';
		$localpath = $path;
		if( preg_match('/^((?:[A-Za-z]\\:'.$preg_dirsep.')|'.$preg_dirsep.'{1,2})(.*?)$/', $path ?? '', $matched) ){
			// もともと絶対パスの指定か調べる
			$prefix = preg_replace('/'.$preg_dirsep.'$/', '', $matched[1]);
			$localpath = $matched[2];
			$cd = null; // 元の指定が絶対パスだったら、カレントディレクトリは関係ないので捨てる。
		}

		$path = $cd.DIRECTORY_SEPARATOR.'.'.DIRECTORY_SEPARATOR.$localpath;

		if( file_exists( $prefix.$path ) ){
			$rtn = realpath( $prefix.$path );
			if( $is_dir && $rtn != realpath('/') ){
				$rtn .= DIRECTORY_SEPARATOR;
			}
			return $rtn;
		}

		$paths = explode( DIRECTORY_SEPARATOR, $path );
		$path = '';
		foreach( $paths as $idx=>$row ){
			if( $row == '' || $row == '.' ){
				continue;
			}
			if( $row == '..' ){
				$path = dirname($path);
				if($path == DIRECTORY_SEPARATOR || preg_match('/^[a-zA-Z]\:[\\/\\\\]*$/s', $path) ){
					$path ='';
				}
				continue;
			}
			if(!($idx===0 && DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-zA-Z]\:$/s', $row ?? ''))){
				$path .= DIRECTORY_SEPARATOR;
			}
			$path .= $row;
		}

		$rtn = $prefix.$path;
		if( $is_dir ){
			$rtn .= DIRECTORY_SEPARATOR;
		}
		return $rtn;
	}

	/**
	 * 相対パスを得る。
	 *
	 * パス情報を受け取り、ドットスラッシュから始まる相対絶対パスに変換して返します。
	 *
	 * @param string $path 対象のパス
	 * @param string $cd カレントディレクトリパス。
	 * 実在する有効なディレクトリのパス、または絶対パスの表現で指定される必要があります。
	 * 省略時、カレントディレクトリを自動採用します。
	 * @return string 相対パス
	 */
	public function get_relatedpath( $path, $cd = '.' ){
		$is_dir = false;
		if( preg_match( '/(\/|\\\\)+$/s', $path ?? '' ) ){
			$is_dir = true;
		}
		if( !strlen( $cd ?? '' ) ){
			$cd = realpath('.');
		}elseif( $this->is_dir($cd) ){
			$cd = realpath($cd);
		}elseif( $this->is_file($cd) ){
			$cd = realpath(dirname($cd));
		}
		$path = $this->get_realpath($path, $cd);

		$normalize = function( $tmp_path, $fs ){
			$tmp_path = $fs->localize_path( $tmp_path );
			$preg_dirsep = preg_quote(DIRECTORY_SEPARATOR, '/');
			if( DIRECTORY_SEPARATOR == '\\' ){
				$tmp_path = preg_replace( '/^[a-zA-Z]\:/s', '', $tmp_path ?? '' );
			}
			$tmp_path = preg_replace( '/^('.$preg_dirsep.')+/s', '', $tmp_path ?? '' );
			$tmp_path = preg_replace( '/('.$preg_dirsep.')+$/s', '', $tmp_path ?? '' );
			if( strlen($tmp_path) ){
				$tmp_path = explode( DIRECTORY_SEPARATOR, $tmp_path );
			}else{
				$tmp_path = array();
			}

			return $tmp_path;
		};

		$cd = $normalize($cd, $this);
		$path = $normalize($path, $this);

		$rtn = array();
		while( 1 ){
			if( !count($cd) || !count($path) ){
				break;
			}
			if( $cd[0] === $path[0] ){
				array_shift( $cd );
				array_shift( $path );
				continue;
			}
			break;
		}
		if( count($cd) ){
			foreach($cd as $dirname){
				array_push( $rtn, '..' );
			}
		}else{
			array_push( $rtn, '.' );
		}
		$rtn = array_merge( $rtn, $path );
		$rtn = implode( DIRECTORY_SEPARATOR, $rtn );

		if( $is_dir ){
			$rtn .= DIRECTORY_SEPARATOR;
		}
		return $rtn;
	}

	/**
	 * パス情報を得る。
	 *
	 * @param string $path 対象のパス
	 * @return array パス情報
	 */
	public function pathinfo( $path ){
		if(strpos($path,'#')!==false){ list($path, $hash) = explode( '#', $path, 2 ); }
		if(strpos($path,'?')!==false){ list($path, $query) = explode( '?', $path, 2 ); }

		$pathinfo = pathinfo( $path );
		$pathinfo['filename'] = $this->trim_extension( $pathinfo['basename'] );
		$pathinfo['extension'] = $this->get_extension( $pathinfo['basename'] );
		$pathinfo['query'] = (isset($query)&&strlen($query) ? '?'.$query : null);
		$pathinfo['hash'] = (isset($hash)&&strlen($hash) ? '#'.$hash : null);
		return $pathinfo;
	}

	/**
	 * パス情報から、ファイル名を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 抜き出されたファイル名
	 */
	public function get_basename( $path ){
		$path = pathinfo( $path , PATHINFO_BASENAME );
		if( !strlen($path ?? '') ){$path = null;}
		return $path;
	}

	/**
	 * パス情報から、拡張子を除いたファイル名を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 拡張子が除かれたパス
	 */
	public function trim_extension( $path ){
		$pathinfo = pathinfo( $path );
		if( !array_key_exists('extension', $pathinfo) ){
			$pathinfo['extension'] = '';
		}
		$RTN = preg_replace( '/\.'.preg_quote( $pathinfo['extension'], '/' ).'$/' , '' , $path ?? '' );
		return $RTN;
	}

	/**
	 * ファイル名を含むパス情報から、ファイルが格納されているディレクトリ名を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 親ディレクトリのパス
	 */
	public function get_dirpath( $path ){
		$path = pathinfo( $path , PATHINFO_DIRNAME );
		if( !strlen($path ?? '') ){$path = null;}
		return $path;
	}

	/**
	 * パス情報から、拡張子を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 拡張子
	 */
	public function get_extension( $path ){
		if( is_null($path) ){ return null; }
		$path = preg_replace('/\#.*$/si', '', $path);
		$path = preg_replace('/\?.*$/si', '', $path);
		$path = pathinfo( $path , PATHINFO_EXTENSION );
		if(!strlen($path ?? '')){$path = null;}
		return $path;
	}


	/**
	 * CSVファイルを読み込む。
	 *
	 * @param string $path 対象のCSVファイルのパス
	 * @param array $options オプション
	 * - delimiter = 区切り文字(省略時、カンマ)
	 * - enclosure = クロージャー文字(省略時、ダブルクオート)
	 * - size = 一度に読み込むサイズ(省略時、10000)
	 * - charset = 文字セット(省略時、UTF-8)
	 * @return array|bool 読み込みに成功した場合、行列を格納した配列、失敗した場合には `false` を返します。
	 */
	public function read_csv( $path , $options = array() ){
		// $options['charset'] は、保存されているCSVファイルの文字エンコードです。
		// 省略時は UTF-8 から、内部エンコーディングに変換します。

		$path = $this->localize_path($path);

		if( !$this->is_file( $path ) ){
			// ファイルがなければfalseを返す
			return false;
		}

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !isset($options['delimiter']) || !strlen( $options['delimiter'] ?? '' ) )    { $options['delimiter'] = ','; }
		if( !isset($options['enclosure']) || !strlen( $options['enclosure'] ?? '' ) )    { $options['enclosure'] = '"'; }
		if( !isset($options['size'])      || !strlen( $options['size'] ?? '' ) )         { $options['size'] = 10000; }
		if( !isset($options['charset'])   || !strlen( $options['charset'] ?? '' ) )      { $options['charset'] = 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP'; }//←CSVの文字セット

		$RTN = array();
		$fp = fopen( $path, 'r' );
		if( !is_resource( $fp ) ){
			return false;
		}

		while( $SMMEMO = fgetcsv( $fp , intval( $options['size'] ) , $options['delimiter'] , $options['enclosure'] ) ){
			foreach( $SMMEMO as $key=>$row ){
				$SMMEMO[$key] = mb_convert_encoding( $row ?? '' , mb_internal_encoding(), $options['charset'] );
			}
			array_push( $RTN , $SMMEMO );
		}
		fclose($fp);
		return $RTN;
	} // read_csv()

	/**
	 * 配列をCSV形式に変換する。
	 *
	 * 改行コードはLFで出力されます。
	 *
	 * @param array $array 2次元配列
	 * @param array $options オプション
	 * - charset = 文字セット(省略時、UTF-8)
	 * @return string 生成されたCSV形式のテキスト
	 */
	public function mk_csv( $array , $options = array() ){
		// $options['charset'] は、出力されるCSV形式の文字エンコードを指定します。
		// 省略時は UTF-8 に変換して返します。
		if( !is_array( $array ) ){ $array = array(); }

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !array_key_exists( 'charset', $options ) ){
			$options['charset'] = null;
		}
		if( !isset($options['charset']) || !strlen( $options['charset'] ?? '' ) ){
			$options['charset'] = 'UTF-8';
		}

		$RTN = '';
		foreach( $array as $Line ){
			if( is_null( $Line ) ){ continue; }
			if( !is_array( $Line ) ){ $Line = array(); }
			foreach( $Line as $cell ){
				$cell = mb_convert_encoding( $cell ?? '' , $options['charset']);
				if( preg_match( '/"/' , $cell ?? '' ) ){
					$cell = preg_replace( '/"/' , '""' , $cell ?? '');
				}
				if( strlen( $cell ) ){
					$cell = '"'.$cell.'"';
				}
				$RTN .= $cell.',';
			}
			$RTN = preg_replace( '/,$/' , '' , $RTN );
			$RTN .= "\n";
		}
		return $RTN;
	} // mk_csv()

	/**
	 * ファイルを複製する。
	 *
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy( $from , $to , $perm = null ){
		$from = $this->localize_path($from);
		$to   = $this->localize_path($to  );

		if( !$this->is_file( $from ) ){
			return false;
		}
		if( !$this->is_readable( $from ) ){
			return false;
		}

		if( $this->is_file( $to ) ){
			//	まったく同じファイルだった場合は、複製しないでtrueを返す。
			if( md5_file( $from ) == md5_file( $to ) && filesize( $from ) == filesize( $to ) ){
				return true;
			}
		}
		if( !@copy( $from , $to ) ){
			return false;
		}
		$this->chmod( $to , $perm );
		return true;
	}//copy()

	/**
	 * ディレクトリを再帰的に複製する(下層ディレクトリも全てコピー)
	 *
	 * ディレクトリを、含まれる内容ごと複製します。
	 * 受け取ったパスがファイルの場合は、単体のファイルが複製されます。
	 *
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy_r( $from , $to , $perm = null ){
		$from = $this->localize_path($from);
		$to   = $this->localize_path($to  );

		$result = true;

		if( $this->is_file( $from ) ){
			if( $this->mkdir_r( dirname( $to ) ) ){
				if( !$this->copy( $from , $to , $perm ) ){
					$result = false;
				}
			}else{
				$result = false;
			}
		}elseif( $this->is_dir( $from ) ){
			if( !$this->is_dir( $to ) ){
				if( !$this->mkdir_r( $to ) ){
					$result = false;
				}
			}
			$itemlist = $this->ls( $from );
			if( is_array($itemlist) ){
				foreach( $itemlist as $Line ){
					if( $Line == '.' || $Line == '..' ){ continue; }
					if( $this->is_dir( $from.DIRECTORY_SEPARATOR.$Line ) ){
						if( $this->is_file( $to.DIRECTORY_SEPARATOR.$Line ) ){
							continue;
						}elseif( !$this->is_dir( $to.DIRECTORY_SEPARATOR.$Line ) ){
							if( !$this->mkdir_r( $to.DIRECTORY_SEPARATOR.$Line ) ){
								$result = false;
							}
						}
						if( !$this->copy_r( $from.DIRECTORY_SEPARATOR.$Line , $to.DIRECTORY_SEPARATOR.$Line , $perm ) ){
							$result = false;
						}
						continue;
					}elseif( $this->is_file( $from.DIRECTORY_SEPARATOR.$Line ) ){
						if( !$this->copy_r( $from.DIRECTORY_SEPARATOR.$Line , $to.DIRECTORY_SEPARATOR.$Line , $perm ) ){
							$result = false;
						}
						continue;
					}
				}
			}
		}

		return $result;
	} // copy_r()

	/**
	 * パーミッションを変更する。
	 *
	 * @param string $filepath 対象のパス
	 * @param int $perm 与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function chmod( $filepath, $perm = null ){
		$filepath = $this->localize_path($filepath);
		if( !file_exists($filepath) ){
			return;
		}

		if( is_null( $perm ) ){
			if( $this->is_dir( $filepath ) ){
				$perm = $this->default_permission['dir'];
			}else{
				$perm = $this->default_permission['file'];
			}
		}
		if( is_null( $perm ) ){
			$perm = 0775; // コンフィグに設定モレがあった場合
		}
		return chmod( $filepath , $perm );
	} // chmod()

	/**
	 * パーミッションを再帰的に変更する。(下層のファイルやディレクトリも全て)
	 *
	 * `$perm_file` と `$perm_dir` が省略された場合は、代わりに初期化時に登録されたデフォルトのパーミッションが与えられます。
	 *
	 * 第2引数 `$perm_dir` が省略され、最初の引数 `$perm_file` だけが与えられた場合は、 ファイルとディレクトリの両方に `$perm_file` が適用されます。
	 *
	 * @param string $filepath 対象のパス
	 * @param int $perm_file ファイルに与えるパーミッション (省略可)
	 * @param int $perm_dir ディレクトリに与えるパーミッション (省略可)
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function chmod_r( $filepath, $perm_file = null, $perm_dir = null ){
		$filepath = $this->localize_path($filepath);
		if( !is_null( $perm_file ) && is_null($perm_dir) ){
			// パーミッション設定値が1つだけ与えられた場合には、
			// ファイルにもディレクトリにも適用する。
			$perm_dir = $perm_file;
		}

		$result = true;

		if( $this->is_file( $filepath ) ){
			if( !$this->chmod( $filepath, $perm_file ) ){
				$result = false;
			}
		}elseif( $this->is_dir( $filepath ) ){
			$itemlist = $this->ls( $filepath );
			if( !$this->chmod( $filepath, $perm_dir ) ){
				$result = false;
			}
			if( is_array($itemlist) ){
				foreach( $itemlist as $Line ){
					if( $Line == '.' || $Line == '..' ){ continue; }
					if( !$this->chmod_r( $filepath.DIRECTORY_SEPARATOR.$Line, $perm_file, $perm_dir ) ){
						$result = false;
					}
					continue;
				}
			}
		}

		return $result;
	}


	/**
	 * パーミッション情報を調べ、3桁の数字で返す。
	 *
	 * @param string $path 対象のパス
	 * @return int|bool 成功時に 3桁の数字、失敗時に `false` を返します。
	 */
	public function get_permission( $path ){
		$path = $this->localize_path($path);

		if( !file_exists( $path ) ){
			return false;
		}
		$perm = rtrim( sprintf( "%o\n" , fileperms( $path ) ) );
		$start = strlen( $perm ) - 3;
		return substr( $perm , $start , 3 );
	}


	/**
	 * ディレクトリにあるファイル名のリストを配列で返す。
	 *
	 * @param string $path 対象ディレクトリのパス
	 * @return array|bool 成功時にファイルまたはディレクトリ名の一覧を格納した配列、失敗時に `false` を返します。
	 */
	public function ls($path){
		$path = $this->localize_path($path);

		if( $path === false ){ return false; }
		if( !file_exists( $path ) ){ return false; }
		if( !$this->is_dir( $path ) ){ return false; }

		$RTN = array();
		$dr = @opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			// CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			array_push( $RTN , $ent );
		}
		closedir($dr);
		usort($RTN, "strnatcmp");
		return	$RTN;
	}//ls()

	/**
	 * ディレクトリの内部を比較し、$comparisonに含まれない要素を$targetから削除する。
	 *
	 * @param string $target クリーニング対象のディレクトリパス
	 * @param string $comparison 比較するディレクトリのパス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function compare_and_cleanup( $target , $comparison ){
		if( is_null( $comparison ) || is_null( $target ) ){ return false; }

		$target = $this->localize_path($target);
		$comparison = $this->localize_path($comparison);

		if( !file_exists( $comparison ) && file_exists( $target ) ){
			$this->rm( $target );
			return true;
		}

		if( $this->is_dir( $target ) ){
			$flist = $this->ls( $target );
		}else{
			return true;
		}

		if( is_array($flist) ){
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->compare_and_cleanup( $target.DIRECTORY_SEPARATOR.$Line , $comparison.DIRECTORY_SEPARATOR.$Line );
			}
		}

		return true;
	}//compare_and_cleanup()

	/**
	 * ディレクトリを同期する。
	 *
	 * @param string $path_sync_from 同期元ディレクトリ
	 * @param string $path_sync_to 同期先ディレクトリ
	 * @return bool 常に `true` を返します。
	 */
	public function sync_dir( $path_sync_from , $path_sync_to ){
		$this->copy_r( $path_sync_from , $path_sync_to );
		$this->compare_and_cleanup( $path_sync_to , $path_sync_from );
		return true;
	}//sync_dir()

	/**
	 * 指定されたディレクトリ以下の、全ての空っぽのディレクトリを削除する。
	 *
	 * @param string $path ディレクトリパス
	 * @param array $options オプション
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function remove_empty_dir( $path , $options = array() ){
		$path = $this->localize_path($path);

		if( !$this->is_writable( $path ) ){ return false; }
		if( !$this->is_dir( $path ) ){ return false; }
		if( $this->is_file( $path ) || $this->is_link( $path ) ){ return false; }
		$path = @realpath( $path );
		if( $path === false ){ return false; }

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !array_key_exists( 'depth', $options ) ){
			$options['depth'] = null;
		}

		// --------------------------------------
		// 次の階層を処理するかどうかのスイッチ
		$switch_donext = false;
		if( is_null( $options['depth'] ) ){
			// 深さの指定がなければ掘る
			$switch_donext = true;
		}elseif( !is_int( $options['depth'] ) ){
			// 指定がnullでも数値でもなければ掘らない
			$switch_donext = false;
		}elseif( $options['depth'] <= 0 ){
			// 指定がゼロ以下なら、今回の処理をして終了
			$switch_donext = false;
		}elseif( $options['depth'] > 0 ){
			// 指定が正の数(ゼロは含まない)なら、掘る
			$options['depth'] --;
			$switch_donext = true;
		}else{
			return false;
		}
		// / 次の階層を処理するかどうかのスイッチ
		// --------------------------------------

		$flist = $this->ls( $path );
		if( !count( $flist ) ){
			// 開いたディレクトリの中身が
			// "." と ".." のみだった場合
			// 削除して終了
			$result = @rmdir( $path );
			return	$result;
		}
		$alive = false;
		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( $this->is_link( $path.DIRECTORY_SEPARATOR.$Line ) ){
				// シンボリックリンクは無視する。
			}elseif( $this->is_dir( $path.DIRECTORY_SEPARATOR.$Line ) ){
				if( $switch_donext ){
					// さらに掘れと指令があれば、掘る。
					$this->remove_empty_dir( $path.DIRECTORY_SEPARATOR.$Line , $options );
				}
			}
			if( file_exists( $path.DIRECTORY_SEPARATOR.$Line ) ){
				$alive = true;
			}
		}
		if( !$alive ){
			$result = @rmdir( $path );
			return	$result;
		}
		return true;
	}//remove_empty_dir()


	/**
	 * 指定された2つのディレクトリの内容を比較し、まったく同じかどうか調べる。
	 *
	 * @param string $dir_a 比較対象ディレクトリA
	 * @param string $dir_b 比較対象ディレクトリB
	 * @param array $options オプション
	 * <dl>
	 *   <dt>bool $options['compare_filecontent']</dt>
	 * 	   <dd>ファイルの中身も比較するか？</dd>
	 *   <dt>bool $options['compare_emptydir']</dt>
	 * 	   <dd>空っぽのディレクトリの有無も評価に含めるか？</dd>
	 * </dl>
	 * @return bool 同じ場合に `true`、異なる場合に `false` を返します。
	 */
	public function compare_dir( $dir_a , $dir_b , $options = array() ){
		if( ( $this->is_file( $dir_a ) && !$this->is_file( $dir_b ) ) || ( !$this->is_file( $dir_a ) && $this->is_file( $dir_b ) ) ){
			return false;
		}
		if( ( ( $this->is_dir( $dir_a ) && !$this->is_dir( $dir_b ) ) || ( !$this->is_dir( $dir_a ) && $this->is_dir( $dir_b ) ) ) && $options['compare_emptydir'] ){
			return false;
		}

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !array_key_exists( 'compare_filecontent', $options ) ){
			$options['compare_filecontent'] = null;
		}
		if( !array_key_exists( 'compare_emptydir', $options ) ){
			$options['compare_emptydir'] = null;
		}

		if( $this->is_file( $dir_a ) && $this->is_file( $dir_b ) ){
			// --------------------------------------
			// 両方ファイルだったら
			if( $options['compare_filecontent'] ){
				// ファイルの内容も比較する設定の場合、
				// それぞれファイルを開いて同じかどうかを比較
				$filecontent_a = $this->read_file( $dir_a );
				$filecontent_b = $this->read_file( $dir_b );
				if( $filecontent_a !== $filecontent_b ){
					return false;
				}
			}
			return true;
		}

		if( $this->is_dir( $dir_a ) || $this->is_dir( $dir_b ) ){
			// --------------------------------------
			// 両方ディレクトリだったら
			$contlist_a = $this->ls( $dir_a );
			$contlist_b = $this->ls( $dir_b );

			if( $options['compare_emptydir'] && $contlist_a !== $contlist_b ){
				// 空っぽのディレクトリも厳密に評価する設定で、
				// ディレクトリ内の要素配列の内容が異なれば、false。
				return false;
			}

			$done = array();
			foreach( $contlist_a as $Line ){
				// Aをチェック
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.DIRECTORY_SEPARATOR.$Line , $dir_b.DIRECTORY_SEPARATOR.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

			foreach( $contlist_b as $Line ){
				// Aに含まれなかったBをチェック
				if( $done[$Line] ){ continue; }
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.DIRECTORY_SEPARATOR.$Line , $dir_b.DIRECTORY_SEPARATOR.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

		}

		return true;
	}//compare_dir()


	/**
	 * サーバがUNIXパスか調べる。
	 *
	 * @return bool UNIXパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_unix(){
		if( DIRECTORY_SEPARATOR == '/' ){
			return true;
		}
		return false;
	}//is_unix()

	/**
	 * サーバがWindowsパスか調べる。
	 *
	 * @return bool Windowsパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_windows(){
		if( DIRECTORY_SEPARATOR == '\\' ){
			return true;
		}
		return false;
	}//is_windows()


	/**
	 * パスを正規化する。
	 *
	 * 受け取ったパスを、スラッシュ区切りの表現に正規化します。
	 * Windowsのボリュームラベルが付いている場合は削除します。
	 * URIスキーム(http, https, ftp など) で始まる場合、2つのスラッシュで始まる場合(`//www.example.com/abc/` など)、これを残して正規化します。
	 *
	 *  - 例： `\a\b\c.html` → `/a/b/c.html` バックスラッシュはスラッシュに置き換えられます。
	 *  - 例： `/a/b////c.html` → `/a/b/c.html` 余計なスラッシュはまとめられます。
	 *  - 例： `C:\a\b\c.html` → `/a/b/c.html` ボリュームラベルは削除されます。
	 *  - 例： `http://a/b/c.html` → `http://a/b/c.html` URIスキームは残されます。
	 *  - 例： `//a/b/c.html` → `//a/b/c.html` ドメイン名は残されます。
	 *
	 * @param string $path 正規化するパス
	 * @return string 正規化されたパス
	 */
	public function normalize_path($path){
		if( is_null($path) ){ return null; }
		$path = trim($path ?? '');
		$path = $this->convert_encoding( $path );//文字コードを揃える
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//バックスラッシュをスラッシュに置き換える。
		$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		$prefix = '';
		if( preg_match( '/^((?:[a-zA-Z0-9]+\\:)?\\/)(\\/.*)$/', $path ?? '', $matched ) ){
			$prefix = $matched[1];
			$path = $matched[2];
		}
		$path = preg_replace( '/\\/+/s', '/', $path ?? '' );//重複するスラッシュを1つにまとめる
		return $prefix.$path;
	}


	/**
	 * パスをOSの標準的な表現に変換する。
	 *
	 * 受け取ったパスを、OSの標準的な表現に変換します。
	 * - スラッシュとバックスラッシュの違いを吸収し、`DIRECTORY_SEPARATOR` に置き換えます。
	 *
	 * @param string $path ローカライズするパス
	 * @return string ローカライズされたパス
	 */
	public function localize_path($path){
		if( is_null($path) ){ return null; }
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//一旦スラッシュに置き換える。
		if( $this->is_unix() ){
			// Windows以外だった場合に、ボリュームラベルを受け取ったら削除する
			$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		}
		$path = preg_replace( '/\\/+/s', '/', $path );//重複するスラッシュを1つにまとめる
		$path = preg_replace( '/\\/|\\\\/s', DIRECTORY_SEPARATOR, $path );
		return $path;
	}

	/**
	 * 受け取ったテキストを、ファイルシステムエンコードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $to_encoding 文字セット(省略時、内部文字セット)
	 * @param string $from_encoding 変換前の文字セット
	 * @return string 文字セット変換後のテキスト
	 */
	public function convert_encoding( $text, $to_encoding = null, $from_encoding = null ){
		$RTN = $text;
		if( !is_callable( 'mb_internal_encoding' ) ){
			return $text;
		}

		$to_encoding_fin = $to_encoding;
		if( !strlen($to_encoding_fin ?? '') ){
			$to_encoding_fin = mb_internal_encoding();
		}
		if( !strlen($to_encoding_fin ?? '') ){
			$to_encoding_fin = 'UTF-8';
		}

		$from_encoding_fin = (is_string($from_encoding) && strlen($from_encoding) ? $from_encoding : 'UTF-8,SJIS-win,cp932,eucJP-win,SJIS,EUC-JP,JIS,ASCII');

		// ---
		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){
				return $text;
			}
			foreach( $text as $key=>$row ){
				$RTN[$key] = $this->convert_encoding( $row, $to_encoding, $from_encoding );
			}
		}else{
			if( !strlen( $text ?? '' ) ){
				return $text;
			}
			$RTN = mb_convert_encoding( $text ?? '', $to_encoding_fin, $from_encoding_fin );
		}
		return $RTN;
	}

	/**
	 * 受け取ったテキストを、指定の改行コードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $crlf 改行コード名。CR|LF(default)|CRLF
	 * @return string 改行コード変換後のテキスト
	 */
	public function convert_crlf( $text, $crlf = null ){
		if( !strlen($crlf ?? '') ){
			$crlf = 'LF';
		}
		$crlf_code = "\n";
		switch(strtoupper($crlf ?? '')){
			case 'CR':
				$crlf_code = "\r";
				break;
			case 'CRLF':
				$crlf_code = "\r\n";
				break;
			case 'LF':
			default:
				$crlf_code = "\n";
				break;
		}
		$RTN = $text;
		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){
				return $text;
			}
			foreach( $text as $key=>$val ){
				$RTN[$key] = $this->convert_crlf( $val , $crlf );
			}
		}else{
			if( !strlen( $text ?? '' ) ){
				return $text;
			}
			$RTN = preg_replace( '/\r\n|\r|\n/', $crlf_code, $text );
		}
		return $RTN;
	}

}
?><?php
/**
 * tomk79/request
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */

namespace renconFramework;

/**
 * tomk79/request core class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class request{
	/**
	 * 設定オブジェクト
	 */
	private $conf;
	/**
	 * ファイルシステムオブジェクト
	 */
	private $fs;
	/**
	 * URLパラメータ
	 */
	private $param = array();
	/**
	 * コマンドからのアクセス フラグ
	 */
	private $flg_cmd = false;
	/**
	 * リクエストファイルパス
	 */
	private $request_file_path;
	/**
	 * 優先ディレクトリインデックス
	 */
	private $directory_index_primary;
	/**
	 * コマンドラインオプション
	 */
	private $cli_options;
	/**
	 * コマンドラインパラメータ
	 */
	private $cli_params;

	/**
	 * コンストラクタ
	 *
	 * @param object $conf 設定オブジェクト
	 */
	public function __construct( $conf = null ){
		$this->conf = (object) array();
		$this->fs = new filesystem();

		if(!property_exists($this->conf, 'get') || !@is_array($this->conf->get)){
			$this->conf->get = $_GET;
		}
		if(!property_exists($this->conf, 'post') || !@is_array($this->conf->post)){
			$this->conf->post = $_POST;
		}
		if(!property_exists($this->conf, 'files') || !@is_array($this->conf->files)){
			$this->conf->files = $_FILES;
		}
		if(!property_exists($this->conf, 'server') || !@is_array($this->conf->server)){
			$this->conf->server = $_SERVER;
		}
		if( !array_key_exists( 'PATH_INFO' , $this->conf->server ) ){
			$this->conf->server['PATH_INFO'] = null;
		}
		if( !array_key_exists( 'HTTP_USER_AGENT' , $this->conf->server ) ){
			$this->conf->server['HTTP_USER_AGENT'] = null;
		}
		if( !array_key_exists( 'argv' , $this->conf->server ) ){
			$this->conf->server['argv'] = null;
		}
		if(!property_exists($this->conf, 'session_name') || !strlen($this->conf->session_name ?? '')){
			$this->conf->session_name = 'SESSID';
		}
		if(!property_exists($this->conf, 'session_expire') || !strlen($this->conf->session_expire ?? '')){
			$this->conf->session_expire = 1800;
		}
		if(!property_exists($this->conf, 'directory_index_primary') || !strlen($this->conf->directory_index_primary ?? '')){
			$this->conf->directory_index_primary = 'index.html';
		}
		if(!property_exists($this->conf, 'cookie_default_path') || !strlen($this->conf->cookie_default_path ?? '')){
			// クッキーのデフォルトのパス
			// session の範囲もこの設定に従う。
			$this->conf->cookie_default_path = $this->get_path_current_dir();
		}

		$this->parse_input();
		$this->session_start();
	}

	/**
	 *	入力値を解析する。
	 *
	 * `$_GET`, `$_POST`, `$_FILES` に送られたパラメータ情報を取りまとめ、1つの連想配列としてまとめま、オブジェクト内に保持します。
	 *
	 * コマンドラインから実行された場合は、コマンドラインオプションをそれぞれ `=` 記号で区切り、URLパラメータ同様にパースします。
	 *
	 * このメソッドの処理には、入力文字コードの変換(UTF-8へ統一)などの整形処理が含まれます。
	 *
	 * @return bool 常に `true`
	 */
	private function parse_input(){
		$this->request_file_path = $this->conf->server['PATH_INFO'];
		if( !strlen($this->request_file_path ?? '') ){
			$this->request_file_path = '/';
		}
		$this->cli_params = array();
		$this->cli_options = array();

		if( !array_key_exists( 'REMOTE_ADDR' , $this->conf->server ) ){
			//  コマンドラインからの実行か否か判断
			$this->flg_cmd = true;//コマンドラインから実行しているかフラグ
			if( is_array( $this->conf->server['argv'] ) && count( $this->conf->server['argv'] ) ){
				$tmp_path = null;
				for( $i = 0; count( $this->conf->server['argv'] ) > $i; $i ++ ){
					if( preg_match( '/^\-/', $this->conf->server['argv'][$i] ) ){
						$this->cli_params = array();//オプションの前に引数は付けられない
						$this->cli_options[$this->conf->server['argv'][$i]] = $this->conf->server['argv'][$i+1];
						$i ++;
					}else{
						array_push( $this->cli_params, $this->conf->server['argv'][$i] );
					}
				}
				$tmp_path = @$this->cli_params[count($this->cli_params)-1];
				if( preg_match( '/^\//', $tmp_path ) && @is_array($this->conf->server['argv']) ){
					$tmp_path = array_pop( $this->conf->server['argv'] );
					$tmp_path = parse_url($tmp_path);
					$this->request_file_path = $tmp_path['path'];
					@parse_str( $tmp_path['query'], $query );
					if( is_array($query) ){
						$this->conf->get = array_merge( $this->conf->get, $query );
					}
				}
				unset( $tmp_path );
			}
		}

		if( ini_get('magic_quotes_gpc') ){
			// PHPINIのmagic_quotes_gpc設定がOnだったら、
			// エスケープ文字を削除。
			foreach( array_keys( $this->conf->get ) as $Line ){
				$this->conf->get[$Line] = self::stripslashes( $this->conf->get[$Line] );
			}
			foreach( array_keys( $this->conf->post ) as $Line ){
				$this->conf->post[$Line] = self::stripslashes( $this->conf->post[$Line] );
			}
		}

		$this->conf->get = self::convert_encoding( $this->conf->get );
		$this->conf->post = self::convert_encoding( $this->conf->post );
		$param = array_merge( $this->conf->get , $this->conf->post );
		$param = $this->normalize_input( $param );

		if( is_array( $this->conf->files ) ){
			$FILES_KEYS = array_keys( $this->conf->files );
			foreach($FILES_KEYS as $Line){
				$this->conf->files[$Line]['name'] = self::convert_encoding( $this->conf->files[$Line]['name'] );
				$this->conf->files[$Line]['name'] = mb_convert_kana( $this->conf->files[$Line]['name'] , 'KV' , mb_internal_encoding() );
				$param[$Line] = $this->conf->files[$Line];
			}
		}

		$this->param = $param;
		unset($param);

		if (preg_match('/\/$/', $this->request_file_path)) {
			$this->request_file_path .= $this->conf->directory_index_primary;
		}
		$this->request_file_path = $this->fs->get_realpath( $this->request_file_path );
		$this->request_file_path = $this->fs->normalize_path( $this->request_file_path );

		return true;
	}

	/**
	 *	入力値に対する標準的な変換処理
	 *
	 * @param array $param パラメータ
	 * @return array 変換後のパラメータ
	 */
	private function normalize_input( $param ){
		$is_callable_mb_check_encoding = is_callable( 'mb_check_encoding' );
		foreach( $param as $key=>$val ){
			// URLパラメータを加工
			if( is_array( $val ) ){
				// 配列なら
				$param[$key] = $this->normalize_input( $param[$key] );
			}elseif( is_string( $param[$key] ) ){
				// 文字列なら
				$param[$key] = mb_convert_kana( $param[$key] , 'KV' , mb_internal_encoding() );
					// 半角カナは全角に統一
				$param[$key] = preg_replace( '/\r\n|\r|\n/' , "\n" , $param[$key] );
					// 改行コードはLFに統一
				if( $is_callable_mb_check_encoding ){
					// 不正なバイトコードのチェック
					if( !mb_check_encoding( $key , mb_internal_encoding() ) ){
						// キーの中に見つけたらパラメータごと削除
						unset( $param[$key] );
					}
					if( !mb_check_encoding( $param[$key] , mb_internal_encoding() ) ){
						// 値の中に見つけたら false に置き換える
						$param[$key] = false;
					}
				}
			}
		}
		return $param;
	}

	/**
	 * メソッドを取得する
	 * @return string|boolean メソッド名。すべて小文字に変換されて返されます。コマンドラインから実行された場合は `command` が返されます。取得できない場合は `false` を返します。
	 */
	public function get_method(){
		if( $this->is_cmd() ){
			return 'command';
		}
		if( isset($this->conf->server['REQUEST_METHOD']) && is_string($this->conf->server['REQUEST_METHOD']) && strlen($this->conf->server['REQUEST_METHOD']) ){
			return strtolower( $this->conf->server['REQUEST_METHOD'] );
		}
		return false;
	}

	/**
	 * リクエストヘッダ全体を取得する
	 * @return array|boolean|null リクエストヘッダーのリスト。コマンドラインから実行されている場合は `null` を返します。`getallheaders` が実行できない場合 `false` を返します。
	 */
	public function get_headers(){
		if( $this->is_cmd() ){
			return null;
		}
		if( !is_callable('getallheaders') ){
			return false;
		}
		$headers = getallheaders();
		return $headers;
	}

	/**
	 * リクエストヘッダを取得する
	 *
	 * @param string $name ヘッダー名。`get_header()` は、大文字/小文字を区別しません。
	 * @param boolean $ignore_case `true` が指定された場合、 `get_header()` は、 `$name` の大文字/小文字を区別せずに検索します。デフォルトは `true` です。
	 * @return string|boolean|null リクエストヘッダーの値。
	 * 与えられた名前に該当する項目がない場合、コマンドラインから実行されている場合は `null` を返します。
	 * `getallheaders` が実行できない場合、その他ヘッダー情報全体にアクセスできない場合は `false` を返します。
	 */
	public function get_header( $name, $ignore_case = true ){
		$headers = $this->get_headers();
		if( !is_array($headers) ){
			return $headers;
		}
		if( $ignore_case ){
			$name = strtolower( $name );
		}
		foreach( $headers as $header_key => $header_val ){
			if( $ignore_case ){
				$header_key = strtolower( $header_key );
			}
			if( $name == $header_key ){
				return $header_val;
			}
		}
		return null;
	}

	/**
	 * パラメータを取得する。
	 *
	 * `$_GET`, `$_POST`、`$_FILES` を合わせた連想配列の中から `$key` に当たる値を引いて返します。
	 * キーが定義されていない場合は、`null` を返します。
	 *
	 * @param string $key URLパラメータ名
	 * @return mixed URLパラメータ値
	 */
	public function get_param( $key ){
		if( !isset( $this->param[$key] ) ){
			return null;
		}
		return $this->param[$key];
	}

	/**
	 * パラメータをセットする。
	 *
	 * @param string $key パラメータ名
	 * @param mixed $val パラメータ値
	 * @return bool 常に `true`
	 */
	public function set_param( $key , $val ){
		$this->param[$key] = $val;
		return true;
	}

	/**
	 * パラメータをすべて取得する。
	 *
	 * @return array すべてのパラメータを格納する連想配列
	 */
	public function get_all_params(){
		return $this->param;
	}

	/**
	 * コマンドラインオプションを取得する
	 * @param string $name オプション名
	 * @return string 指定されたオプション値
	 */
	public function get_cli_option( $name ){
		if( !array_key_exists($name, $this->cli_options) ){
			return null;
		}
		if( !isset( $this->cli_options[$name] ) ){
			return null;
		}
		return $this->cli_options[$name];
	}

	/**
	 * すべてのコマンドラインオプションを連想配列で取得する
	 * @return array すべてのコマンドラインオプション
	 */
	public function get_cli_options(){
		return $this->cli_options;
	}

	/**
	 * コマンドラインパラメータを取得する
	 * @param string $idx パラメータ番号
	 * @return string 指定されたオプション値
	 */
	public function get_cli_param( $idx = 0 ){
		if($idx < 0){
			// マイナスのインデックスが与えられた場合、
			// 配列の最後から数える
			$idx = count($this->cli_params)+$idx;
		}
		if( !isset( $this->cli_params[$idx] ) ){
			return null;
		}
		return $this->cli_params[$idx];
	}

	/**
	 * すべてのコマンドラインパラメータを配列で取得する
	 * @return array すべてのコマンドラインパラメータ
	 */
	public function get_cli_params(){
		return $this->cli_params;
	}



	// ----- cookies -----

	/**
	 * クッキー情報を取得する。
	 *
	 * @param string $key クッキー名
	 * @return mixed クッキーの値
	 */
	public function get_cookie( $key ){
		if( !isset( $_COOKIE[$key] ) ){
			return null;
		}
		return $_COOKIE[$key];
	}

	/**
	 * クッキー情報をセットする。
	 *
	 * @param string $key クッキー名
	 * @param string $val クッキー値
	 * @param string $expires_or_options クッキーの有効期限。
	 * @param string $path サーバー上での、クッキーを有効としたいパス。デフォルトは `/`
	 * @param string $domain クッキーが有効なドメイン。
	 * @param bool $secure `true` を設定し、クライアントからのセキュアな HTTPS 接続の場合にのみクッキーが送信されるようにします。デフォルトは `true`
	 * @param bool $httponly `true` を設定し、HTTPでの送信のみ許可し、JavaScriptから参照できないようにします。デフォルトは `true`
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function set_cookie( $key , $val , $expires_or_options = null , $path = null , $domain = null , $secure = true, $httponly = true ){
		$options = array();
		if( is_array($expires_or_options) ){
			$options = $expires_or_options;
		}elseif( is_int($expires_or_options) ){
			$options['expires'] = $expires_or_options;
		}
		$options['expires'] = $options['expires'] ?? $this->conf->cookie_default_expire;
		$options['expires'] += time();
		$options['path'] = $options['path'] ?? $path ?? $this->get_path_current_dir() ?? '/';

		if( !isset($options['domain']) && strlen($domain ?? '') ){
			$options['domain'] = $domain;
		}
		if( !strlen($options['domain'] ?? '') ){
			$options['domain'] = $this->conf->cookie_default_domain;
		}
		if( !isset($options['secure']) && !is_null($secure) ){
			$options['secure'] = !!$secure;
		}
		if( !isset($options['httponly']) && !is_null($httponly) ){
			$options['httponly'] = !!$httponly;
		}

		if( !@setcookie( $key, $val ?? '', $options ) ){
			return false;
		}

		$_COOKIE[$key] = $val; // 現在の処理からも呼び出せるように
		return true;
	}

	/**
	 * クッキー情報を削除する。
	 *
	 * @param string $key クッキー名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function delete_cookie( $key ){
		if( !@setcookie( $key , '', 0 ) ){
			return false;
		}
		unset( $_COOKIE[$key] );
		return true;
	}



	// ----- session -----

	/**
	 * セッションを開始する。
	 *
	 * @return bool セッションが正常に開始した場合に `true`、それ以外の場合に `false` を返します。
	 */
	private function session_start(){
		if( $this->is_cmd() ){
			// CLIではセッションを開始しない
			return false;
		}
		if(isset($_SESSION)){
			// すでにセッションが開始されていたら、ここで終了する。
			return true;
		}

		$expire = intval($this->conf->session_expire);
		$session_name = 'SESSID';
		if( strlen( $this->conf->session_name ?? '' ) ){
			$session_name = $this->conf->session_name;
		}
		$path = $this->conf->cookie_default_path;
		if( !strlen( $path ?? '' ) ){
			$path = $this->get_path_current_dir();
		}
		if( !strlen( $path ?? '' ) ){
			$path = '/';
		}

		@session_name( $session_name );
		@session_set_cookie_params( $expire, $path );

		// セッションを開始
		$rtn = @session_start();

		// セッションの有効期限を評価
		$last_modified_time_key = 'SESSION_STARTED_AT';
		if( strlen( $this->get_session( $last_modified_time_key ) ?? '' ) ){
			$last_modified_time = intval( $this->get_session( $last_modified_time_key ) );
			if( $last_modified_time < intval( time() - $expire ) ){
				// セッションの有効期限が切れていたら、セッションを破壊する。
				$_SESSION = array();
			}elseif( $last_modified_time < intval( time() - ($expire/2) ) ){
				// セッションの有効期限が残り 半分 を切っていたら、セッションを再発行し延長する。
				$this->session_update();
				$this->delete_session( $last_modified_time_key ); // 一旦削除
			}
		}
		if( !strlen( $this->get_session( $last_modified_time_key ) ?? '' ) ){
			$this->set_session( $last_modified_time_key, time() );
		}

		return $rtn;
	}

	/**
	 * セッションを更新する。
	 *
	 * @return boolean 成功した場合に `true` を、失敗した場合に `false` を返します。 
	 */
	public function session_update(){
		$destroyed_time_key = 'SESSION_DESTROYED_AT';
		$_SESSION[$destroyed_time_key] = time();
		$result = session_regenerate_id();
		unset($_SESSION[$destroyed_time_key]);
		return $result;
	}

	/**
	 * セッションIDを取得する。
	 *
	 * @return string セッションID
	 */
	public function get_session_id(){
		return session_id();
	}

	/**
	 * セッション情報を取得する。
	 *
	 * @param string $key セッションキー
	 * @return mixed `$key` に対応するセッション値
	 */
	public function get_session( $key ){
		if( !isset( $_SESSION[$key] ) ){
			return null;
		}
		return $_SESSION[$key];
	}

	/**
	 * セッション情報をセットする。
	 *
	 * @param string $key セッションキー
	 * @param mixed $val `$key` に対応するセッション値
	 * @return bool 常に `true` を返します。
	 */
	public function set_session( $key , $val ){
		$_SESSION[$key] = $val;
		return true;
	}

	/**
	 * セッション情報を削除する。
	 *
	 * @param string $key セッションキー
	 * @return bool 常に `true` を返します。
	 */
	public function delete_session( $key ){
		unset( $_SESSION[$key] );
		return true;
	}


	// ----- upload file access -----

	/**
	 * アップロードされたファイルをセッションに保存する。
	 *
	 * @param string $key セッションキー
	 * @param array $ulfileinfo アップロードファイル情報
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function save_uploadfile( $key , $ulfileinfo ){
		// base64でエンコードして、バイナリデータを持ちます。
		// $ulfileinfo['content'] にバイナリを格納して渡すか、
		// $ulfileinfo['tmp_name'] または $ulfileinfo['path'] のいずれかに、
		// アップロードファイルのパスを指定してください。
		$fileinfo = array();
		$fileinfo['name'] = $ulfileinfo['name'];
		$fileinfo['type'] = $ulfileinfo['type'];

		if( $ulfileinfo['content'] ){
			$fileinfo['content'] = base64_encode( $ulfileinfo['content'] );
		}else{
			$filepath = '';
			if( @is_file( $ulfileinfo['tmp_name'] ) ){
				$filepath = $ulfileinfo['tmp_name'];
			}elseif( @is_file( $ulfileinfo['path'] ) ){
				$filepath = $ulfileinfo['path'];
			}else{
				return false;
			}
			$fileinfo['content'] = base64_encode( file_get_contents( $filepath ) );
		}

		if( !is_array( $_SESSION ) ){
			$_SESSION = array();
		}
		if( !isset($_SESSION['FILE']) ){
			$_SESSION['FILE'] = array();
		}

		$_SESSION['FILE'][$key] = $fileinfo;
		return true;
	}
	/**
	 * セッションに保存されたファイル情報を取得する。
	 *
	 * @param string $key セッションキー
	 * @return array|boolean 成功時、ファイル情報 を格納した連想配列、失敗時 `false` を返します。
	 */
	public function get_uploadfile( $key ){
		if( !strlen($key ?? '' )){
			return false;
		}
		if( !isset($_SESSION['FILE'][$key]) ){
			return false;
		}

		$rtn = $_SESSION['FILE'][$key];
		if( !isset( $rtn['content'] ) ){
			return false;
		}

		$rtn['content'] = base64_decode( $rtn['content'] );
		return $rtn;
	}
	/**
	 * セッションに保存されたファイル情報の一覧を取得する。
	 *
	 * @return array ファイル情報 を格納した連想配列
	 */
	public function get_uploadfile_list(){
		if( !isset($_SESSION['FILE']) ){
			return false;
		}
		return array_keys( $_SESSION['FILE'] );
	}
	/**
	 * セッションに保存されたファイルを削除する。
	 *
	 * @param string $key セッションキー
	 * @return bool 常に `true` を返します。
	 */
	public function delete_uploadfile( $key ){
		if( !isset($_SESSION['FILE']) ){
			return true;
		}
		unset( $_SESSION['FILE'][$key] );
		return true;
	}
	/**
	 * セッションに保存されたファイルを全て削除する。
	 *
	 * @return bool 常に `true` を返します。
	 */
	public function delete_uploadfile_all(){
		return $this->delete_session( 'FILE' );
	}


	// ----- utils -----

	/**
	 * USER_AGENT を取得する。
	 *
	 * @return string USER_AGENT
	 */
	public function get_user_agent(){
		return $this->conf->server['HTTP_USER_AGENT'] ?? null;
	}

	/**
	 * リクエストパスを取得する。
	 *
	 * @return string リクエストパス
	 */
	public function get_request_file_path(){
		return $this->request_file_path;
	}

	/**
	 *  SSL通信か調べる
	 *
	 * @return bool SSL通信の場合 `true`、それ以外の場合 `false` を返します。
	 */
	public function is_ssl(){
		if( ($this->conf->server['HTTP_SSL'] ?? null) || ($this->conf->server['HTTPS'] ?? null) ){
			// SSL通信が有効か否か判断
			return true;
		}
		return false;
	}

	/**
	 * コマンドラインによる実行か確認する。
	 *
	 * @return bool コマンドからの実行の場合 `true`、ウェブからの実行の場合 `false` を返します。
	 */
	public function is_cmd(){
		if( isset( $this->conf->server['REMOTE_ADDR'] ) ){
			return false;
		}
		return true;
	}


	// ----- private -----

	/**
	 * 受け取ったテキストを、指定の文字セットに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $encode 変換後の文字セット。省略時、`mb_internal_encoding()` から取得
	 * @param string $encodefrom 変換前の文字セット。省略時、自動検出
	 * @return string 文字セット変換後のテキスト
	 */
	private static function convert_encoding( $text, $encode = null, $encodefrom = null ){
		if( !is_callable( 'mb_internal_encoding' ) ){
			return $text;
		}
		if( !strlen( $encodefrom ?? '' ) ){
			$encodefrom = mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII';
		}
		if( !strlen( $encode ?? '' ) ){
			$encode = mb_internal_encoding();
		}

		if( is_array( $text ) ){
			$rtn = array();
			if( !count( $text ) ){
				return $text;
			}
			$TEXT_KEYS = array_keys( $text );
			foreach( $TEXT_KEYS as $Line ){
				$KEY = mb_convert_encoding( $Line , $encode , $encodefrom );
				if( is_array( $text[$Line] ) ){
					$rtn[$KEY] = self::convert_encoding( $text[$Line] ?? array() , $encode , $encodefrom );
				}else{
					$rtn[$KEY] = mb_convert_encoding( $text[$Line] ?? '' , $encode , $encodefrom );
				}
			}
		}else{
			if( !strlen( $text ?? '' ) ){
				return $text;
			}
			$rtn = mb_convert_encoding( $text ?? '' , $encode , $encodefrom );
		}
		return $rtn;
	}

	/**
	 * クォートされた文字列のクォート部分を取り除く。
	 *
	 * この関数は、PHPの `stripslashes()` のラッパーです。
	 * 配列を受け取ると再帰的に文字列を変換して返します。
	 *
	 * @param mixed $text テキスト
	 * @return string クォートが元に戻されたテキスト
	 */
	private static function stripslashes( $text ){
		if( is_array( $text ) ){
			// 配列なら
			foreach( $text as $key=>$val ){
				$text[$key] = self::stripslashes( $val );
			}
		}elseif( is_string( $text ) ){
			// 文字列なら
			$text = stripslashes( $text );
		}
		return $text;
	}

	/**
	 * カレントディレクトリのパスを取得
	 * @return string ドキュメントルートからのパス(スラッシュ閉じ)
	 */
	private function get_path_current_dir(){
		//  環境変数から自動的に判断。
		$rtn = dirname( $this->conf->server['SCRIPT_NAME'] );
		if( !array_key_exists( 'REMOTE_ADDR' , $this->conf->server ) ){
			//  CUIから起動された場合
			//  ドキュメントルートが判定できないので、
			//  ドキュメントルート直下にあるものとする。
			$rtn = '/';
		}
		$rtn = str_replace('\\','/',$rtn);
		$rtn .= ($rtn!='/'?'/':'');
		return $rtn;
	}

}
?><?php
namespace renconFramework;

/**
 * data.PHP Helper
 */
class dataDotPhp{

	private static $src_header = '<'.'?php header(\'HTTP/1.1 404 Not Found\'); echo(\'404 Not Found\');exit(); ?'.'>'."\n";

	/**
	 * JSON.PHP を読み込む
	 */
	static public function read_json( $realpath ){
		if( !is_file($realpath) ){
			return false;
		}
		$jsonDotPhp = file_get_contents($realpath);
		$jsonDotPhp = preg_replace('/^.*?exit\(\)\;\s*\?\>\s*/is', '', $jsonDotPhp);
		$json = json_decode($jsonDotPhp);
		return $json;
	}

	/**
	 * JSON.PHP を保存する
	 */
	static public function write_json( $realpath, $content ){
		$jsonString = json_encode($content, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		$jsonDotPhp = self::$src_header.$jsonString;
		$result = file_put_contents( $realpath, $jsonDotPhp );
		return $result;
	}

	/**
	 * data.PHP にデータを保存する
	 */
	static public function write( $realpath, $text ){
		$result = file_put_contents( $realpath, self::$src_header.$text );
		return $result;
	}

	/**
	 * data.PHP にデータを追記する
	 */
	static public function write_a( $realpath, $text ){
		if( !is_file($realpath) ){
			error_log( self::$src_header, 3, $realpath );
		}
		error_log( $text, 3, $realpath );
		return true;
	}
}
?><?php
namespace renconFramework;

/**
 * ログ管理機能
 */
class logger{

	/** $renconオブジェクト */
	private $rencon;

	/** ログディレクトリ */
	private $realpath_logs;

	/**
	 * Constructor
	 *
	 * @param object $rencon $renconオブジェクト
	 */
	public function __construct( $rencon ){
		$this->rencon = $rencon;

		// ログディレクトリ
		$this->realpath_logs = $this->rencon->realpath_private_data_dir('/logs/');
		if( strlen($this->realpath_logs ?? '') && !$this->rencon->fs()->is_dir($this->realpath_logs) ){
			$this->rencon->fs()->mkdir_r($this->realpath_logs);
		}
	}


	/**
	 * メッセージを記録する
	 */
	public function log(){
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		$arg_list = func_get_args();
	
		$message = '';
		if( count($arg_list) == 1 && is_string($arg_list[0]) ){
			$message = $arg_list[0];
		}elseif( count($arg_list) == 1 ){
			$message = json_encode($arg_list[0]);
		}else{
			$message = json_encode($arg_list);
		}

		$remote_addr = null;
		if( isset($_SERVER["REMOTE_ADDR"]) ){
			$remote_addr = $_SERVER["REMOTE_ADDR"];
		}

		$log = array(
			date('c'), // 時刻
			getmypid(), // プロセスID
			$this->rencon->req()->get_session('ADMIN_USER_ID'), // ログインユーザーID (未ログイン時は null)
			$message, // ログメッセージ
			$trace[0]['file'], // 呼び出したスクリプトファイル
			$trace[0]['line'], // 呼び出した行番号
			$remote_addr, // IPアドレス
		);

		dataDotPhp::write_a( $this->realpath_logs.'log-'.date('Y-m-d').'.csv.php', $this->rencon->fs()->mk_csv( array($log) ) );
		return;
	}

	/**
	 * エラーメッセージを記録する
	 */
	public function error_log(){
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		$arg_list = func_get_args();
	
		$message = '';
		if( count($arg_list) == 1 && is_string($arg_list[0]) ){
			$message = $arg_list[0];
		}elseif( count($arg_list) == 1 ){
			$message = json_encode($arg_list[0]);
		}else{
			$message = json_encode($arg_list);
		}

		$remote_addr = null;
		if( isset($_SERVER["REMOTE_ADDR"]) ){
			$remote_addr = $_SERVER["REMOTE_ADDR"];
		}

		$log_datetime = date('c');
		$log_date = date('Y-m-d');

		$log = array(
			$log_datetime, // 時刻
			getmypid(), // プロセスID
			$this->rencon->req()->get_session('ADMIN_USER_ID'), // ログインユーザーID (未ログイン時は null)
			$message, // ログメッセージ
			$trace[0]['file'], // 呼び出したスクリプトファイル
			$trace[0]['line'], // 呼び出した行番号
			$remote_addr, // IPアドレス
		);
		dataDotPhp::write_a( $this->realpath_logs.'errorlog-'.$log_date.'.csv.php', $this->rencon->fs()->mk_csv( array($log) ) );

		$log = array(
			$log_datetime, // 時刻
			getmypid(), // プロセスID
			$this->rencon->req()->get_session('ADMIN_USER_ID'), // ログインユーザーID (未ログイン時は null)
			'Error: '.$message, // ログメッセージ
			$trace[0]['file'], // 呼び出したスクリプトファイル
			$trace[0]['line'], // 呼び出した行番号
			$remote_addr, // IPアドレス
		);
		dataDotPhp::write_a( $this->realpath_logs.'log-'.$log_date.'.csv.php', $this->rencon->fs()->mk_csv( array($log) ) );
		return;
	}

}
?><?php
namespace renconFramework;

/**
 * initializer
 */
class initializer {

	/** renconオブジェクト */
	private $rencon;

	/** 管理データ定義ディレクトリ */
	private $realpath_private_data_dir;

	/**
	 * Constructor
	 *
	 * @param object $rencon $renconオブジェクト
	 */
	public function __construct( $rencon ){
		$this->rencon = $rencon;
		$this->realpath_private_data_dir = $rencon->conf()->realpath_private_data_dir;
	}


	/**
	 * 初期化プロセス
	 */
	public function initialize(){
		if( is_string($this->realpath_private_data_dir) && strlen($this->realpath_private_data_dir) ){
			if( !is_dir($this->realpath_private_data_dir) ){
				$this->rencon->fs()->mkdir_r($this->realpath_private_data_dir);
			}

			if( !is_file($this->realpath_private_data_dir.'.htaccess') ){
				ob_start(); ?>
RewriteEngine off
Deny from all
<?php
				$src_htaccess = ob_get_clean();
				$this->rencon->fs()->save_file( $this->realpath_private_data_dir.'.htaccess', $src_htaccess );
			}

			// 管理ユーザーデータ
			if( !is_dir($this->realpath_private_data_dir.'admin_users/') ){
				$this->rencon->fs()->mkdir_r($this->realpath_private_data_dir.'admin_users/');
			}
			if( !is_dir($this->realpath_private_data_dir.'admin_users/') || !count( $this->rencon->fs()->ls($this->realpath_private_data_dir.'admin_users/') ) ){
				$this->initialize_admin_user_page();
				exit;
			}
		}
	}

	/**
	 * 管理ユーザーデータを初期化する画面
	 */
	private function initialize_admin_user_page(){
		$result = (object) array(
			"result" => null,
			"message" => null,
			"errors" => (object) array(),
		);
		if( $this->rencon->req()->get_method() == 'post' ){
			$user_info = array(
				'name' => $this->rencon->req()->get_param('ADMIN_USER_NAME'),
				'id' => $this->rencon->req()->get_param('ADMIN_USER_ID'),
				'pw' => $this->rencon->req()->get_param('ADMIN_USER_PW'),
				'lang' => $this->rencon->req()->get_param('ADMIN_USER_LANG'),
				'email' => $this->rencon->req()->get_param('admin_user_email'),
				'role' => 'admin',
			);
			$result = $this->rencon->auth()->create_admin_user( $user_info );
			if( $result->result ){
				header('Location:'.'?a=');
				exit;
			}
		}

		header('Content-type: text/html');
		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="robots" content="nofollow, noindex, noarchive" />
		<?= $this->mk_css() ?>
	</head>
	<body>
		<div class="theme-container">
			<h1><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></h1>
			<?php if( strlen($result->message ?? '') ){ ?>
				<div class="alert alert-danger" role="alert">
					<div><?= htmlspecialchars($result->message) ?></div>
				</div>
			<?php } ?>

			<form action="?" method="post">
<table>
	<tr>
		<th>Name:</th>
		<td><input type="text" name="ADMIN_USER_NAME" value="<?= htmlspecialchars($this->rencon->req()->get_param('ADMIN_USER_NAME') ?? '') ?>" />
			<?php if( strlen( $result->errors->name[0] ?? '' ) ){ ?>
			<p><?= htmlspecialchars( $result->errors->name[0] ?? '' ) ?></p>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th>ID:</th>
		<td><input type="text" name="ADMIN_USER_ID" value="<?= htmlspecialchars($this->rencon->req()->get_param('ADMIN_USER_ID') ?? '') ?>" />
			<?php if( strlen( $result->errors->id[0] ?? '' ) ){ ?>
			<p><?= htmlspecialchars( $result->errors->id[0] ?? '' ) ?></p>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th>Password:</th>
		<td><input type="password" name="ADMIN_USER_PW" value="" />
			<?php if( strlen( $result->errors->pw[0] ?? '' ) ){ ?>
			<p><?= htmlspecialchars( $result->errors->pw[0] ?? '' ) ?></p>
			<?php } ?>
		</td>
	</tr>
</table>
<p><button type="submit">Create User</button></p>
<input type="hidden" name="a" value="<?= htmlspecialchars($this->rencon->req()->get_param('a') ?? '') ?>" />
			</form>
		</div>
	</body>
</html>
<?php
		$rtn = ob_get_clean();
		print $rtn;
		exit;
	}

	/**
	 * CSSを生成
	 */
	private function mk_css(){
		ob_start();?>
		<style>
			html, body {
				background-color: #e7e7e7;
				color: #333;
				font-size: 16px;
				margin: 0;
				padding: 0;
			}
			body,input,textarea,select,option,button{
				font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif, system-ui;
			}
			.theme-container {
				box-sizing: border-box;
				text-align: center;
				padding: 4em 20px;
				margin: 30px auto;
				width: calc(100% - 20px);
				max-width: 600px;
				background-color: #f6f6f6;
				border: 1px solid #bbb;
				border-radius: 5px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.1);
			}
			h1 {
				font-size: 22px;
			}
			table{
				margin: 0 auto;
				max-width: 100%;
			}
			th {
				text-align: right;
				padding: 3px;
			}
			td {
				text-align: left;
				padding: 3px;
			}
			input[type=text],
			input[type=password]{
				display: inline-block;
				box-sizing: border-box;
				width: 160px;
				min-width: 50px;
				max-width: 100%;
				padding: .375rem .75rem;
				font-size: 1em;
				font-weight: normal;
				line-height: 1.5;
				color: #333;
				background-color: #f6f6f6;
				background-clip: padding-box;
				border: 1px solid #ced4da;
				border-radius: .25rem;
				transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
			}
			input[type=text]:focus,
			input[type=password]:focus{
				color: #333;
				background-color: #fff;
				border-color: #80bdff;
				outline: 0;
				box-shadow: 0 0 0 .2rem rgba(0,123,255,.25);
			}

			button {
				display: inline-block;
				border-radius: 3px;
				background-color: #f5fbfe;
				color: #00a0e6;
				border: 1px solid #00a0e6;
				box-shadow: 0 2px 0px rgba(0,0,0,0.1);
				padding: 0.5em 2em;
				font-size:1em;
				font-weight: normal;
				line-height: 1;
				text-decoration: none;
				text-align: center;
				cursor: pointer;
				box-sizing: border-box;
				align-items: stretch;
				transition:
					color 0.1s,
					background-color 0.1s,
					transform 0.1s
				;
			}
			button:focus,
			button:hover{
				background-color: #d9f1fb;
			}
			button:hover{
				background-color: #ccecfa;
			}
			button:active{
				background-color: #00a0e6;
				color: #fff;
			}

		</style>

		<?php
		$src = ob_get_clean();
		return $src;
	}

}
?><?php
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
		if( !is_null( $conf->users ?? null ) ){
			$this->users = (array) $conf->users;
		}

		// --------------------------------------
		// $conf->realpath_private_data_dir
		$this->realpath_private_data_dir = null;
		if( is_string( $conf->realpath_private_data_dir ?? null ) ){
			$this->realpath_private_data_dir = $this->rencon->fs()->get_realpath($conf->realpath_private_data_dir);
		}

		// --------------------------------------
		// $conf->databases
		$this->databases = null;
		if( !is_null( $conf->databases ?? null ) ){
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
?><?php
namespace renconFramework;

/**
 * user class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class user {
	private $rencon;

	/**
	 * Constructor
	 */
	public function __construct( $rencon ){
		$this->rencon = $rencon;
	}

	/**
	 * ログインしているか
	 */
	public function is_login(){
		$login_id = $this->get_user_id();
		return !!strlen($login_id ?? '');
	}

	/**
	 * ユーザーIDを取得
	 */
	public function get_user_id(){
		$login_id = $this->rencon->req()->get_session($this->rencon->app_id().'_ses_login_id');
		return $login_id;
	}

}
?><?php
/**
 * langbank.php
 */
namespace renconFramework;

/**
 * langbank
 */
class LangBank{

	private $fs;
	private $pathCsv;
	private $options = array();
	private $langDb = array();
	public $defaultLang;
	public $lang;

	/**
	 * constructor
	 */
	public function __construct( $options = array() ){
		$this->options = $options;
		$this->fs = new \renconFramework\filesystem();

		$this->langDb = array();

		$csvAry = array (
  0 => 
  array (
    0 => '',
    1 => 'en',
    2 => 'ja',
    3 => 'zh',
    4 => 'zh-TW',
  ),
  1 => 
  array (
    0 => 'page_title.logout',
    1 => 'Logout',
    2 => 'ログアウト',
    3 => '',
    4 => '',
  ),
  2 => 
  array (
    0 => 'ui_label.ok',
    1 => 'OK',
    2 => 'OK',
    3 => 'OK',
    4 => 'OK',
  ),
  3 => 
  array (
    0 => 'ui_label.save',
    1 => 'Save',
    2 => '保存する',
    3 => '保存',
    4 => '保存',
  ),
  4 => 
  array (
    0 => 'ui_label.cancel',
    1 => 'Cancel',
    2 => 'キャンセル',
    3 => '取消',
    4 => '取消',
  ),
  5 => 
  array (
    0 => 'ui_label.close',
    1 => 'Close',
    2 => '閉じる',
    3 => '关闭',
    4 => '關閉',
  ),
  6 => 
  array (
    0 => 'ui_label.create',
    1 => 'Create',
    2 => '作成する',
    3 => '',
    4 => '',
  ),
  7 => 
  array (
    0 => 'ui_label.finished',
    1 => 'Finished',
    2 => '完了',
    3 => '',
    4 => '',
  ),
  8 => 
  array (
    0 => 'ui_label.anchor',
    1 => 'Anchor',
    2 => 'アンカー',
    3 => '',
    4 => '',
  ),
  9 => 
  array (
    0 => 'ui_label.search',
    1 => 'Search',
    2 => '検索',
    3 => '',
    4 => '',
  ),
  10 => 
  array (
    0 => 'ui_label.back',
    1 => 'Back',
    2 => '戻る',
    3 => '',
    4 => '',
  ),
  11 => 
  array (
    0 => 'ui_label.login',
    1 => 'Login',
    2 => 'ログイン',
    3 => '',
    4 => '',
  ),
  12 => 
  array (
    0 => 'ui_label.remove_this_module',
    1 => 'Remove',
    2 => '削除する',
    3 => '',
    4 => '',
  ),
  13 => 
  array (
    0 => 'ui_label.user_name',
    1 => 'Name',
    2 => 'お名前',
    3 => '',
    4 => '',
  ),
  14 => 
  array (
    0 => 'ui_label.user_id',
    1 => 'User ID',
    2 => 'ユーザーID',
    3 => '',
    4 => '',
  ),
  15 => 
  array (
    0 => 'ui_label.user_pw',
    1 => 'Password',
    2 => 'パスワード',
    3 => '',
    4 => '',
  ),
  16 => 
  array (
    0 => 'ui_label.user_email',
    1 => 'E-mail',
    2 => 'メールアドレス',
    3 => '',
    4 => '',
  ),
  17 => 
  array (
    0 => 'ui_label.user_role',
    1 => 'Role',
    2 => 'ロール',
    3 => '',
    4 => '',
  ),
  18 => 
  array (
    0 => 'ui_message.first_register_an_administrator_account',
    1 => 'First, register an administrator account.',
    2 => 'はじめに、管理者アカウントを登録してください。',
    3 => '',
    4 => '',
  ),
  19 => 
  array (
    0 => 'ui_message.user_id_enable_char',
    1 => 'Single-byte alphanumeric characters, hyphens, and underscores can be used.',
    2 => '半角英数字、ハイフン、アンダースコア が使えます。',
    3 => '',
    4 => '',
  ),
  20 => 
  array (
    0 => 'ui_message.this_module_has_no_options',
    1 => 'This module has no options.',
    2 => 'このモジュールにはオプションが定義されていません。',
    3 => '',
    4 => '',
  ),
  21 => 
  array (
    0 => 'ui_message.sending_data',
    1 => 'Sending data...',
    2 => 'データを送信しています...',
    3 => '',
    4 => '',
  ),
  22 => 
  array (
    0 => 'ui_message.confirm_error',
    1 => 'There is any input errors. Please confirm.',
    2 => '入力エラーがあります。確認してください。',
    3 => '',
    4 => '',
  ),
  23 => 
  array (
    0 => 'ui_message.enter_only_when_changing_the_password',
    1 => 'Enter only when changing the password.',
    2 => 'パスワードを変更する場合のみ入力してください。',
    3 => '',
    4 => '',
  ),
  24 => 
  array (
    0 => 'ui_message.save_completed',
    1 => 'Saving is complete.',
    2 => '保存を完了しました。',
    3 => '',
    4 => '',
  ),
  25 => 
  array (
    0 => 'ui_message.logged_out',
    1 => 'Logged out.',
    2 => 'ログアウトしました。',
    3 => '',
    4 => '',
  ),
  26 => 
  array (
    0 => 'login_error.csrf_token_expired',
    1 => 'Token Expired.',
    2 => '認証トークンが無効か、期限が切れています。',
  ),
  27 => 
  array (
    0 => 'login_error.user_id_is_required',
    1 => 'User ID is required.',
    2 => 'ユーザーIDを入力してください。',
  ),
  28 => 
  array (
    0 => 'login_error.invalid_user_id',
    1 => 'Invalid User ID.',
    2 => 'ユーザーIDの形式が不正です。',
  ),
  29 => 
  array (
    0 => 'login_error.failed',
    1 => 'Failed to login. Incorrect credentials.',
    2 => 'ログインに失敗しました。 認証情報が正しくありません。',
  ),
  30 => 
  array (
    0 => 'login_error.account_locked',
    1 => 'Account is locked. Please try again after a while.',
    2 => 'アカウントがロックされています。しばらく時間をおいてお試しください。',
  ),
  31 => 
  array (
    0 => 'error_message.invalid',
    1 => 'There is a problem with the input content.',
    2 => '入力内容に不備があります。',
    3 => '',
    4 => '',
  ),
  32 => 
  array (
    0 => 'error_message.invalid_user_id',
    1 => 'Malformed User ID.',
    2 => '不正な形式のユーザーIDです。',
    3 => '',
    4 => '',
  ),
  33 => 
  array (
    0 => 'error_message.invalid_email',
    1 => 'Malformed E-mail address.',
    2 => '不正な形式のメールアドレスです。',
    3 => '',
    4 => '',
  ),
  34 => 
  array (
    0 => 'error_message.required',
    1 => 'This is required.',
    2 => '必ず入力してください。',
    3 => '',
    4 => '',
  ),
  35 => 
  array (
    0 => 'error_message.required_select',
    1 => 'This is required.',
    2 => '必ず選択してください。',
    3 => '',
    4 => '',
  ),
  36 => 
  array (
    0 => 'error_message.required_user_id',
    1 => 'User ID is required.',
    2 => 'ユーザーIDを入力してください。',
    3 => '',
    4 => '',
  ),
);

		$langIdx = array();

		foreach( $csvAry as $i1=>$row1 ){
			if($i1 == 0){
				foreach( $csvAry[$i1] as $i2=>$row2 ){
					if($i2 == 0){
						continue;
					}
					if($i2 == 1){
						$this->defaultLang = $csvAry[$i1][$i2];
						$this->lang = $csvAry[$i1][$i2];
					}
					$langIdx[$i2] = $csvAry[$i1][$i2];
				}
			}else{
				$this->langDb[$csvAry[$i1][0]] = array();
				foreach( $csvAry[$i1] as $i2=>$row2 ){
					if($i2 == 0){continue;}
					$this->langDb[$csvAry[$i1][0]][$langIdx[$i2]] = $csvAry[$i1][$i2];
				}
			}
		}
	}

	/**
	 * set Language
	 */
	public function setLang($lang){
		$this->lang = $lang;
		return true;
	}

	/**
	 * get Language
	 */
	public function getLang(){
		return $this->lang;
	}

	/**
	 * get word by key
	 *
	 * @param string $key キー
	 * @param string $bindData バインドデータ(省略可)
	 * @param string $defaultValue デフォルト(キーが未定義だった場合)の戻り値
	 * @return string 設定された言語に対応する文字列
	 */
	public function get($key){
		$bindData = array();
		$defaultValue = '---';

		$args = func_get_args();
		if( count($args) == 2 ){
			if( is_string($args[1]) ){
				$defaultValue = $args[1];
			}else{
				$bindData = $args[1];
			}
		}elseif( count($args) == 3 ){
			$bindData = $args[1];
			$defaultValue = $args[2];
		}

		if( !strlen(''.$defaultValue) ){
			$defaultValue = '---';
		}
		$lang = $this->lang;
		if( !isset($this->langDb[$key][$lang]) || !strlen(''.$this->langDb[$key][$lang]) ){
			$lang = $this->defaultLang;
		}
		$rtn = $defaultValue;
		if( isset($this->langDb[$key][$lang]) && strlen(''.$this->langDb[$key][$lang]) ){
			$rtn = $this->langDb[$key][$lang];
		}
		$data = $this->options['bind'] ?? array();
		foreach( $bindData as $bindDataKey=>$bindDataValue ){
			$data[$bindDataKey] = $bindDataValue;
		}
		$data['_ENV'] = $this;

		// Twig にバインドする
		if( class_exists('\\Twig_Loader_Array') ){
			// Twig ^1.35.3
			$loader = new \Twig_Loader_Array(array(
				'index' => $rtn,
			));
			$twig = new \Twig_Environment($loader);
			$rtn = $twig->render('index', $data);

		}elseif( class_exists('\\Twig\\Loader\\ArrayLoader') ){
			// Twig ^3.0.0
			$loader = new \Twig\Loader\ArrayLoader([
				'index' => $rtn,
			]);
			$twig = new \Twig\Environment($loader);
			$rtn = $twig->render('index', $data);

		}

		return $rtn;
	}

	/**
	 * get word list
	 */
	public function getList(){
		return $this->langDb;
	}

}
?><?php
namespace renconFramework;

/**
 * theme class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class theme{
	private $rencon;
	private $app_info;
	private $current_page_info;

	/**
	 * Constructor
	 */
	public function __construct( $rencon, $app_info, $current_page_info = array() ){
		$this->rencon = $rencon;
		$this->app_info = (object) $app_info;
		$this->current_page_info = (object) $current_page_info;
	}

	/**
	 * ページ情報
	 */
	public function set_current_page_info( $page_info ){
		$this->current_page_info = (object) array_merge((array) $this->current_page_info, (array) $page_info);
		return true;
	}

	/**
	 * アプリケーション情報を取得
	 */
	public function app_info(){
		return $this->app_info;
	}

	/**
	 * ページ情報を取得
	 */
	public function get_current_page_info(){
		return $this->current_page_info;
	}

	/**
	 * テーマにコンテンツを包んで返す
	 */
	public function bind( $content ){
		$action_ary = explode('.', $this->rencon->req()->get_param('a') ?? '');
		if( !is_array($action_ary) || !count($action_ary) ){
			$action_ary[0] = '';
		}
		$class_active['active'] = $action_ary[0];
		$rencon = $this->rencon;

		ob_start();
		?><?php
$app_info = $this->app_info();
$current_page_info = $this->get_current_page_info();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title><?= htmlspecialchars( $app_info->name ) ?> | <?= htmlspecialchars( $current_page_info->title ) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="robots" content="nofollow, noindex, noarchive" />
        <link rel="stylesheet" href="?res=theme.css" />
    </head>
    <body>

<p><a href="?a="><?= htmlspecialchars( $app_info->name ) ?></a></p>

<ul><?php
foreach( $app_info->pages as $pid=>$page_info ){
    echo '<li><a href="?a='.htmlspecialchars($pid).'">'.htmlspecialchars($page_info->title).'</a></li>'."\n";
}

?></ul>

<hr />
<div class="theme-middle">
<h1><?= nl2br( htmlspecialchars( $current_page_info->title ) ) ?></h1>
<div class="contents">
<?= $content ?>
</div>
</div>

<hr />

<?php if( $rencon->auth()->is_login_required() && $rencon->user()->is_login() ) { ?>
<p>
    <a href="?a=logout">Logout</a>
</p>
<?php } ?>

        <script src="?res=theme.js"></script>
    </body>
</html>
<?php
		$rtn = ob_get_clean();

		return $rtn;
	}
}
?><?php
namespace renconFramework;

/**
 * auth class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class auth{
	private $rencon;
	private $app_info;
	private $lb;

	/** 管理ユーザー定義ディレクトリ */
	private $realpath_admin_users;

	/** アカウントロック情報格納ディレクトリ */
	private $realpath_account_lock;

	/** APIキー定義ファイル */
	private $realpath_api_key_json;

	/** CSRFトークンの有効期限 */
	private $csrf_token_expire = 60 * 60;

	/**
	 * Constructor
	 */
	public function __construct( $rencon, $app_info ){
		$this->rencon = $rencon;
		$this->app_info = (object) $app_info;
		$this->lb = new LangBank();

		// 管理ユーザー定義ディレクトリ
		$this->realpath_admin_users = $this->rencon->realpath_private_data_dir('/admin_users/');
		if( is_string($this->realpath_admin_users ?? null) && !is_dir($this->realpath_admin_users) ){
			$this->rencon->fs()->mkdir_r($this->realpath_admin_users);
		}

		// アカウントロック情報格納ディレクトリ
		$this->realpath_account_lock = $this->rencon->realpath_private_data_dir('/account_lock/');
		if( !is_dir($this->realpath_account_lock) ){
			$this->rencon->fs()->mkdir_r($this->realpath_account_lock);
		}

		// APIキー定義ファイル
		$this->realpath_api_key_json = $this->rencon->realpath_private_data_dir('/api_keys.json.php');
	}

	/**
	 * 認証プロセス
	 */
	public function auth(){

		if( !$this->is_login_required() ){
			// ユーザーが設定されていなければ、ログインの評価を行わない。
			return;
		}

		if( $this->is_csrf_token_required() && !$this->is_valid_csrf_token_given() ){
			$this->login_page('csrf_token_expired');
			exit;
		}

		$users = (array) $this->rencon->conf()->users;
		$ses_id = $this->rencon->app_id().'_ses_login_id';
		$ses_pw = $this->rencon->app_id().'_ses_login_pw';

		if( $this->rencon->req()->get_param('ADMIN_USER_FLG') ){
			$login_challenger_id = $this->rencon->req()->get_param('ADMIN_USER_ID');
			$login_challenger_pw = $this->rencon->req()->get_param('ADMIN_USER_PW');

			if( !strlen($login_challenger_id ?? '') ){
				// User ID が未指定
				$this->rencon->logger()->error_log('Failed to login. User ID is not set.');
				$this->login_page('user_id_is_required');
				exit;
			}

			if( !$this->validate_admin_user_id($login_challenger_id) ){
				// 不正な形式のID
				$this->rencon->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'. Invalid user ID format.');
				$this->login_page('invalid_user_id');
				exit;
			}

			if( $this->is_account_locked( $login_challenger_id ) ){
				// アカウントがロックされている
				$this->admin_user_login_failed( $login_challenger_id );
				$this->rencon->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'. Account is LOCKED.');
				$this->login_page('account_locked');
				exit;
			}

			$user_info = $this->get_admin_user_info( $login_challenger_id );
			if( !is_object($user_info) ){
				// 不正なユーザーデータ
				$this->admin_user_login_failed( $login_challenger_id );
				$this->rencon->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'. User undefined.');
				$this->login_page('failed');
				exit;
			}
			$admin_id = $user_info->id;
			$admin_pw = $user_info->pw;

			if( strlen($login_challenger_id ?? '') && strlen($login_challenger_pw ?? '') ){
				// ログイン評価
				if( $login_challenger_id == $admin_id && (password_verify($login_challenger_pw, $user_info->pw) || sha1($login_challenger_pw) == $user_info->pw) ){
					$this->rencon->req()->set_session($ses_id, $login_challenger_id);
					$this->rencon->req()->set_session($ses_pw, $user_info->pw);
					header('Location: ?a='.urlencode($this->rencon->req()->get_param('a') ?? ''));
					exit;
				}
			}


			$login_id = $this->rencon->req()->get_session($ses_id);
			$login_pw_hash = $this->rencon->req()->get_session($ses_pw);
			if( strlen($login_id ?? '') && strlen($login_pw_hash ?? '') ){
				// ログイン済みか評価
				if( array_key_exists($login_id, $users) && $users[$login_id] == $login_pw_hash ){
					return;
				}
				$this->rencon->req()->delete_session($ses_id);
				$this->rencon->req()->delete_session($ses_pw);
				$this->rencon->forbidden();
				exit;
			}

			$action = $this->rencon->req()->get_param('a') ?? null;
			if( $action == 'logout' || $action == 'login' ){
				$this->rencon->req()->set_param('a', null);
			}

			$this->admin_user_login_failed( $login_challenger_id );
			$this->rencon->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'.');
			$this->login_page('failed');
			exit;
		}

		if( !$this->is_login() ){
			$this->login_page();
			exit;
		}

		return;
	}

	/**
	 * ログアウトして終了する
	 */
	public function logout(){
		$this->rencon->req()->delete_session($this->rencon->app_id().'_ses_login_id');
		$this->rencon->req()->delete_session($this->rencon->app_id().'_ses_login_pw');



		header('Content-type: text/html');
		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="robots" content="nofollow, noindex, noarchive" />
		<?= $this->mk_css() ?>
	</head>
	<body>
		<div class="theme-container">
			<h1><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></h1>
			<p>Logged out.</p>
			<p><a href="?">Back to Home</a></p>
		</div>
	</body>
</html>
<?php
		$rtn = ob_get_clean();
		print $rtn;
		exit;
	}

	/**
	 * ログインが必要か？
	 */
	public function is_login_required(){
		if( (
				!is_array($this->rencon->conf()->users ?? null)
				&& !is_object($this->rencon->conf()->users ?? null)
			) && (
				is_null($this->rencon->conf()->realpath_private_data_dir)
				|| !is_dir($this->rencon->conf()->realpath_private_data_dir)
			) ){
			return false;
		}
		return true;
	}

	/**
	 * ログインしているか確認する
	 */
	public function is_login(){
		$ses_id = $this->rencon->app_id().'_ses_login_id';
		$ses_pw = $this->rencon->app_id().'_ses_login_pw';

		$ADMIN_USER_ID = $this->rencon->req()->get_session($ses_id);
		$ADMIN_USER_PW = $this->rencon->req()->get_session($ses_pw);
		if( !is_string($ADMIN_USER_ID) || !strlen($ADMIN_USER_ID) ){
			return false;
		}
		if( $this->is_csrf_token_required() && !$this->is_valid_csrf_token_given() ){
			return false;
		}

		$admin_user_info = $this->get_admin_user_info( $ADMIN_USER_ID );
		if( !is_object($admin_user_info) || !isset($admin_user_info->id) ){
			return false;
		}
		if( $ADMIN_USER_ID !=$admin_user_info->id ){
			return false;
		}
		if( $ADMIN_USER_PW != $admin_user_info->pw ){
			return false;
		}
		return true;
	}

	/**
	 * パスワードをハッシュ化する
	 */
	public function password_hash($password){
		if( !is_string($password) ){
			return false;
		}
		return password_hash($password, PASSWORD_BCRYPT);
	}

	/**
	 * ログイン画面を表示して終了する
	 */
	public function login_page( $error_message = null ){
		header('Content-type: text/html');



		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="robots" content="nofollow, noindex, noarchive" />
		<?= $this->mk_css() ?>
	</head>
	<body>
		<div class="theme-container">
			<h1><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></h1>
			<?php if( strlen($this->rencon->req()->get_param('ADMIN_USER_FLG') ?? '') && strlen($error_message ?? '') ){ ?>
				<div class="alert alert-danger" role="alert">
					<div><?= htmlspecialchars($this->lb->get('login_error.'.$error_message) ?? '') ?></div>
				</div>
			<?php } ?>

			<form action="?" method="post">
<table>
	<tr>
		<th>ID:</th>
		<td><input type="text" name="ADMIN_USER_ID" value="" /></td>
	</tr>
	<tr>
		<th>Password:</th>
		<td><input type="password" name="ADMIN_USER_PW" value="" /></td>
	</tr>
</table>
<p><button type="submit">Login</button></p>
<input type="hidden" name="ADMIN_USER_FLG" value="1" />
<input type="hidden" name="CSRF_TOKEN" value="<?= htmlspecialchars($this->get_csrf_token()) ?>" />
<input type="hidden" name="a" value="<?= htmlspecialchars($this->rencon->req()->get_param('a') ?? '') ?>" />
			</form>
		</div>
	</body>
</html>
<?php
		$rtn = ob_get_clean();
		print $rtn;
		exit;
	}


	/**
	 * CSSを生成
	 */
	private function mk_css(){
		ob_start();?>
		<style>
			html, body {
				background-color: #e7e7e7;
				color: #333;
				font-size: 16px;
				margin: 0;
				padding: 0;
			}
			body,input,textarea,select,option,button{
				font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif, system-ui;
			}
			.theme-container {
				box-sizing: border-box;
				text-align: center;
				padding: 4em 20px;
				margin: 30px auto;
				width: calc(100% - 20px);
				max-width: 600px;
				background-color: #f6f6f6;
				border: 1px solid #bbb;
				border-radius: 5px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.1);
			}
			h1 {
				font-size: 22px;
			}
			table{
				margin: 0 auto;
				max-width: 100%;
			}
			th {
				text-align: right;
				padding: 3px;
			}
			td {
				text-align: left;
				padding: 3px;
			}
			input[type=text],
			input[type=password]{
				display: inline-block;
				box-sizing: border-box;
				width: 160px;
				min-width: 50px;
				max-width: 100%;
				padding: .375rem .75rem;
				font-size: 1em;
				font-weight: normal;
				line-height: 1.5;
				color: #333;
				background-color: #f6f6f6;
				background-clip: padding-box;
				border: 1px solid #ced4da;
				border-radius: .25rem;
				transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
			}
			input[type=text]:focus,
			input[type=password]:focus{
				color: #333;
				background-color: #fff;
				border-color: #80bdff;
				outline: 0;
				box-shadow: 0 0 0 .2rem rgba(0,123,255,.25);
			}

			button {
				display: inline-block;
				border-radius: 3px;
				background-color: #f5fbfe;
				color: #00a0e6;
				border: 1px solid #00a0e6;
				box-shadow: 0 2px 0px rgba(0,0,0,0.1);
				padding: 0.5em 2em;
				font-size:1em;
				font-weight: normal;
				line-height: 1;
				text-decoration: none;
				text-align: center;
				cursor: pointer;
				box-sizing: border-box;
				align-items: stretch;
				transition:
					color 0.1s,
					background-color 0.1s,
					transform 0.1s
				;
			}
			button:focus,
			button:hover{
				background-color: #d9f1fb;
			}
			button:hover{
				background-color: #ccecfa;
			}
			button:active{
				background-color: #00a0e6;
				color: #fff;
			}

		</style>

		<?php
		$src = ob_get_clean();
		return $src;
	}


	// --------------------------------------
	// アカウントロック制御

	/**
	 * 管理ユーザーアカウントがロックされているか確認する
	 */
	private function is_account_locked( $user_id ){
		$realpath_json_php = $this->realpath_account_lock.urlencode($user_id).'.json.php';
		$data = new \stdClass;
		if( is_file($realpath_json_php) ){
			$data = dataDotPhp::read_json($realpath_json_php);
		}

		if( !is_array($data->failed_log ?? null) ){
			return false;
		}

		$counter = 0;
		foreach( $data->failed_log as $log ){
			$time = strtotime( $log->at );
			if( $time > time() - (60 * 60) ){
				// 60分以内の失敗ログがあればカウントする
				$counter ++;
			}
			if( $counter >= 5 ){
				// 失敗ログ 5回 でロックする
				return true;
			}
		}

		return false;
	}

	/**
	 * 管理ユーザーがログインに失敗したことを記録する
	 */
	private function admin_user_login_failed( $user_id ){
		$realpath_json_php = $this->realpath_account_lock.urlencode($user_id).'.json.php';
		$data = new \stdClass;
		if( is_file($realpath_json_php) ){
			$data = dataDotPhp::read_json($realpath_json_php);
		}

		if( !is_array($data->failed_log ?? null) ){
			$data->last_failed = null;
			$data->failed_log = array();
		}
		$failed_date = date('c');
		$data->last_failed = $failed_date;
		array_push($data->failed_log, (object) array(
			"at" => $failed_date,
			"client_ip" => $_SERVER['REMOTE_ADDR'] ?? null,
		));

		$result = dataDotPhp::write_json($realpath_json_php, $data);
		return true;
	}

	/**
	 * 管理ユーザーがログインに成功したことを記録する
	 */
	private function admin_user_login_successful( $user_id ){
		$realpath_json_php = $this->realpath_account_lock.urlencode($user_id).'.json.php';
		$this->rencon->fs()->rm( $realpath_json_php );
		return true;
	}


	// --------------------------------------
	// 管理ユーザーの作成画面

	/**
	 * 管理ユーザーを作成する
	 *
	 * @param array|object $user_info 作成するユーザー情報
	 */
	public function create_admin_user( $user_info ){
		$result = (object) array(
			'result' => true,
			'message' => 'OK',
			'errors' => (object) array(),
		);
		$user_info = (object) $user_info;

		$user_info_validated = $this->validate_admin_user_info($user_info);
		if( !$user_info_validated->is_valid ){
			// 不正な形式のユーザー情報
			return (object) array(
				'result' => false,
				'message' => $user_info_validated->message,
				'errors' => $user_info_validated->errors,
			);
		}

		if( $this->admin_user_data_exists( $user_info->id ) ){
			return (object) array(
				'result' => false,
				'message' => 'そのユーザーIDはすでに存在します。',
				'errors' => (object) array(),
			);
		}

		$user_info->pw = $this->password_hash($user_info->pw);
		if( !$this->write_admin_user_data($user_info->id, $user_info) ){
			return (object) array(
				'result' => false,
				'message' => 'ユーザー情報の保存に失敗しました。',
				'errors' => (object) array(),
			);
		}
		return $result;
	}

	/**
	 * 管理者ユーザーの情報を取得する
	 *
	 * このメソッドの戻り値には、パスワードハッシュが含まれます。
	 */
	private function get_admin_user_info($user_id){
		if( !$this->validate_admin_user_id($user_id) ){
			// 不正な形式のID
			return null;
		}

		// config に定義されたユーザーでログインを試みる
		$users = (array) $this->rencon->conf()->users;
		if( is_string($users[$user_id] ?? null) ){
			return (object) array(
				"name" => $user_id,
				"id" => $user_id,
				"pw" => $users[$user_id],
				"lang" => null,
				"email" => null,
				"role" => "admin",
			);
		}elseif( is_array($users[$user_id] ?? null) || is_object($users[$user_id] ?? null) ){
			return (object) $users[$user_id];
		}

		// ユーザーディレクトリにセットされたユーザーで試みる
		$user_info = null;
		if( strlen($this->realpath_admin_users ?? '') && is_dir($this->realpath_admin_users) && $this->rencon->fs()->ls($this->realpath_admin_users) ){
			if( $this->admin_user_data_exists( $user_id ) ){
				$user_info = $this->read_admin_user_data( $user_id );
				if( !isset($user_info->id) || $user_info->id != $user_id ){
					// ID値が不一致だったら
					return null;
				}
			}
		}
		return is_null($user_info) ? $user_info : (object) $user_info;
	}

	/**
	 * Validation: ユーザーID
	 */
	private function validate_admin_user_id( $user_id ){
		if( !is_string($user_id) || !strlen($user_id) ){
			return false;
		}
		if( !preg_match('/^[a-zA-Z0-9\_\-]+$/', $user_id) ){
			// 不正な形式
			return false;
		}
		return true;
	}

	/**
	 * Validation: ユーザー情報
	 */
	private function validate_admin_user_info( $user_info ){
		$rtn = (object) array(
			'is_valid' => true,
			'message' => null,
			'errors' => (object) array(),
		);
		$user_info = (object) $user_info;

		if( !strlen($user_info->id ?? '') ){
			// IDが未指定
			$rtn->is_valid = false;
			$rtn->errors->id = array('User ID is required.');
		}elseif( !$this->validate_admin_user_id($user_info->id) ){
			// 不正な形式のID
			$rtn->is_valid = false;
			$rtn->errors->id = array('Malformed User ID.');
		}
		if( !isset($user_info->name) || !strlen($user_info->name) ){
			$rtn->is_valid = false;
			$rtn->errors->name = array('This is required.');
		}
		if( !isset($user_info->pw) || !strlen($user_info->pw) ){
			$rtn->is_valid = false;
			$rtn->errors->pw = array('This is required.');
		}
		if( isset($user_info->email) && is_string($user_info->email) && strlen($user_info->email) ){
			if( !preg_match('/^[^@\/\\\\]+\@[^@\/\\\\]+$/', $user_info->email) ){
				$rtn->is_valid = false;
				$rtn->errors->email = array('Malformed E-mail address.');
			}
		}
		if( $rtn->is_valid ){
			$rtn->message = 'OK';
		}else{
			$rtn->message = 'There is a problem with the input content.';
		}
		return $rtn;
	}

	/**
	 * 管理ユーザーデータファイルが存在するか確認する
	 */
	private function admin_user_data_exists( $user_id ){
		$realpath_json = $this->realpath_admin_users.urlencode($user_id).'.json';
		$realpath_json_php = $realpath_json.'.php';
		if( is_file( $realpath_json ) || is_file($realpath_json_php) ){
			return true;
		}
		return false;
	}


	// --------------------------------------
	// 管理ユーザーデータファイルの読み書き

	/**
	 * 管理ユーザーデータファイルの書き込み
	 */
	private function write_admin_user_data( $user_id, $data ){
		$realpath_json = $this->realpath_admin_users.urlencode($user_id).'.json';
		$realpath_json_php = $realpath_json.'.php';
		$result = dataDotPhp::write_json($realpath_json_php, $data);
		if( !$result ){
			return false;
		}
		$this->rencon->fs()->chmod_r($this->realpath_admin_users, 0700, 0700);

		if( is_file($realpath_json) ){
			unlink($realpath_json); // 素のJSONがあったら削除する
		}
		return $result;
	}

	/**
	 * 管理ユーザーデータファイルの読み込み
	 */
	private function read_admin_user_data( $user_id ){
		$realpath_json = $this->realpath_admin_users.urlencode($user_id).'.json';
		$realpath_json_php = $realpath_json.'.php';
		if( is_file($realpath_json_php) ){
			$data = dataDotPhp::read_json($realpath_json_php);
			return $data;
		}
		if( is_file($realpath_json) ){
			$data = json_decode(file_get_contents($realpath_json));
			return $data;
		}
		return false;
	}


	// --------------------------------------
	// APIキー

	/**
	 * APIキーが有効か？
	 */
	public function is_valid_api_key( $api_key ){
		$api_key_attribute = $this->get_api_key_attributes( $api_key );
		if( $api_key_attribute === false ){
			return false;
		}
		return true;
	}

	/**
	 * APIキーに与えられた属性情報を取得する
	 */
	public function get_api_key_attributes( $api_key ){

		// config に定義されたAPIキーでログインを試みる
		$api_keys = (array) $this->rencon->conf()->api_keys;
		if( is_array($api_keys[$api_key] ?? null) || is_object($api_keys[$api_key] ?? null) ){
			return (object) $api_keys[$api_key];
		}

		// ユーザーディレクトリにセットされたユーザーで試みる
		if( strlen($this->realpath_api_key_json ?? '') && is_file($this->realpath_api_key_json) ){
			$api_keys = dataDotPhp::read_json($this->realpath_api_key_json);
			if( is_object($api_keys->{$api_key} ?? null) ){
				return (object) $api_keys->{$api_key};
			}
		}
		return false;
	}


	// --------------------------------------
	// CSRFトークン

	/**
	 * CSRFトークンを取得する
	 */
	public function get_csrf_token(){
		$CSRF_TOKEN = $this->rencon->req()->get_session('CSRF_TOKEN');
		if( !is_array($CSRF_TOKEN) ){
			$CSRF_TOKEN = array();
		}
		if( !count($CSRF_TOKEN) ){
			return $this->create_csrf_token();
		}
		foreach( $CSRF_TOKEN as $token ){
			if( $token['created_at'] < time() - ($this->csrf_token_expire / 2) ){
				continue; // 有効期限が切れていたら評価できない
			}
			return $token['hash'];
		}
		return $this->create_csrf_token();
	}

	/**
	 * 新しいCSRFトークンを発行する
	 */
	private function create_csrf_token(){
		$CSRF_TOKEN = $this->rencon->req()->get_session('CSRF_TOKEN');
		if( !is_array($CSRF_TOKEN) ){
			$CSRF_TOKEN = array();
		}

		$id = $this->rencon->req()->get_param('ADMIN_USER_ID');
		$rand = uniqid('clover'.$id, true);
		$hash = md5( $rand );
		array_push($CSRF_TOKEN, array(
			'hash' => $hash,
			'created_at' => time(),
		));
		$this->rencon->req()->set_session('CSRF_TOKEN', $CSRF_TOKEN);
		return $hash;
	}

	/**
	 * CSRFトークンの検証を行わない条件を調査
	 */
	private function is_csrf_token_required(){
		if( $_SERVER['REQUEST_METHOD'] == 'GET' ){
			// PXコマンドなしのGETのリクエストでは、CSRFトークンを要求しない
			return false;
		}
		return true;
	}

	/**
	 * 有効なCSRFトークンを受信したか
	 */
	public function is_valid_csrf_token_given(){

		$csrf_token = $this->rencon->req()->get_param('CSRF_TOKEN');
		if( !$csrf_token ){
			$headers = getallheaders();
			foreach($headers as $header_name=>$header_val){
				if( strtolower($header_name) == 'x-px2-clover-admin-csrf-token' ){
					$csrf_token = $header_val;
					break;
				}
			}
		}
		if( !$csrf_token ){
			return false;
		}

		$CSRF_TOKEN = $this->rencon->req()->get_session('CSRF_TOKEN');
		if( !is_array($CSRF_TOKEN) ){
			$CSRF_TOKEN = array();
		}
		foreach( $CSRF_TOKEN as $token ){
			if( $token['created_at'] < time() - $this->csrf_token_expire ){
				continue; // 有効期限が切れていたら評価できない
			}
			if( $token['hash'] == $csrf_token ){
				return true;
			}
		}

		return false;
	}
}
?><?php

namespace app01;

class api_test {
	static public function test001( $rencon ){
		$rtn = array();
		$rtn['result'] = true;
		$rtn['test001'] = 'test001';
		return $rtn;
	}
	static public function test_route_param( $rencon ){
		$rtn = array();
		$rtn['result'] = true;
		$rtn['routeParam1'] = $rencon->get_route_param('routeParam1');
		return $rtn;
	}
}
?><?php

namespace app01;

class dinamicRoute {
    static public function start( $rencon ){
        echo "<p>dinamicRoute::start()</p>"."\n";
        echo "<pre>"."\n";
        var_dump($rencon->get_route_params());
        var_dump($rencon->get_route_param('routeParam1'));
        echo "</pre>"."\n";
        return;
    }
}
?><?php
namespace app01\middleware;
class sample {
    public function middleware( $rencon ){
        if( !$rencon->req()->get_param('middleware') ){
            return;
        }


        ob_start();
?>

<p>パラメータ <code>middleware</code> に <code><?= htmlspecialchars($rencon->req()->get_param('middleware')) ?></code> がセットされました。</p>

<?php
        $src = ob_get_clean();
        echo $rencon->theme()->bind($src);
        exit;
    }
}
?><?php

namespace app01;

class test {
    static public function start( $rencon ){
        ?>
        <p>test::start()</p>
        <form action="?a=test.post" method="post">
            <input type="hidden" name="CSRF_TOKEN" value="<?= htmlspecialchars($rencon->auth()->get_csrf_token()) ?>" />
            <button type="submit">test.post</button>
        </form>
        <?php
        return;
    }
    static public function post( $rencon ){
        echo "test::post()"."\n";
        return;
    }
    static public function api_preview($rencon){
        ?>
        <script>
        function sendApiRequest(apiName, apiKey){
            fetch('?api='+apiName, {
                method: 'post',
                headers: {
                    'X-API-KEY': apiKey,
                }
            });
            return;
        }
        </script>
        <p><button type="button" onclick="sendApiRequest('api.test.test001', 'zzzzzzzzzzz-zzzzzzzzz-zzzzzzzzz');">api.test.test001</button></p>
        <p><button type="button" onclick="sendApiRequest('api.test.aaaaaa', 'xxxxx-xxxxx-xxxxxxxxxxx-xxxxxxx');">api.test.aaaaaa</button></p>
        <?php
        return;
    }
}
?><?php
// php/sample.php
?><?php
namespace tomk79;
// This is a dummy library.

class filesystem {}
?><?php
// vendor/tomk79/filesystem/files/sample2.php
?><?php
namespace renconFramework;

/**
 * resourceMgr class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class resources{
	private $main;

	/**
	 * Constructor
	 */
	public function __construct( $main ){
		$this->main = $main;
	}

	public function echo_resource( $path ){
		$ext = null;
		if( preg_match('/\.([a-zA-Z0-9\_\-]*)$/', $path, $matched) ){
			$ext = $matched[1];
			$ext = strtolower($ext);
			$mime = $this->get_mime_type($ext);
			if( !$mime ){ $mime = 'text/html'; }
			header('Content-type: '.$mime);
		}
		echo $this->get($path);
		exit;
	}

	/**
	 * リソースを取得
	 */
	public function get( $path ){
		$path = preg_replace( '/$(?:\/*|\.\.?\/)*/', '', $path );

		$resources = array(

'images/sample-gif.gif' => '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',
'images/sample-jpeg.jpg' => '/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAAAKAAD/4QMUaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA2LjAtYzAwMiA3OS4xNjQzNTIsIDIwMjAvMDEvMzAtMTU6NTA6MzggICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjU0RjU0QUVGODlCODExRUE5OEIxOEY2RUQyODNFOUFDIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjU0RjU0QUVFODlCODExRUE5OEIxOEY2RUQyODNFOUFDIiB4bXA6Q3JlYXRvclRvb2w9IjEwLjMuMSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkM0NTVERUU5NkVCQzExRTdCNTFGQUYyQTFGOTk2NTMzIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkM0NTVERUVBNkVCQzExRTdCNTFGQUYyQTFGOTk2NTMzIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+/+4ADkFkb2JlAGTAAAAAAf/bAIQAFBAQGRIZJxcXJzImHyYyLiYmJiYuPjU1NTU1PkRBQUFBQUFERERERERE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',
'images/sample-png.png' => 'iVBORw0KGgoAAAANSUhEUgAAAyAAAAJYCAMAAACtqHJCAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAvtpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTM4IDc5LjE1OTgyNCwgMjAxNi8wOS8xNC0wMTowOTowMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NjcyODBDQTI2RUJEMTFFN0I1MUZBRjJBMUY5OTY1MzMiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NjcyODBDQTE2RUJEMTFFN0I1MUZBRjJBMUY5OTY1MzMiIHhtcDpDcmVhdG9yVG9vbD0iMTAuMy4xIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9IjlCOEI3N0E4NDRCOUM3MDQ1QjdBOUU4MkNFQUYyRTJCIiBzdFJlZjpkb2N1bWVudElEPSI5QjhCNzdBODQ0QjlDNzA0NUI3QTlFODJDRUFGMkUyQiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PrKTQqMAAAGAUExURWRMV/aZXE4QB/VbJZ8hDfNuVK6dnykNC4ygKVAjJHRFNK7SXkxLDnKNIa/VlwcD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',
'test.txt' => 'VGVzdCBUZXh0IEZpbGUuCg==',
'theme.css' => 'aHRtbCwgYm9keSB7CiAgICBiYWNrZ3JvdW5kOiAjZjRmNGY0OwogICAgbWFyZ2luOiAwOwogICAgcGFkZGluZzogMDsKfQppbWcgewogICAgbWF4LXdpZHRoOiAxMDAlOwp9Ci50aGVtZS1taWRkbGUgewogICAgbWFyZ2luOiAwIGF1dG87CiAgICBtYXgtd2lkdGg6IDYwMHB4OwogICAgd2lkdGg6IGNhbGMoMTAwJSAtIDQwcHgpOwp9Cg==',
'theme.js' => 'd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ2xvYWQnLCBmdW5jdGlvbigpewogICAgY29uc29sZS5sb2coJ3dpbmRvdy5vbmxvYWQoKTogZG9uZS4nKTsKfSk7',


		);

		if( !array_key_exists($path, $resources) ){ return false; }
		return base64_decode($resources[$path]);
	}

	/**
	 * 拡張子から mime-type を得る
	 */
	public function get_mime_type($ext){
		switch( $ext ){
			case 'html':
			case 'htm':
				return 'text/html';
				break;
			case 'js':
				return 'text/javascript';
				break;
			case 'css':
			case 'scss':
				return 'text/css';
				break;
			case 'gif':
				return 'image/gif';
				break;
			case 'png':
				return 'image/png';
				break;
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				return 'image/jpeg';
				break;
			case 'svg':
				return 'image/svg+xml ';
				break;
			case 'text':
			case 'txt':
			case 'log':
			case 'sh':
			case 'bat':
			case 'php':
			case 'json':
			case 'yml':
			case 'yml':
			case 'htaccess':
				return 'text/plain';
				break;
		}
		return false;
	}

}
?>