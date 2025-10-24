<?php
header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');

readfile(getenv('BASE_URL_INTERNAL') . '/web-components/rendering-service/edu-service-worker.js');
