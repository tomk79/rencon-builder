<?php
namespace tomk79\renconBuilder;

class writer {

	/** $utils */
	private $utils;

	/** $renconBuilderJson */
	private $renconBuilderJson;

	/** app_id */
	private $app_id;

	/** version */
	private $version;

	/** app_name */
	private $app_name;

	/** $require_files */
	private $require_files;

	/** $resource_files */
	private $resource_files;

	/**
	 * Constructor
	 */
	public function __construct( $utils, $renconBuilderJson ){
		$this->utils = $utils;
		$this->renconBuilderJson = $renconBuilderJson;
		$this->require_files = array();
		$this->resource_files = array();
	}


	/**
	 * バージョン番号をセット
	 */
	public function set_version($version){
		$this->version = $version;
	}

	/**
	 * アプリケーション名をセット
	 */
	public function set_app_name($app_name){
		$this->app_name = $app_name;
	}

	/**
	 * アプリケーションIDをセット
	 */
	public function set_app_id($app_id){
		$this->app_id = $app_id;
	}

	/**
	 * スキャン済みのパッケージか調べる
	 */
	public function is_scaned( $package_name ){
		if( !strlen($package_name ?? '') ){
			$package_name = '';
		}
		if( array_key_exists( $package_name, $this->require_files ) ){
			return true;
		}
		return false;
	}


	/**
	 * PHPコードを加える
	 */
	public function require( $package_name, $realpath ){
		if( !strlen($package_name ?? '') ){
			$package_name = '';
		}
		if( !array_key_exists($package_name, $this->require_files) ){
			$this->require_files[$package_name] = array();
		}
		array_push( $this->require_files[$package_name], $realpath );
		return true;
	}


	/**
	 * リソースファイルを加える
	 */
	public function resource( $realpath ){
		array_push( $this->resource_files, $realpath );
		return true;
	}


	/**
	 * 保存する
	 */
	public function save(){
		$framework_files = new framework_files();

		$rtn = '';

		$src_function_resource = '';
		foreach( $this->resource_files as $file ){
			$bin = file_get_contents($this->renconBuilderJson->resources.'/'.$file);
			$src_function_resource .= ''.var_export($file, true).' => '.var_export(base64_encode( $bin ), true).','."\n";
		}


		$src_route = '';
		$src_route_login = '';
		$src_route_logout = '';
		if( $this->renconBuilderJson->route ){
			foreach( $this->renconBuilderJson->route as $route => $func_name ){
				$src_route .= ''.var_export($route, true).' => (object) array('."\n";
				$src_route .= '	"title" => '.var_export($func_name->title, true).','."\n";
				if( is_file( $func_name->page ) ){
					$src_route .= '	"page" => function( $rencon ){ ?'.'>'."\n";
					$src_route .= file_get_contents( $func_name->page );
					$src_route .= '<'.'?php return; },'."\n";
				}else{
					$src_route .= '	"page" => '.var_export($func_name->page, true).','."\n";
				}
				$src_route .= '),'."\n";

				if( $route == 'login' ){
					if( is_file( $func_name->page ) ){
						$src_route_login .= '$page = function(){'."\n";
						$src_route_login .= '$rencon = $this; ?'.'>'."\n";
						$src_route_login .= file_get_contents( $func_name->page );
						$src_route_login .= '<'.'?php return; }'."\n";
					}else{
						$src_route_login .= '$page = '.var_export($func_name->page, true).';'."\n";
					}
					$src_route_login .= '$controller = $route[$action];'."\n";
					$src_route_login .= '$page_info[\'title\'] = $controller->title;'."\n";
					$src_route_login .= '$this->theme()->set_current_page_info( $page_info );'."\n";
					$src_route_login .= 'ob_start();'."\n";
					$src_route_login .= 'call_user_func( $controller->page );'."\n";
					$src_route_login .= '$content = ob_get_clean();'."\n";
					$src_route_login .= '$html = $this->theme()->bind( $content );'."\n";
					$src_route_login .= 'echo $html;'."\n";
				}
				if( $route == 'logout' ){
					if( is_file( $func_name->page ) ){
						$src_route_logout .= '$page = function(){'."\n";
						$src_route_logout .= '$rencon = $this; ?'.'>'."\n";
						$src_route_logout .= file_get_contents( $func_name->page );
						$src_route_logout .= '<'.'?php return; }'."\n";
					}else{
						$src_route_logout .= '$page = '.var_export($func_name->page, true).';'."\n";
					}
					$src_route_logout .= '$controller = $route[$action];'."\n";
					$src_route_logout .= '$page_info[\'title\'] = $controller->title;'."\n";
					$src_route_logout .= '$this->theme()->set_current_page_info( $page_info );'."\n";
					$src_route_logout .= 'ob_start();'."\n";
					$src_route_logout .= 'call_user_func( $controller->page );'."\n";
					$src_route_logout .= '$content = ob_get_clean();'."\n";
					$src_route_logout .= '$html = $this->theme()->bind( $content );'."\n";
					$src_route_logout .= 'echo $html;'."\n";
				}
			}

		}

		$src_config_template = '';
		if( $this->renconBuilderJson->config_template ){
			$src_config_template .= file_get_contents( $this->renconBuilderJson->config_template );
		}else{
			$src_config_template = '$conf = new \stdClass();';
		}
		$src_config_template = trim($src_config_template ?? '');
		$src_config_template = preg_replace('/^\<\?php/', '', $src_config_template);
		$src_config_template = preg_replace('/\?\>$/', '', $src_config_template);


		$src_middleware = '';
		if( $this->renconBuilderJson->middleware ){
			$src_middleware .= var_export($this->renconBuilderJson->middleware, true);
		}else{
			$src_middleware .= 'array()';
		}

		$src_template = '';
		if( $this->renconBuilderJson->theme ){
			$src_template .= file_get_contents( $this->renconBuilderJson->theme );
		}


		$framework = $framework_files->get('rencon');
		$framework = str_replace('<!-- app_name -->', $this->app_name, $framework);
		$framework = str_replace('<!-- app_id -->', $this->app_id, $framework);
		$framework = str_replace('<!-- version -->', $this->version, $framework);
		$framework = str_replace('/*-- config --*/', $src_config_template, $framework);
		$framework = str_replace('/* router */', $src_route, $framework);
		$framework = str_replace('array(/* middleware */)', $src_middleware, $framework);
		$framework = str_replace('/* theme template */', $src_template, $framework);
		$rtn .= $framework;
		unset($framework);


		$rtn .= $framework_files->get('initializer');
		$rtn .= $framework_files->get('conf');
		$rtn .= $framework_files->get('filesystem');
		$rtn .= $framework_files->get('request');
		$rtn .= $framework_files->get('user');

		$src_theme = $framework_files->get('theme');
		$src_theme = str_replace('/* theme template */', $src_template, $src_theme);
		$rtn .= $src_theme;
		unset($src_theme);


		$src_login = $framework_files->get('login');
		$src_login = str_replace('<!-- app_name -->', $this->app_name, $src_login);
		$src_login = str_replace('<!-- app_id -->', $this->app_id, $src_login);
		$src_login = str_replace('/* route:login */', $src_route_login, $src_login);
		$src_login = str_replace('/* route:logout */', $src_route_logout, $src_login);
		$rtn .= $src_login;
		unset($src_login);

		foreach( $this->require_files as $package_name => $files ){
			foreach( $files as $file ){
				if( !preg_match('/\.php$/i', $file ?? '') ){
					continue;
				}
				$bin = file_get_contents($file);
				$bin = trim( $bin );
				$bin = preg_replace( '/\?\>$/si', '', $bin )."\n".'?'.'>';
				$rtn .= $bin;
			}
		}


		$src_resourceMgr = $framework_files->get('resources');
		$src_resourceMgr = str_replace('/* function resource() */', $src_function_resource, $src_resourceMgr);
		$rtn .= $src_resourceMgr;

		return $this->utils->fs()->save_file( $this->renconBuilderJson->dist, $rtn );
	}

}
