<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$dbManager = new \HybridRAG\KnowledgeGraph\ArangoDBManager($config['arangodb']);

$backupPath = $config['arangodb']['backup']['path'] . '/backup_' . date('Y-m-d_H-i-s') . '.json';
$dbManager->backupDatabase($backupPath);

echo "Backup completed: $backupPath\n";