<?php
function json_ok($data = null, int $code = 200) {
    Flight::json(['data' => $data, 'error' => null], $code);
}
function json_error(string $message, int $code = 400, ?array $fields = null) {
    Flight::json(['data' => null, 'error' => ['code'=>$code, 'message'=>$message, 'fields'=>$fields]], $code);
}
