<?php
header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');


readfile('node_modules/ngx-edu-sharing-rendering-web-component/edu-service-worker.js');