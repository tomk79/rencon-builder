<?php
namespace renconFramework;

/**
 * rencon: Language Helper
 */
class lang {

	/** renconオブジェクト */
	private $rencon;

	/** ログインユーザー情報 */
	private $loginUserInfo;

	/** LangBankオブジェクト */
	private $lb;

	/**
	 * Constructor
	 *
	 * @param object $rencon $renconオブジェクト
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $rencon ){
		$this->rencon = $rencon;
		// $this->loginUserInfo = $this->rencon->auth()->get_login_user_info(); // TODO: 言語設定を取り出す
		$this->lb = new LangBank();
		if( strlen($this->rencon->req()->get_param('LANG') ?? '') ){
			$this->lb->setLang( $this->rencon->req()->get_param('LANG') );
		}elseif( strlen($this->loginUserInfo->lang ?? '') ){
			$this->lb->setLang( $this->loginUserInfo->lang );
		}else{
			$this->lb->setLang( 'ja' );
		}
	}

	/**
	 * get
	 */
	public function get($key){
		return $this->lb->get($key);
	}

}
?>
