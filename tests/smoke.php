<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Environment.php';require dirname(__DIR__).'/src/Dashboard.php';
assert(class_exists(AymudoStarter\Environment::class));assert(class_exists(AymudoStarter\Dashboard::class));echo "starter smoke test passed\n";
