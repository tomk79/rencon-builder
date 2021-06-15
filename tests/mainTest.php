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
	 * Test
	 */
	public function testStandard(){
		// $realpath_rencon_builder = realpath(__DIR__.'/../rencon-builder.phar');
		$realpath_rencon_builder = 'php '.realpath(__DIR__.'/../rencon-builder.php');
		$cd = realpath('.');
		chdir(__DIR__.'/testdata/app01/');

		$stdout = exec( $realpath_rencon_builder );
		var_dump($stdout);

		$this->assertEquals( 1, 1 );

		chdir($cd);
	}

}
