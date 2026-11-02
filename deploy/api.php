<?php
// Every /api/... request is rewritten to this file (deploy/apache.conf),
// which runs the normal backend front controller.
require '/var/www/backend/public/index.php';
