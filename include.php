<?
$config = array(
  'page_titles' => array()
);

include(__DIR__ . '/config.php');

function quotes_create_fts($pdo) {
  global $config;

  $pdo->exec(
    <<<EOF
CREATE VIRTUAL TABLE `$config[table_fts]`
USING FTS5 (
  `quote`,
  `tags`,
  content=`$config[table_quotes]`,
  content_rowid=`id`
)
EOF
  );

var_dump($pdo->errorInfo());

  $pdo->exec(
    <<<EOF
CREATE TRIGGER `post_insert` AFTER INSERT ON `$config[table_quotes]` BEGIN
  INSERT INTO `$config[table_fts]` (
    `rowid`,
    `quote`,
    `tags`
  )
  VALUES (
    `new`.`id`,
    `new`.`quote`,
    `new`.`tags`
  );
END
EOF
  );

var_dump($pdo->errorInfo());

  $pdo->exec(
    <<<EOF
CREATE TRIGGER `post_delete` AFTER DELETE ON `$config[table_quotes]` BEGIN
  INSERT INTO `$config[table_fts]` (
    `$config[table_fts]`,
    `rowid`,
    `quote`, `tags`
  )
  VALUES (
    'delete',
    `old`.`id`,
    `old`.`quote`,
    `old`.`tags`
  );
END
EOF
  );

var_dump($pdo->errorInfo());

  $pdo->exec(
    <<<EOF
CREATE TRIGGER `post_update` AFTER UPDATE ON `$config[table_quotes]` BEGIN
  INSERT INTO `$config[table_fts]` (
    `$config[table_fts]`,
    `rowid`,
    `quote`, `tags`
  )
  VALUES (
    'delete',
    `old`.`id`,
    `old`.`quote`,
    `old`.`tags`
  );
  INSERT INTO `$config[table_fts]` (
    `rowid`,
    `quote`,
    `tags`
  )
  VALUES (
    `new`.`id`,
    `new`.`quote`,
    `new`.`tags`
  );
END
EOF
  );

var_dump($pdo->errorInfo());
}
?>
