<?php
require_once(__DIR__.'/../vendor/autoload.php');

$src = '';
$src .= '<'.'?php'."\n";
$src .= 'namespace tomk79\renconBuilder;'."\n";
$src .= ''."\n";
$src .= 'class framework_files {'."\n";
$src .= '   public function get( $className ) {'."\n";
$src .= '       $files = array('."\n";
$src .= '           "framework" => '.var_export(base64_encode( file_get_contents( __DIR__.'/../lib/framework.php' ) ), true).','."\n";
$src .= '           "conf" => '.var_export(base64_encode( file_get_contents( __DIR__.'/../lib/conf.php' ) ), true).','."\n";
$src .= '       );'."\n";
$src .= '       return base64_decode($files[$className]);'."\n";
$src .= '   }'."\n";
$src .= '}'."\n";
file_put_contents( __DIR__.'/../src/framework_files.php', $src );

exit();
