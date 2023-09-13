<?php
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
				// $this->clover->logger()->error_log('Failed to login. User ID is not set.');
				$this->login_page('user_id_is_required');
				exit;
			}

			if( !$this->validate_admin_user_id($login_challenger_id) ){
				// 不正な形式のID
				// $this->clover->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'. Invalid user ID format.');
				$this->login_page('invalid_user_id');
				exit;
			}

			if( $this->is_account_locked( $login_challenger_id ) ){
				// アカウントがロックされている
				$this->admin_user_login_failed( $login_challenger_id );
				// $this->clover->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'. Account is LOCKED.');
				$this->login_page('account_locked');
				exit;
			}

			$user_info = $this->get_admin_user_info( $login_challenger_id );
			if( !is_object($user_info) ){
				// 不正なユーザーデータ
				$this->admin_user_login_failed( $login_challenger_id );
				// $this->clover->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'. User undefined.');
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
			// $this->clover->logger()->error_log('Failed to login as user \''.$login_challenger_id.'\'.');
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

/* router:logout */

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

/* router:login */

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
?>
