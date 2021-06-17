<?php
namespace renconFramework;

/**
 * user class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class user{
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
		return !!strlen($login_id);
	}

	/**
	 * ユーザーIDを取得
	 */
	public function get_user_id(){
		$login_id = $this->rencon->req()->get_session($this->rencon->app_id().'_ses_login_id');
		return $login_id;
	}

}
?>
