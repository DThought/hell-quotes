<?
$config = array();

include('config.php');
include('lib/cleverly/Cleverly.class.php');

$create = !file_exists($config['db_path']);
$pdo = new PDO('sqlite:' . $config['db_path']);
$cleverly = new Cleverly();

if ($create) {
	$pdo->exec(<<<EOF
CREATE TABLE `$config[table_quotes]` (
	`id` integer PRIMARY KEY ASC,
	`quote` text NOT NULL,
	`tags` text,
	`score` int NOT NULL DEFAULT '0',
	`user` varchar(64),
	`created` datetime NOT NULL
)
EOF
		);

	$pdo->exec(<<<EOF
CREATE TABLE `$config[table_votes]` (
	`quote` integer NOT NULL,
	`user` varchar(64),
	`updated` datetime NOT NULL
)
EOF
		);
}

$cleverly->display(__DIR__ . "/$config[tpl_path]/index.tpl");
?>
