<?php
include_once('src/unusorin/Git/Repository.php');
include_once('src/unusorin/Git/Exceptions/GitException.php');
include_once('src/unusorin/Git/GitStatus.php');

$repo = new \unusorin\Git\Repository('/home/vagrant/www/ThinFrame');
print_r($repo->getAllRemotes());