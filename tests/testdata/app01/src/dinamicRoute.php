<?php

namespace app01;

class dinamicRoute {
    static public function start( $rencon ){
        echo "<p>dinamicRoute::start()</p>"."\n";
        echo "<pre>"."\n";
        var_dump($rencon->get_route_params());
        var_dump($rencon->get_route_param('routeParam1'));
        echo "</pre>"."\n";
        return;
    }
}
