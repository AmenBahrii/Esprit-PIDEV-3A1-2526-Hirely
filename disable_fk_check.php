<?php
require 'vendor/autoload.php';
require 'config/bootstrap.php';

\ = \->get('doctrine.orm.default_entity_manager');
\ = \->getConnection();

// Disable foreign key checks
\->executeQuery('SET FOREIGN_KEY_CHECKS=0');
echo "Foreign key checks disabled\n";
