<?php
require_once __DIR__ . '/../dao/UserDao.php';
$u = new UserDao();
print_r($u->getAll());