<?php

namespace app01;

class api_test {
    static public function api_preview($rencon){
        ?>
        <script>
        function sendApiRequest(apiName, apiKey){
            fetch('?api='+apiName, {
                method: 'post',
                headers: {
                    'X-API-KEY': apiKey,
                }
            }).then((data)=>{
				return data.json();
			}).then((data)=>{
				console.log(data);
				alert(JSON.stringify(data));
			});
            return;
        }
        </script>
        <p><button type="button" onclick="sendApiRequest('api.test.test001', '12345zzzzzzzzzzz-zzzzzzzzz-zzzzzzzzz');">api.test.test001</button></p>
        <p><button type="button" onclick="sendApiRequest('api.test.aaaaaa', '12345zzzzzzzzzzz-zzzzzzzzz-zzzzzzzzz');">api.test.aaaaaa</button></p>
        <p><button type="button" onclick="sendApiRequest('api.test.bbbbbb', 'xxxxx-xxxxx-xxxxxxxxxxx-xxxxxxx');">api.test.bbbbbb (無効なAPIキーを指定)</button></p>
        <?php
        return;
    }

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
