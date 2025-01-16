<?php
require_once 'helpers.php';

session_start();
session_unset();
session_destroy();

redirect('index.php?msg=Te-ai delogat cu succes!');
