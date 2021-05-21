<?php

OC_Util::checkLoggedIn();

$tmpl = new OCP\Template('circles', 'files/list', '');
$tmpl->printPage();
