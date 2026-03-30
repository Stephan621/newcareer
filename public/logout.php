<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';

logout(); // Destroys session and redirects to login.php
