<?php

OCP\User::checkLoggedIn();

$tmpl = new OCP\Template('circles', 'files/list', '');
$tmpl->printPage();
