<?php
require_once(__DIR__.'/../vendor/autoload.php');

$fs = new \tomk79\filesystem();
$src = '';
$src .= '<'.'?php'."\n";
$src .= 'namespace tomk79\renconBuilder;'."\n";
$src .= ''."\n";
$src .= 'class framework_files {'."\n";
$src .= '   public function get( $className ) {'."\n";
$src .= '       $files = array('."\n";
$src .= '           "rencon" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/rencon.php' ) ) ), true).','."\n";
$src .= '           "conf" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/conf.php' ) ) ), true).','."\n";
$src .= '           "dataDotPhp" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/dataDotPhp.php' ) ) ), true).','."\n";
$src .= '           "langbank" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/LangBank.php' ) ) ), true).','."\n";
$src .= '           "filesystem" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/filesystem.php' ) ) ), true).','."\n";
$src .= '           "request" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/request.php' ) ) ), true).','."\n";
$src .= '           "auth" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/auth.php' ) ) ), true).','."\n";
$src .= '           "user" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/user.php' ) ) ), true).','."\n";
$src .= '           "initializer" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/initializer.php' ) ) ), true).','."\n";
$src .= '           "resources" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/resources.php' ) ) ), true).','."\n";
$src .= '           "theme" => '.var_export(base64_encode( trim( file_get_contents( __DIR__.'/../lib/theme.php' ) ) ), true).','."\n";
$src .= '           "language.csv" => '.var_export(base64_encode( var_export($fs->read_csv( __DIR__.'/../data/language.csv' ), true ) ), true).','."\n";
$src .= '       );'."\n";
$src .= '       return base64_decode($files[$className]);'."\n";
$src .= '   }'."\n";
$src .= '}'."\n";
file_put_contents( __DIR__.'/../src/framework_files.php', $src );

exit();
