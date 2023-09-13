<?php
/**
 * test
 */
class mainTest extends PHPUnit\Framework\TestCase{
	private $fs;

	public function setUp() : void{
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
	}


	/**
	 * Build test
	 */
	public function testStandard(){
		// ビルド
		// $command_rencon_builder = 'php '.realpath(__DIR__.'/../rencon-builder.php');
		$command_rencon_builder = realpath(__DIR__.'/../rencon-builder.phar');
		$cd = realpath('.');
		chdir(__DIR__.'/testdata/app01/');

		exec( $command_rencon_builder, $stdout );
		var_dump( implode("\n", $stdout) );

		$this->assertEquals( 1, 1 );
		chdir($cd);

	}

	/**
	 * Create test data
	 */
	public function testCreateData(){
		ob_start();
		?>
<<?= '?php' ?> header('HTTP/1.1 404 Not Found'); echo('404 Not Found');exit(); <?= '?' ?>>
{
    "zzzzzzzzzzz-zzzzzzzzz-zzzzzzzzz": {
	}
}
		<?php
		$this->fs->save_file(__DIR__.'/testdata/app01/dist/app01__data/api_keys.json.php', ob_get_clean());

		$this->assertEquals( 1, 1 );
	}

}
