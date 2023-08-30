<?php

namespace app01;

class test {
    static public function start( $rencon ){
        ?>
        <p>test::start()</p>
        <form action="?a=test.post" method="post">
            <input type="hidden" name="CSRF_TOKEN" value="<?= htmlspecialchars($rencon->auth()->get_csrf_token()) ?>" />
            <button type="submit">test.post</button>
        </form>
        <?php
        return;
    }
    static public function post( $rencon ){
        echo "test::post()"."\n";
        return;
    }
    static public function api_preview($rencon){
        ?>
        <script>
        function sendApiRequest(apiName){
            fetch('?api='+apiName, {
                method: 'post',
                headers: {
                    'X-API-KEY': 'xxxxx-xxxxx-xxxxxxxxxxx-xxxxxxx',
                }
            });
            return;
        }
        </script>
        <p><button type="button" onclick="sendApiRequest('api.test.test001');">api.test.test001</button></p>
        <p><button type="button" onclick="sendApiRequest('api.test.aaaaaa');">api.test.aaaaaa</button></p>
        <?php
        return;
    }
}
