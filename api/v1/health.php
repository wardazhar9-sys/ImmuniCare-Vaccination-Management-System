<?php

require_once "../../includes/api.php";

api_method('GET');

if (!$conn->ping()) {
    api_response(['ok' => false, 'error' => 'Database unavailable.'], 503);
}

api_response([
    'ok' => true,
    'service' => 'immunicare',
    'time' => gmdate('c')
]);
