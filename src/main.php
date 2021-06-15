<?php
namespace tomk79\renconBuilder;

class main {
    public function __construct(){}

    public function start(){

        $version = '0.0.1-alpha.1+dev';
        echo '-------------------'."\n";
        echo 'rencon-builder v'.$version."\n";

        $fs = new \tomk79\filesystem();
        $req = new \tomk79\request();

        $composerJson = json_decode( file_get_contents( './composer.json' ) );
        $renconBuilderJson = json_decode( file_get_contents( './rencon-builder.json' ) );

        ob_start();var_dump($composerJson);error_log(ob_get_clean(),3,$renconBuilderJson->dist);
        ob_start();var_dump($renconBuilderJson);error_log(ob_get_clean(),3,$renconBuilderJson->dist);

    }
}
