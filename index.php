<?
$config = array(
	'page_titles' => array()
);

include('config.php');
include('lib/cleverly/Cleverly.class.php');

function generate_link($section, $src, $dest) {
	global $cleverly;

	return $cleverly->fetch($src == $dest ? 'pager_link_active.tpl' : 'pager_link.tpl', array(
		'link_index' => $dest,
		'link_url' => generate_url($section, $dest);
	));
}

function generate_url($section = NULL, $index = NULL) {
	if ($section == 'home') {
		$section = NULL;
	}

	if ($_config['pretty_url']) {

	} else {
		$url = './';

		if ($section) {
			$url .= '?page=' . $section;
		}

		if ($index) {
			$url .= ($section ? '&' : '?') . 'p=' . $index;
		}

		return $url;
	}
}

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

$order = '';
$section = @$_GET['page'] ? $_GET['page'] : 'home';

if (!isset($config[$section])) {
	$section = 'error';
}

switch ($section) {
	case 'latest':
		$order = 'ORDER BY `created` DESC';
		break;
	case 'browse':
		$order = 'ORDER BY `created`';
		break;
	case 'random':
		$order = 'ORDER BY RANDOM()';
		break;
	case 'top':
		$order = 'ORDER BY `score`';
		break;
}

$limit = 'LIMIT ' . $config['posts_per_page'];
$index = (int) @$_GET['p'];
$offset = 'OFFSET ' . $index * $config['posts_per_page'];
$nav = '';

foreach ($config['page_titles'] as $page => $title) {
	$vars = array(
		'link_name' => $page,
		'link_title' => $title,
		'link_url' => generate_url($page);
	);

	$nav .= $cleverly->fetch($page == $section ? 'nav_link_active.tpl' : 'nav_link.tpl', $vars);
}

$result = $pdo->prepare(<<<EOF
SELECT COUNT(*)
FROM `$config[table_quotes]`
EOF
	);

$result->execute();
$num_index = $result->fetchColumn();
$min_index = max(1, $index - 5);
$max_index = min($num_index - 2, $index + 5);
$pager = generate_link($section, $index, 0);

if ($index > 6) {
	$pager .= ' &ellipses; ';
}


for ($i = $min_index; $i <= $max_index; $i++) {
	$pager .= generate_link($section, $index, $i);
}

if ($index < $num_index - 7) {
	$pager .= ' &ellipses; ';
}

$pager .= generate_link($section, $index, $num_index - 1);

$cleverly->display('index.tpl', $config + array(
	'page_title' => @$config['page_titles'][$_GET['page']],
	'pager' => function() {
		echo $pager;
	},
	'nav' => function() {
		echo $nav;
	},
	'main' => function() {
		global $cleverly;
		global $config;

		switch (@$_GET['page']) {
			case '':
				$cleverly->display('main_home.tpl');
				break;
			case 'browse':
				$cleverly->display('main_paged.tpl');
				break;
			case 'latest':
			case 'random':
			case 'top':
				$cleverly->display('main_unpaged.tpl');
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
