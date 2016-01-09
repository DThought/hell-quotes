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
	`score` integer NOT NULL DEFAULT '0',
	`user` varchar(64),
	`queued` integer NOT NULL DEFAULT '0',
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
				$cleverly->display('main_home.tpl');
				break;
			case 'latest':
				$cleverly->display('main_latest.tpl');
				break;
			case 'browse':
				$cleverly->display('main_browse.tpl');
				break;
			case 'random':
				$cleverly->display('main_random.tpl');
				break;
			case 'top':
				$cleverly->display('main_top.tpl');
				break;
			case 'add':
				$cleverly->display('main_add.tpl');
				break;
			case 'admin':
				$cleverly->display('main_admin.tpl');
				break;
			case 'search':
				$cleverly->display('main_search.tpl');
				break;
		}
	},
	'quotes' => function() {

	}
));
?>
