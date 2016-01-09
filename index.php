<?
$config = array(
	'page_titles' => array()
);

include('config.php');
include('lib/cleverly/Cleverly.class.php');

if (@$_GET['page'] == 'home') {
	header('Location: ./', true, 301);
}

$create = !file_exists($config['db_path']);
$pdo = new PDO('sqlite:' . $config['db_path']);
$cleverly = new Cleverly();
$cleverly->setTemplateDir(__DIR__ . '/' . $config['template_path']);

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

$cleverly->display('index.tpl', $config + array(
	'page_title' => 'Yet Another Quote Database',
	'nav' => function() {
		global $cleverly;
		global $config;

		foreach ($config['page_titles'] as $page => $title) {
			$vars = array(
				'link_name' => $page,
				'link_title' => $title
			);

			if ($page == 'home' and !isset($_GET['page']) or $page == @$_GET['page']) {
				$cleverly->display('nav_link_active.tpl', $vars);
			} else {
				$cleverly->display('nav_link.tpl', $vars);
			}
		}
	},
	'main' => function() {
		global $cleverly;
		global $config;

		switch (@$_GET['page']) {
			case '':
				$cleverly->display('main_default.tpl');
				break;
		}
	}
));
?>
