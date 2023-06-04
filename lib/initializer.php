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
			$result = $this->clover->auth()->create_admin_user( $user_info );
			if( $result->result ){
				header('Location:'.'?a=');
				exit;
			}
		}
echo "now develop // TODO: initialize page";
return;
		echo $this->clover->view()->bind(
			'/system/initialize_admin_user.twig',
			array(
				'ADMIN_USER_NAME' => $this->rencon->req()->get_param('ADMIN_USER_NAME'),
				'ADMIN_USER_ID' => $this->rencon->req()->get_param('ADMIN_USER_ID'),
				'ADMIN_USER_LANG' => $this->rencon->req()->get_param('ADMIN_USER_LANG') ?? $this->rencon->lang(),
				'admin_user_email' => $this->rencon->req()->get_param('admin_user_email'),
				'message' => $result->message,
				'errors' => $result->errors,
				'url_backto' => '?',
			)
		);
		exit;
	}

}
?>