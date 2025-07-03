<?php
header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');


readfile(getenv('BASE_URL_INTERNAL') . '/web-components/rendering-service/edu-service-worker.js');