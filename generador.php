<?php

$password = '123';

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash generado para: <br>";
echo "$password <br>";
echo $hash;
