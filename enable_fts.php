<?
include('include.php');

if (!file_exists($config['db_path']) || !@$config['table_fts']) {
  die();
}

$pdo = new PDO('sqlite:' . $config['db_path']);
quotes_create_fts($pdo);

$pdo->exec(
  <<<EOF
INSERT INTO `$config[table_fts]` (`$config[table_fts]`)
VALUES ('rebuild')
EOF
);
?>
