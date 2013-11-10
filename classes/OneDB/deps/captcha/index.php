<?php

require_once "kcaptcha.php";
session_start();
$captcha = new Captcha();
$_SESSION[$_GET['captcha_id']] = $captcha->getKeyString();

?>