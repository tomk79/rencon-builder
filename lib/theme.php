<?php
namespace renconFramework;

/**
 * theme class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class theme{
	private $main;
	private $app_name;
	private $current_page_info;

	/**
	 * Constructor
	 */
	public function __construct( $main, $current_page_info ){
		$this->main = $main;
		$this->current_page_info = (object) $current_page_info;
	}

	/**
	 * アプリケーション名を取得
	 */
	public function app_name(){
		return $this->app_name;
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
		ob_start();
		?>/* theme template */<?php
		$rtn = ob_get_clean();
		return $rtn;
	}
}
?>
