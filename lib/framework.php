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
	private $resources;

	public function __construct( $conf ){
		$this->conf = new conf( $conf );
		$this->fs = new filesystem();
		$this->req = new request();
		$this->resources = new resources($this);
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
			header("Content-type: ".$this->resources->get_mime_type($resource));
			$bin = $this->resources->get($resource);
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


	public function theme( $contents ){
		ob_start();
?>/* theme template */<?php
		$html = ob_get_clean();
		return $html;
	}

}
?>