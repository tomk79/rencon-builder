<?php

namespace app01;

class api_test {
	static public function test001( $rencon ){
		$rtn = array();
		$rtn['result'] = true;
		$rtn['test001'] = 'test001';
		return $rtn;
	}
	static public function test_route_param( $rencon ){
		$rtn = array();
		$rtn['result'] = true;
		$rtn['routeParam1'] = $rencon->get_route_param('routeParam1');
		return $rtn;
	}
}
