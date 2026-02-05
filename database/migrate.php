#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ADMS\Database\Database;
use ADMS\Database\Schema;

$config = require __DIR__ . '/../config/config.php';

echo "Running database migrations...\n";

try {
    $db = Database::getInstance($config['database']);
    $schema = new Schema($db);
    
    echo "Creating tables...\n";
    $schema->migrate();
    
    echo "✓ Migration completed successfully!\n";
    echo "Database created at: " . $config['database']['path'] . "\n";
} catch (\Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
