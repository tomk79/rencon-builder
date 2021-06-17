<?php
namespace renconFramework;

/**
 * login class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class login{
	private $rencon;
	private $app_info;

	/**
	 * Constructor
	 */
	public function __construct( $rencon, $app_info ){
		$this->rencon = $rencon;
		$this->app_info = (object) $app_info;
	}

	/**
	 * ログインしているか調べる
	 */
	public function check(){

		if( !$this->rencon->conf()->is_login_required() ){
			// ユーザーが設定されていなければ、ログインの評価を行わない。
			return true;
		}

		$users = (array) $this->rencon->conf()->users;
		$ses_id = $this->rencon->app_id().'_ses_login_id';
		$ses_pw = $this->rencon->app_id().'_ses_login_pw';

		$login_id = $this->rencon->req()->get_param('login_id');
		$login_pw = $this->rencon->req()->get_param('login_pw');
		$login_try = $this->rencon->req()->get_param('login_try');
		if( strlen( $login_try ) && strlen($login_id) && strlen($login_pw) ){
			// ログイン評価
			if( array_key_exists($login_id, $users) && $users[$login_id] == sha1($login_pw) ){
				$this->rencon->req()->set_session($ses_id, $login_id);
				$this->rencon->req()->set_session($ses_pw, sha1($login_pw));
				header('Location: ?a='.urlencode($this->rencon->req()->get_param('a')));
				return true;
			}
		}


		$login_id = $this->rencon->req()->get_session($ses_id);
		$login_pw_hash = $this->rencon->req()->get_session($ses_pw);
		if( strlen($login_id) && strlen($login_pw_hash) ){
			// ログイン済みか評価
			if( array_key_exists($login_id, $users) && $users[$login_id] == $login_pw_hash ){
				return true;
			}
			$this->rencon->req()->delete_session($ses_id);
			$this->rencon->req()->delete_session($ses_pw);
			$this->rencon->forbidden();
			exit;
		}

		return false;
	}

	/**
	 * ログイン画面を表示して終了する
	 */
	public function please_login(){
		header('Content-type: text/html');
		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ) ?></title>
		<meta name="robots" content="nofollow, noindex, noarchive" />
	</head>
	<body>
		<div class="container">
			<h1><?= htmlspecialchars( $this->app_info->name ) ?></h1>
			<?php if( strlen($this->rencon->req()->get_param('login_try')) ){ ?>
				<div class="alert alert-danger" role="alert">
					<div>IDまたはパスワードが違います。</div>
				</div>
			<?php } ?>

			<form action="?" method="post">
ID: <input type="text" name="login_id" value="" class="form-element" />
PW: <input type="password" name="login_pw" value="" class="form-element" />
<input type="submit" value="Login" class="btn btn-primary" />
<input type="hidden" name="login_try" value="1" />
<input type="hidden" name="a" value="<?= htmlspecialchars($this->rencon->req()->get_param('a')) ?>" />
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

		header('Content-type: text/html');
		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?= htmlspecialchars( $this->app_info->name ) ?></title>
		<meta name="robots" content="nofollow, noindex, noarchive" />
	</head>
	<body>
		<div class="container">
			<h1><?= htmlspecialchars( $this->app_info->name ) ?></h1>
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

}
?>
