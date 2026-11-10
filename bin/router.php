<?php
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('~(?:^|/)\.|^/(?:bin|database|headers)(?:/|$)|^/config(?:\.local|\.example)?\.php$|^/uploads/.*\.(?:php\d*|phtml|phar)(?:/|$)~i', $path)) {
    http_response_code(404);
    exit;
}
return false;
