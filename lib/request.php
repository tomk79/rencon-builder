<?php
namespace renconFramework;

/**
 * tomk79/request core class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class request{
	/**
	 * 設定オブジェクト
	 */
	private $conf;
	/**
	 * ファイルシステムオブジェクト
	 */
	private $fs;
	/**
	 * URLパラメータ
	 */
	private $param = array();
	/**
	 * コマンドからのアクセス フラグ
	 */
	private $flg_cmd = false;
	/**
	 * 優先ディレクトリインデックス
	 */
	private $directory_index_primary;
	/**
	 * コマンドラインオプション
	 */
	private $cli_options;
	/**
	 * コマンドラインパラメータ
	 */
	private $cli_params;

	/**
	 * コンストラクタ
	 *
	 * @param object $conf 設定オブジェクト
	 */
	public function __construct($conf=null){
		$this->conf = $conf;
		if( !is_object($this->conf) ){
			$this->conf = json_decode('{}');
		}

		if(!property_exists($this->conf, 'get') || !@is_array($this->conf->get)){
			$this->conf->get = $_GET;
		}
		if(!property_exists($this->conf, 'post') || !@is_array($this->conf->post)){
			$this->conf->post = $_POST;
		}
		if(!property_exists($this->conf, 'files') || !@is_array($this->conf->files)){
			$this->conf->files = $_FILES;
		}
		if(!property_exists($this->conf, 'server') || !@is_array($this->conf->server)){
			$this->conf->server = $_SERVER;
		}
		if( !array_key_exists( 'PATH_INFO' , $this->conf->server ) ){
			$this->conf->server['PATH_INFO'] = null;
		}
		if( !array_key_exists( 'HTTP_USER_AGENT' , $this->conf->server ) ){
			$this->conf->server['HTTP_USER_AGENT'] = null;
		}
		if( !array_key_exists( 'argv' , $this->conf->server ) ){
			$this->conf->server['argv'] = null;
		}
		if(!property_exists($this->conf, 'session_name') || !@strlen($this->conf->session_name)){
			$this->conf->session_name = 'SESSID';
		}
		if(!property_exists($this->conf, 'session_expire') || !@strlen($this->conf->session_expire)){
			$this->conf->session_expire = 1800;
		}
		if(!property_exists($this->conf, 'directory_index_primary') || !@strlen($this->conf->directory_index_primary)){
			$this->conf->directory_index_primary = 'index.html';
		}
		if(!property_exists($this->conf, 'cookie_default_path') || !@strlen($this->conf->cookie_default_path)){
			// クッキーのデフォルトのパス
			// session の範囲もこの設定に従う。
			$this->conf->cookie_default_path = $this->get_path_current_dir();
		}

		$this->parse_input();
		$this->session_start();
	}

	/**
	 *	入力値を解析する。
	 *
	 * `$_GET`, `$_POST`, `$_FILES` に送られたパラメータ情報を取りまとめ、1つの連想配列としてまとめま、オブジェクト内に保持します。
	 *
	 * コマンドラインから実行された場合は、コマンドラインオプションをそれぞれ `=` 記号で区切り、URLパラメータ同様にパースします。
	 *
	 * このメソッドの処理には、入力文字コードの変換(UTF-8へ統一)などの整形処理が含まれます。
	 *
	 * @return bool 常に `true`
	 */
	private function parse_input(){
		$this->cli_params = array();
		$this->cli_options = array();

		if( !array_key_exists( 'REMOTE_ADDR' , $this->conf->server ) ){
			//  コマンドラインからの実行か否か判断
			$this->flg_cmd = true;//コマンドラインから実行しているかフラグ
			if( is_array( $this->conf->server['argv'] ) && count( $this->conf->server['argv'] ) ){
				$tmp_path = null;
				for( $i = 0; count( $this->conf->server['argv'] ) > $i; $i ++ ){
					if( preg_match( '/^\-/', $this->conf->server['argv'][$i] ) ){
						$this->cli_params = array();//オプションの前に引数は付けられない
						$this->cli_options[$this->conf->server['argv'][$i]] = $this->conf->server['argv'][$i+1];
						$i ++;
					}else{
						array_push( $this->cli_params, $this->conf->server['argv'][$i] );
					}
				}
				$tmp_path = @$this->cli_params[count($this->cli_params)-1];
				if( preg_match( '/^\//', $tmp_path ) && @is_array($this->conf->server['argv']) ){
					$tmp_path = array_pop( $this->conf->server['argv'] );
					$tmp_path = parse_url($tmp_path);
					@parse_str( $tmp_path['query'], $query );
					if( is_array($query) ){
						$this->conf->get = array_merge( $this->conf->get, $query );
					}
				}
				unset( $tmp_path );
			}
		}

		if( ini_get('magic_quotes_gpc') ){
			// PHPINIのmagic_quotes_gpc設定がOnだったら、
			// エスケープ文字を削除。
			foreach( array_keys( $this->conf->get ) as $Line ){
				$this->conf->get[$Line] = self::stripslashes( $this->conf->get[$Line] );
			}
			foreach( array_keys( $this->conf->post ) as $Line ){
				$this->conf->post[$Line] = self::stripslashes( $this->conf->post[$Line] );
			}
		}

		$this->conf->get = self::convert_encoding( $this->conf->get );
		$this->conf->post = self::convert_encoding( $this->conf->post );
		$param = array_merge( $this->conf->get , $this->conf->post );
		$param = $this->normalize_input( $param );

		if( is_array( $this->conf->files ) ){
			$FILES_KEYS = array_keys( $this->conf->files );
			foreach($FILES_KEYS as $Line){
				$this->conf->files[$Line]['name'] = self::convert_encoding( $this->conf->files[$Line]['name'] );
				$this->conf->files[$Line]['name'] = mb_convert_kana( $this->conf->files[$Line]['name'] , 'KV' , mb_internal_encoding() );
				$param[$Line] = $this->conf->files[$Line];
			}
		}

		$this->param = $param;
		unset($param);

		return	true;
	}//parse_input()

	/**
	 *	入力値に対する標準的な変換処理
	 *
	 * @param array $param パラメータ
	 * @return array 変換後のパラメータ
	 */
	private function normalize_input( $param ){
		$is_callable_mb_check_encoding = is_callable( 'mb_check_encoding' );
		foreach( $param as $key=>$val ){
			// URLパラメータを加工
			if( is_array( $val ) ){
				// 配列なら
				$param[$key] = $this->normalize_input( $param[$key] );
			}elseif( is_string( $param[$key] ) ){
				// 文字列なら
				$param[$key] = mb_convert_kana( $param[$key] , 'KV' , mb_internal_encoding() );
					// 半角カナは全角に統一
				$param[$key] = preg_replace( '/\r\n|\r|\n/' , "\n" , $param[$key] );
					// 改行コードはLFに統一
				if( $is_callable_mb_check_encoding ){
					// 不正なバイトコードのチェック
					if( !mb_check_encoding( $key , mb_internal_encoding() ) ){
						// キーの中に見つけたらパラメータごと削除
						unset( $param[$key] );
					}
					if( !mb_check_encoding( $param[$key] , mb_internal_encoding() ) ){
						// 値の中に見つけたら false に置き換える
						$param[$key] = false;
					}
				}
			}
		}
		return $param;
	}//normalize_input()

	/**
	 * パラメータを取得する。
	 *
	 * `$_GET`, `$_POST`、`$_FILES` を合わせた連想配列の中から `$key` に当たる値を引いて返します。
	 * キーが定義されていない場合は、`null` を返します。
	 *
	 * @param string $key URLパラメータ名
	 * @return mixed URLパラメータ値
	 */
	public function get_param( $key ){
		if( !array_key_exists($key, $this->param) ){ return null; }
		return @$this->param[$key];
	}//get_param()

	/**
	 * パラメータをセットする。
	 *
	 * @param string $key パラメータ名
	 * @param mixed $val パラメータ値
	 * @return bool 常に `true`
	 */
	public function set_param( $key , $val ){
		$this->param[$key] = $val;
		return true;
	}//set_param()

	/**
	 * パラメータをすべて取得する。
	 *
	 * @return array すべてのパラメータを格納する連想配列
	 */
	public function get_all_params(){
		return $this->param;
	}

	/**
	 * コマンドラインオプションを取得する
	 * @param string $name オプション名
	 * @return string 指定されたオプション値
	 */
	public function get_cli_option( $name ){
		if( !array_key_exists($name, $this->cli_options) ){
			return null;
		}
		return @$this->cli_options[$name];
	}

	/**
	 * すべてのコマンドラインオプションを連想配列で取得する
	 * @return array すべてのコマンドラインオプション
	 */
	public function get_cli_options(){
		return @$this->cli_options;
	}

	/**
	 * コマンドラインパラメータを取得する
	 * @param string $idx パラメータ番号
	 * @return string 指定されたオプション値
	 */
	public function get_cli_param( $idx = 0 ){
		if($idx < 0){
			// マイナスのインデックスが与えられた場合、
			// 配列の最後から数える
			$idx = count($this->cli_params)+$idx;
		}
		return @$this->cli_params[$idx];
	}

	/**
	 * すべてのコマンドラインパラメータを配列で取得する
	 * @return array すべてのコマンドラインパラメータ
	 */
	public function get_cli_params(){
		return @$this->cli_params;
	}



	// ----- cookies -----

	/**
	 * クッキー情報を取得する。
	 *
	 * @param string $key クッキー名
	 * @return mixed クッキーの値
	 */
	public function get_cookie( $key ){
		if( @!is_array( $_COOKIE ) ){ return null; }
		if( @!array_key_exists($key, $_COOKIE) ){ return null; }
		return	@$_COOKIE[$key];
	}//get_cookie()

	/**
	 * クッキー情報をセットする。
	 *
	 * @param string $key クッキー名
	 * @param string $val クッキー値
	 * @param string $expire クッキーの有効期限
	 * @param string $path サーバー上での、クッキーを有効としたいパス
	 * @param string $domain クッキーが有効なドメイン
	 * @param bool $secure クライアントからのセキュアな HTTPS 接続の場合にのみクッキーが送信されるようにします。デフォルトは `true`
	 * @return 成功時 `true`、失敗時 `false` を返します。
	 */
	public function set_cookie( $key , $val , $expire = null , $path = null , $domain = null , $secure = true ){
		if( is_null( $path ) ){
			$path = $this->conf->cookie_default_path;
			if( !strlen( $path ) ){
				$path = $this->get_path_current_dir();
			}
			if( !strlen( $path ) ){
				$path = '/';
			}
		}
		if( !@setcookie( $key , $val , $expire , $path , $domain , $secure ) ){
			return false;
		}

		$_COOKIE[$key] = $val;//現在の処理からも呼び出せるように
		return true;
	}//set_cookie()

	/**
	 * クッキー情報を削除する。
	 *
	 * @param string $key クッキー名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function delete_cookie( $key ){
		if( !@setcookie( $key , null ) ){
			return false;
		}
		unset( $_COOKIE[$key] );
		return true;
	}//delete_cookie()



	// ----- session -----

	/**
	 * セッションを開始する。
	 *
	 * @param string $sid セッションID。省略時、自動発行。
	 * @return bool セッションが正常に開始した場合に `true`、それ以外の場合に `false` を返します。
	 */
	private function session_start( $sid = null ){
		$expire = intval($this->conf->session_expire);
		$cache_limiter = 'nocache';
		$session_name = 'SESSID';
		if( strlen( $this->conf->session_name ) ){
			$session_name = $this->conf->session_name;
		}
		$path = $this->conf->cookie_default_path;
		if( !strlen( $path ) ){
			$path = $this->get_path_current_dir();
		}
		if( !strlen( $path ) ){
			$path = '/';
		}

		@session_name( $session_name );
		@session_cache_limiter( $cache_limiter );
		@session_cache_expire( intval($expire/60) );

		if( intval( ini_get( 'session.gc_maxlifetime' ) ) < $expire + 10 ){
			// ガベージコレクションの生存期間が
			// $expireよりも短い場合は、上書きする。
			// バッファは固定値で10秒。
			@ini_set( 'session.gc_maxlifetime' , $expire + 10 );
		}

		@session_set_cookie_params( 0 , $path );
			//  セッションクッキー自体の寿命は定めない(=0)
			//  そのかわり、SESSION_LAST_MODIFIED を新設し、自分で寿命を管理する。

		if( strlen( $sid ) ){
			// セッションIDに指定があれば、有効にする。
			session_id( $sid );
		}

		// セッションを開始
		$rtn = @session_start();

		// セッションの有効期限を評価
		if( strlen( $this->get_session( 'SESSION_LAST_MODIFIED' ) ) && intval( $this->get_session( 'SESSION_LAST_MODIFIED' ) ) < intval( time() - $expire ) ){
			#	セッションの有効期限が切れていたら、セッションキーを再発行。
			if( is_callable('session_regenerate_id') ){
				@session_regenerate_id( true );
			}
		}
		$this->set_session( 'SESSION_LAST_MODIFIED' , time() );
		return $rtn;
	}//session_start()

	/**
	 * セッションIDを取得する。
	 *
	 * @return string セッションID
	 */
	public function get_session_id(){
		return session_id();
	}//get_session_id()

	/**
	 * セッション情報を取得する。
	 *
	 * @param string $key セッションキー
	 * @return mixed `$key` に対応するセッション値
	 */
	public function get_session( $key ){
		if( @!is_array( $_SESSION ) ){ return null; }
		if( @!array_key_exists($key, $_SESSION) ){ return null; }
		return @$_SESSION[$key];
	}//get_session()

	/**
	 * セッション情報をセットする。
	 *
	 * @param string $key セッションキー
	 * @param mixed $val `$key` に対応するセッション値
	 * @return bool 常に `true` を返します。
	 */
	public function set_session( $key , $val ){
		$_SESSION[$key] = $val;
		return true;
	}//set_session()

	/**
	 * セッション情報を削除する。
	 *
	 * @param string $key セッションキー
	 * @return bool 常に `true` を返します。
	 */
	public function delete_session( $key ){
		unset( $_SESSION[$key] );
		return true;
	}//delete_session()


	// ----- upload file access -----

	/**
	 * アップロードされたファイルをセッションに保存する。
	 *
	 * @param string $key セッションキー
	 * @param array $ulfileinfo アップロードファイル情報
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function save_uploadfile( $key , $ulfileinfo ){
		// base64でエンコードして、バイナリデータを持ちます。
		// $ulfileinfo['content'] にバイナリを格納して渡すか、
		// $ulfileinfo['tmp_name'] または $ulfileinfo['path'] のいずれかに、
		// アップロードファイルのパスを指定してください。
		$fileinfo = array();
		$fileinfo['name'] = $ulfileinfo['name'];
		$fileinfo['type'] = $ulfileinfo['type'];

		if( $ulfileinfo['content'] ){
			$fileinfo['content'] = base64_encode( $ulfileinfo['content'] );
		}else{
			$filepath = '';
			if( @is_file( $ulfileinfo['tmp_name'] ) ){
				$filepath = $ulfileinfo['tmp_name'];
			}elseif( @is_file( $ulfileinfo['path'] ) ){
				$filepath = $ulfileinfo['path'];
			}else{
				return false;
			}
			$fileinfo['content'] = base64_encode( file_get_contents( $filepath ) );
		}

		if( @!is_array( $_SESSION ) ){
			$_SESSION = array();
		}
		if( @!array_key_exists('FILE', $_SESSION) ){
			$_SESSION['FILE'] = array();
		}

		$_SESSION['FILE'][$key] = $fileinfo;
		return	true;
	}
	/**
	 * セッションに保存されたファイル情報を取得する。
	 *
	 * @param string $key セッションキー
	 * @return array|bool 成功時、ファイル情報 を格納した連想配列、失敗時 `false` を返します。
	 */
	public function get_uploadfile( $key ){
		if(!strlen($key)){ return false; }
		if( @!is_array( $_SESSION ) ){
			return false;
		}
		if( @!array_key_exists('FILE', $_SESSION) ){
			return false;
		}
		if( @!array_key_exists($key, $_SESSION['FILE']) ){
			return false;
		}

		$rtn = @$_SESSION['FILE'][$key];
		if( is_null( $rtn ) ){ return false; }

		$rtn['content'] = base64_decode( @$rtn['content'] );
		return	$rtn;
	}
	/**
	 * セッションに保存されたファイル情報の一覧を取得する。
	 *
	 * @return array ファイル情報 を格納した連想配列
	 */
	public function get_uploadfile_list(){
		if( @!array_key_exists('FILE', $_SESSION) ){
			return false;
		}
		return	array_keys( $_SESSION['FILE'] );
	}
	/**
	 * セッションに保存されたファイルを削除する。
	 *
	 * @param string $key セッションキー
	 * @return bool 常に `true` を返します。
	 */
	public function delete_uploadfile( $key ){
		if( @!array_key_exists('FILE', $_SESSION) ){
			return true;
		}
		unset( $_SESSION['FILE'][$key] );
		return	true;
	}
	/**
	 * セッションに保存されたファイルを全て削除する。
	 *
	 * @return bool 常に `true` を返します。
	 */
	public function delete_uploadfile_all(){
		return	$this->delete_session( 'FILE' );
	}


	// ----- utils -----

	/**
	 * USER_AGENT を取得する。
	 *
	 * @return string USER_AGENT
	 */
	public function get_user_agent(){
		return @$this->conf->server['HTTP_USER_AGENT'];
	}//get_user_agent()

	/**
	 *  SSL通信か調べる
	 *
	 * @return bool SSL通信の場合 `true`、それ以外の場合 `false` を返します。
	 */
	public function is_ssl(){
		if( @$this->conf->server['HTTP_SSL'] || @$this->conf->server['HTTPS'] ){
			// SSL通信が有効か否か判断
			return true;
		}
		return false;
	}

	/**
	 * コマンドラインによる実行か確認する。
	 *
	 * @return bool コマンドからの実行の場合 `true`、ウェブからの実行の場合 `false` を返します。
	 */
	public function is_cmd(){
		if( array_key_exists( 'REMOTE_ADDR' , $this->conf->server ) ){
			return false;
		}
		return	true;
	}


	// ----- private -----

	/**
	 * 受け取ったテキストを、指定の文字セットに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $encode 変換後の文字セット。省略時、`mb_internal_encoding()` から取得
	 * @param string $encodefrom 変換前の文字セット。省略時、自動検出
	 * @return string 文字セット変換後のテキスト
	 */
	private static function convert_encoding( $text, $encode = null, $encodefrom = null ){
		if( !is_callable( 'mb_internal_encoding' ) ){ return $text; }
		if( !strlen( $encodefrom ) ){ $encodefrom = mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII'; }
		if( !strlen( $encode ) ){ $encode = mb_internal_encoding(); }

		if( is_array( $text ) ){
			$rtn = array();
			if( !count( $text ) ){ return $text; }
			$TEXT_KEYS = array_keys( $text );
			foreach( $TEXT_KEYS as $Line ){
				$KEY = mb_convert_encoding( $Line , $encode , $encodefrom );
				if( is_array( $text[$Line] ) ){
					$rtn[$KEY] = self::convert_encoding( $text[$Line] , $encode , $encodefrom );
				}else{
					$rtn[$KEY] = @mb_convert_encoding( $text[$Line] , $encode , $encodefrom );
				}
			}
		}else{
			if( !strlen( $text ) ){ return $text; }
			$rtn = @mb_convert_encoding( $text , $encode , $encodefrom );
		}
		return $rtn;
	}

	/**
	 * クォートされた文字列のクォート部分を取り除く。
	 *
	 * この関数は、PHPの `stripslashes()` のラッパーです。
	 * 配列を受け取ると再帰的に文字列を変換して返します。
	 *
	 * @param mixed $text テキスト
	 * @return string クォートが元に戻されたテキスト
	 */
	private static function stripslashes( $text ){
		if( is_array( $text ) ){
			// 配列なら
			foreach( $text as $key=>$val ){
				$text[$key] = self::stripslashes( $val );
			}
		}elseif( is_string( $text ) ){
			// 文字列なら
			$text = stripslashes( $text );
		}
		return	$text;
	}

	/**
	 * カレントディレクトリのパスを取得
	 * @return string ドキュメントルートからのパス(スラッシュ閉じ)
	 */
	private function get_path_current_dir(){
		//  環境変数から自動的に判断。
		$rtn = dirname( $this->conf->server['SCRIPT_NAME'] );
		if( !array_key_exists( 'REMOTE_ADDR' , $this->conf->server ) ){
			//  CUIから起動された場合
			//  ドキュメントルートが判定できないので、
			//  ドキュメントルート直下にあるものとする。
			$rtn = '/';
		}
		$rtn = str_replace('\\','/',$rtn);
		$rtn .= ($rtn!='/'?'/':'');
		return $rtn;
	}//get_path_current_dir()

}
?>
