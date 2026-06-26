<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['router']);
jsonResponse(['ok' => true]);
