<?php
namespace renconFramework;

/**
 * initializer
 */
class initializer {

	/** Cloverオブジェクト */
	private $clover;

	/** Picklesオブジェクト */
	private $px;

	/** 管理データ定義ディレクトリ */
	private $realpath_private_data_dir;

	/**
	 * Constructor
	 *
	 * @param object $clover $cloverオブジェクト
	 */
	public function __construct( $clover ){
		$this->clover = $clover;
		$this->px = $this->clover->px();

		// 管理データ定義ディレクトリ
		$this->realpath_private_data_dir = $this->clover->realpath_private_data_dir();
	}


	/**
	 * 初期化プロセス
	 */
	public function initialize(){
		if( !is_dir($this->realpath_private_data_dir) ){
			$this->px->fs()->mkdir_r($this->realpath_private_data_dir);
		}

		// 管理ユーザーデータ
		if( !is_dir($this->realpath_private_data_dir.'admin_users/') ){
			$this->px->fs()->mkdir_r($this->realpath_private_data_dir.'admin_users/');
		}
		if( !is_dir($this->realpath_private_data_dir.'admin_users/') || !count( $this->px->fs()->ls($this->realpath_private_data_dir.'admin_users/') ) ){
			$this->initialize_admin_user_page();
			exit;
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
		if( $this->px->req()->get_method() == 'post' ){
			$user_info = array(
				'name' => $this->px->req()->get_param('ADMIN_USER_NAME'),
				'id' => $this->px->req()->get_param('ADMIN_USER_ID'),
				'pw' => $this->px->req()->get_param('ADMIN_USER_PW'),
				'lang' => $this->px->req()->get_param('ADMIN_USER_LANG'),
				'email' => $this->px->req()->get_param('admin_user_email'),
				'role' => 'admin',
			);
			$result = $this->clover->auth()->create_admin_user( $user_info );
			if( $result->result ){
				header('Location:'.'?PX=admin');
				exit;
			}
		}
		echo $this->clover->view()->bind(
			'/system/initialize_admin_user.twig',
			array(
				'ADMIN_USER_NAME' => $this->px->req()->get_param('ADMIN_USER_NAME'),
				'ADMIN_USER_ID' => $this->px->req()->get_param('ADMIN_USER_ID'),
				'ADMIN_USER_LANG' => $this->px->req()->get_param('ADMIN_USER_LANG') ?? $this->px->lang(),
				'admin_user_email' => $this->px->req()->get_param('admin_user_email'),
				'message' => $result->message,
				'errors' => $result->errors,
				'url_backto' => '?',
			)
		);
		exit;
	}

}
?>