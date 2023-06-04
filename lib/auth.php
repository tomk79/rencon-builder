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

	/** 管理ユーザー定義ディレクトリ */
	private $realpath_admin_users;

	/**
	 * Constructor
	 */
	public function __construct( $rencon, $app_info ){
		$this->rencon = $rencon;
		$this->app_info = (object) $app_info;

		// 管理ユーザー定義ディレクトリ
		$this->realpath_admin_users = $this->rencon->realpath_private_data_dir('/admin_users/');
		if( !is_dir($this->realpath_admin_users) ){
			$this->rencon->fs()->mkdir_r($this->realpath_admin_users);
		}
	}

	/**
	 * 認証プロセス
	 */
	public function auth(){

		if( !$this->rencon->conf()->is_login_required() ){
			// ユーザーが設定されていなければ、ログインの評価を行わない。
			return;
		}

		$users = (array) $this->rencon->conf()->users;
		$ses_id = $this->rencon->app_id().'_ses_login_id';
		$ses_pw = $this->rencon->app_id().'_ses_login_pw';

		$login_id = $this->rencon->req()->get_param('login_id');
		$login_pw = $this->rencon->req()->get_param('login_pw');
		$login_try = $this->rencon->req()->get_param('login_try');
		if( strlen( $login_try ?? '' ) && strlen($login_id ?? '') && strlen($login_pw ?? '') ){
			// ログイン評価
			if( array_key_exists($login_id, $users) && $users[$login_id] == sha1($login_pw) ){
				$this->rencon->req()->set_session($ses_id, $login_id);
				$this->rencon->req()->set_session($ses_pw, sha1($login_pw));
				header('Location: ?a='.urlencode($this->rencon->req()->get_param('a')));
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
		$this->please_login();
		exit;
	}

	/**
	 * ログイン画面を表示して終了する
	 */
	public function please_login(){
		header('Content-type: text/html');

/* route:login */

		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></title>
		<meta name="robots" content="nofollow, noindex, noarchive" />
		<?= $this->mk_css() ?>
	</head>
	<body>
		<div class="theme-container">
			<h1><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></h1>
			<?php if( strlen($this->rencon->req()->get_param('login_try') ?? '') ){ ?>
				<div class="alert alert-danger" role="alert">
					<div>IDまたはパスワードが違います。</div>
				</div>
			<?php } ?>

			<form action="?" method="post">
<table>
	<tr>
		<th>ID:</th>
		<td><input type="text" name="login_id" value="" /></td>
	</tr>
	<tr>
		<th>Password:</th>
		<td><input type="password" name="login_pw" value="" /></td>
	</tr>
</table>
<p><button type="submit">Login</button></p>
<input type="hidden" name="login_try" value="1" />
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
	 * ログアウトして終了する
	 */
	public function logout(){
		$this->rencon->req()->delete_session($this->rencon->app_id().'_ses_login_id');
		$this->rencon->req()->delete_session($this->rencon->app_id().'_ses_login_pw');

/* route:logout */

		header('Content-type: text/html');
		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ?? '' ) ?></title>
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
	 * 管理ユーザーデータファイルの書き込み
	 */
	private function write_admin_user_data( $user_id, $data ){
		$realpath_json = $this->realpath_admin_users.urlencode($user_id).'.json';
		$realpath_json_php = $realpath_json.'.php';
		$result = dataDotPhp::write_json($realpath_json_php, $data);
		if( !$result ){
			return false;
		}
		if( is_file($realpath_json) ){
			unlink($realpath_json); // 素のJSONがあったら削除する
		}
		return $result;
	}
}
?>