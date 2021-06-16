<?php
namespace renconFramework;

/**
 * rencon theme class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class theme{
	private $main;
	private $h1 = 'Home';

	/**
	 * Constructor
	 */
	public function __construct( $main ){
		$this->main = $main;
	}

	/**
	 * h1テキストを登録
	 */
	public function set_h1( $h1 ){
		$this->h1 = $h1;
		return true;
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
