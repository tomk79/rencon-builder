<?php
/* ---------------------

  <!-- appname -->

--------------------- */

namespace renconFramework;

// =-=-=-=-=-=-=-=-=-=-=-= Configuration START =-=-=-=-=-=-=-=-=-=-=-=
$conf = new \stdClass();


/* --------------------------------------
 * ログインユーザーのIDとパスワードの対
 * 
 * rencon の初期画面は、ログイン画面から始まります。
 * `$conf->users` に 登録されたユーザーが、ログインを許可されます。
 * ユーザーIDを キー に、sha1ハッシュ化されたパスワード文字列を 値 に持つ連想配列で設定してください。
 * ユーザーは、複数登録できます。
 */
$conf->users = array(
	"admin" => sha1("admin"),
);




// =-=-=-=-=-=-=-=-=-=-=-= / Configuration END =-=-=-=-=-=-=-=-=-=-=-=


$app = new framework( $conf );
$app->run();

class framework {

	private $conf;
	private $fs;
	private $req;

	public function __construct( $conf ){
		$this->conf = new conf( $conf );
		$this->fs = new filesystem();
		$this->req = new request();
	}

	public function conf(){ return $this->conf; }
	public function fs(){ return $this->fs; }
	public function req(){ return $this->req; }

	public function run(){
		$route = array(

/* router */

		);

		$action = $this->req->get_param('a');
		$resource = $this->req->get_param('res');
		$controller = null;

		if( strlen($resource) ){
			header("Content-type: ".$this->mimetype($resource));
			$bin = $this->resource($resource);
			echo $bin;
			exit();

		}

		header('Content-type: text/html'); // default

		$login = new login($this);
		if( !$login->check() ){
			$login->please_login();
			exit;
		}

		if( $action == 'logout' ){
			$login->logout();
			exit;
		}


		if( array_key_exists( $action, $route ) ){
			$controller = $route[$action];
			ob_start();
			call_user_func($controller->page);
			$contents = ob_get_clean();
			$html = $this->theme( $contents );
			echo $html;

		}
		exit();
	}



	public function resource($path){
		$resources = array(

/* function resource() */

		);
		if( !array_key_exists($path, $resources) ){ return false; }
		return base64_decode($resources[$path]);
	}

	public function mimetype( $path ){
		$ext = preg_replace('/^.*\.([a-z0-9]+)$/i', '$1', $path);
		$ext = strtolower( $ext );

		$mimetype = 'text/plain';
		switch($ext){
			case 'htm':
			case 'html':
				$mimetype = 'text/html';
				break;
			case 'gif':
				$mimetype = 'image/gif';
				break;
			case 'png':
				$mimetype = 'image/png';
				break;
			case 'jpg':
			case 'jpe':
			case 'jpeg':
				$mimetype = 'image/jpeg';
				break;
			case 'css':
				$mimetype = 'text/css';
				break;
			case 'js':
				$mimetype = 'text/javascript';
				break;
		}
		return $mimetype;
	}


	public function theme( $contents ){
		ob_start();
?>/* theme template */<?php
		$html = ob_get_clean();
		return $html;
	}

}
?>