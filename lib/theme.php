<?php
namespace renconFramework;

/**
 * theme class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class theme{
	private $main;
	private $login;
	private $app_info;
	private $current_page_info;

	/**
	 * Constructor
	 */
	public function __construct( $main, $login, $app_info, $current_page_info ){
		$this->main = $main;
		$this->login = $login;
		$this->app_info = (object) $app_info;
		$this->current_page_info = (object) $current_page_info;
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
		$action_ary = explode('.', $this->main->req()->get_param('a'));
		if( !is_array($action_ary) || !count($action_ary) ){
			$action_ary[0] = '';
		}
		$class_active['active'] = $action_ary[0];
		$login = $this->login;

		ob_start();
		?>/* theme template */<?php
		$rtn = ob_get_clean();

		return $rtn;
	}
}
?>