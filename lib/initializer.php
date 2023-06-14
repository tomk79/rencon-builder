<?php
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
			$result = $this->rencon->auth()->create_admin_user( $user_info ); // TODO: auth()->create_admin_user() は存在しない。実装する。
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