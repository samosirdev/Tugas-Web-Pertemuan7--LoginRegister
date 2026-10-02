<?php
require 'config.php';
redirect(current_user() ? 'dashboard.php' : 'login.php');
