<?php
namespace renconFramework;

/**
 * theme class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class theme{
	private $rencon;
	private $app_info;
	private $current_page_info;

	/**
	 * Constructor
	 */
	public function __construct( $rencon, $app_info, $current_page_info = array() ){
		$this->rencon = $rencon;
		$this->app_info = (object) $app_info;
		$this->current_page_info = (object) $current_page_info;
	}

	/**
	 * ページ情報
	 */
	public function set_current_page_info( $page_info ){
		$this->current_page_info = (object) array_merge((array) $this->current_page_info, (array) $page_info);
		return true;
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
		$action_ary = explode('.', $this->rencon->req()->get_param('a') ?? '');
		if( !is_array($action_ary) || !count($action_ary) ){
			$action_ary[0] = '';
		}
		$class_active['active'] = $action_ary[0];
		$rencon = $this->rencon;

		ob_start();
		?>/* theme template */<?php
		$rtn = ob_get_clean();

		return $rtn;
	}
}
?>
