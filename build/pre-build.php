<?php
require_once(__DIR__.'/../vendor/autoload.php');

$src = '';
$src .= '<'.'?php'."\n";
$src .= 'namespace tomk79\renconBuilder;'."\n";
$src .= ''."\n";
$src .= 'class framework_files {'."\n";
$src .= '   public function get( $className ) {'."\n";
$src .= '       $files = array('."\n";
$src .= '           "framework" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/framework.php' ) ) ), true).','."\n";
$src .= '           "conf" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/conf.php' ) ) ), true).','."\n";
$src .= '           "filesystem" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/filesystem.php' ) ) ), true).','."\n";
$src .= '           "request" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/request.php' ) ) ), true).','."\n";
$src .= '           "login" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/login.php' ) ) ), true).','."\n";
$src .= '           "resources" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/resources.php' ) ) ), true).','."\n";
$src .= '           "theme" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/theme.php' ) ) ), true).','."\n";
$src .= '       );'."\n";
$src .= '       return base64_decode($files[$className]);'."\n";
$src .= '   }'."\n";
$src .= '}'."\n";
file_put_contents( __DIR__.'/../src/framework_files.php', $src );

exit();
