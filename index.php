<?
$config = array();

include('config.php');

$create = !file_exists($config['db_path']);
$pdo = new PDO('sqlite:' . $config['db_path']);

if ($create) {
	$pdo->exec(<<<EOF
CREATE TABLE `$config[table_quotes]` (
	`id` integer PRIMARY KEY ASC,
	`quote` text NOT NULL,
	`tags` text,
	`rating` int NOT NULL DEFAULT '0',
	`user` varchar(64),
	`created` datetime NOT NULL
)
EOF
		);

	$pdo->exec(<<<EOF
CREATE TABLE `$config[table_votes]` (
	`quote` integer NOT NULL,
	`user` varchar(64),
	`created` datetime NOT NULL
)
EOF
		);
}
?>
