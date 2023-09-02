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
        function sendApiRequest(apiName, apiKey){
            fetch('?api='+apiName, {
                method: 'post',
                headers: {
                    'X-API-KEY': apiKey,
                }
            });
            return;
        }
        </script>
        <p><button type="button" onclick="sendApiRequest('api.test.test001', 'zzzzzzzzzzz-zzzzzzzzz-zzzzzzzzz');">api.test.test001</button></p>
        <p><button type="button" onclick="sendApiRequest('api.test.aaaaaa', 'xxxxx-xxxxx-xxxxxxxxxxx-xxxxxxx');">api.test.aaaaaa</button></p>
        <?php
        return;
    }
}
