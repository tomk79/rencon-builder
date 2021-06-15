<?php
require_once(__DIR__.'/../vendor/autoload.php');
$file_bin = file_get_contents( __DIR__.'/../lib/framework.php' );

$src = '';
$src .= '<'.'?php'."\n";
$src .= 'namespace tomk79\renconBuilder;'."\n";
$src .= ''."\n";
$src .= 'class framework_files {'."\n";
$src .= '   public function get_framework() {'."\n";
$src .= '       return base64_decode('.var_export(base64_encode($file_bin), true).');'."\n";
$src .= '   }'."\n";
$src .= '}'."\n";
file_put_contents( __DIR__.'/../src/framework_files.php', $src );

exit();
