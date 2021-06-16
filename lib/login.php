<?php
namespace renconFramework;

/**
 * login class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class login{
	private $main;
	private $app_id = '<!-- app_id -->';

	/**
	 * Constructor
	 */
	public function __construct( $main ){
		$this->main = $main;
	}

	/**
	 * ログインしているか調べる
	 */
	public function check(){

		if( !$this->main->conf()->is_login_required() ){
			// ユーザーが設定されていなければ、ログインの評価を行わない。
			return true;
		}

		$users = (array) $this->main->conf()->users;

		$login_id = $this->main->req()->get_param('login_id');
		$login_pw = $this->main->req()->get_param('login_pw');
		$login_try = $this->main->req()->get_param('login_try');
		if( strlen( $login_try ) && strlen($login_id) && strlen($login_pw) ){
			// ログイン評価
			if( array_key_exists($login_id, $users) && $users[$login_id] == sha1($login_pw) ){
				$this->main->req()->set_session($this->app_id.'_ses_login_id', $login_id);
				$this->main->req()->set_session($this->app_id.'_ses_login_pw', sha1($login_pw));
				header('Location: ?a='.urlencode($this->main->req()->get_param('a')));
				return true;
			}
		}


		$login_id = $this->main->req()->get_session($this->app_id.'_ses_login_id');
		$login_pw_hash = $this->main->req()->get_session($this->app_id.'_ses_login_pw');
		if( strlen($login_id) && strlen($login_pw_hash) ){
			// ログイン済みか評価
			if( array_key_exists($login_id, $users) && $users[$login_id] == $login_pw_hash ){
				return true;
			}
			$this->main->req()->delete_session($this->app_id.'_ses_login_id');
			$this->main->req()->delete_session($this->app_id.'_ses_login_pw');
			$this->main->forbidden();
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
		<title>rencon</title>
		<meta name="robots" content="nofollow, noindex, noarchive" />
		<link rel="stylesheet" href="?res=bootstrap4/css/bootstrap.min.css" />
		<script src="?res=bootstrap4/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="?res=styles/common.css" />
	</head>
	<body>
		<div class="container">
			<h1>rencon</h1>
			<?php if( strlen($this->main->req()->get_param('login_try')) ){ ?>
				<div class="alert alert-danger" role="alert">
					<div>IDまたはパスワードが違います。</div>
				</div>
			<?php } ?>

			<form action="?" method="post">
ID: <input type="text" name="login_id" value="" class="form-element" />
PW: <input type="password" name="login_pw" value="" class="form-element" />
<input type="submit" value="Login" class="btn btn-primary" />
<input type="hidden" name="login_try" value="1" />
<input type="hidden" name="a" value="<?= htmlspecialchars($this->main->req()->get_param('a')) ?>" />
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
		$this->main->req()->delete_session($this->app_id.'_ses_login_id');
		$this->main->req()->delete_session($this->app_id.'_ses_login_pw');

		header('Content-type: text/html');
		ob_start();
		?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>rencon</title>
		<meta name="robots" content="nofollow, noindex, noarchive" />
		<link rel="stylesheet" href="?res=bootstrap4/css/bootstrap.min.css" />
		<script src="?res=bootstrap4/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="?res=styles/common.css" />
	</head>
	<body>
		<div class="container">
			<h1>rencon</h1>
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
