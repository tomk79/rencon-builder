<?php
namespace app01\console;
class sample {
    static public function start( $rencon ){
        echo '-------------'."\n";
        echo 'Console Sample'."\n";
        echo 'pwd: '.realpath('.')."\n";
		echo 'routeParam1: '.$rencon->get_route_param('routeParam1')."\n";
        exit;
    }
}
