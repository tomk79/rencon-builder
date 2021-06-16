<?php
namespace renconFramework;

/**
 * resourceMgr class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class resources{
	private $main;

	/**
	 * Constructor
	 */
	public function __construct( $main ){
		$this->main = $main;
	}

	public function echo_resource( $path ){
		$ext = null;
		if( preg_match('/\.([a-zA-Z0-9\_\-]*)$/', $path, $matched) ){
			$ext = $matched[1];
			$ext = strtolower($ext);
			$mime = $this->get_mime_type($ext);
			if( !$mime ){ $mime = 'text/html'; }
			header('Content-type: '.$mime);
		}
		echo $this->get($path);
		exit;
	}

	/**
	 * リソースを取得
	 */
	public function get( $path ){
		$path = preg_replace( '/$(?:\/*|\.\.?\/)*/', '', $path );

		$resources = array(

/* function resource() */

		);

		if( !array_key_exists($path, $resources) ){ return false; }
		return base64_decode($resources[$path]);
	}

	/**
	 * 拡張子から mime-type を得る
	 */
	public function get_mime_type($ext){
		switch( $ext ){
			case 'html':
			case 'htm':
				return 'text/html';
				break;
			case 'js':
				return 'text/javascript';
				break;
			case 'css':
			case 'scss':
				return 'text/css';
				break;
			case 'gif':
				return 'image/gif';
				break;
			case 'png':
				return 'image/png';
				break;
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				return 'image/jpeg';
				break;
			case 'svg':
				return 'image/svg+xml ';
				break;
			case 'text':
			case 'txt':
			case 'log':
			case 'sh':
			case 'bat':
			case 'php':
			case 'json':
			case 'yml':
			case 'yml':
			case 'htaccess':
				return 'text/plain';
				break;
		}
		return false;
	}

}
?>
