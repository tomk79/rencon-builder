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
		if( strlen( $login_try ?? '' ) && strlen($login_id ?? '') && strlen($login_pw ?? '') ){
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
		if( strlen($login_id ?? '') && strlen($login_pw_hash ?? '') ){
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
	<tr><th>ID:</th><td><input type="text" name="login_id" value="" /></td>
	<tr><th>Password:</th><td><input type="password" name="login_pw" value="" /></td>
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

}
?>
