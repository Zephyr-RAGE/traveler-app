<?php

require_once __DIR__ . '/../config/env.php';

loadEnv(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../routes/web.php';