<?
include('lib/cleverly/Cleverly.class.php');
include('include.php');

define('QUOTES_DEFAULT_PAGE', 'latest');

function generate_link($section, $src, $dest) {
  global $cleverly;

  return $cleverly->fetch(
    $src == $dest ? 'pager_link_active.tpl' : 'pager_link.tpl', array(
      'link_index' => $dest + 1,
      'link_url' => generate_url($section, $dest)
    )
  );
}

function generate_url($section = NULL, $index = NULL) {
  global $base;
  global $config;

  if ($section == 'home') {
    $section = NULL;
  }

  if ($config['pretty_url']) {

  } else {
    $url = $base;

    if ($section) {
      $url .= '?page=' . $section;
    }

    if ($index) {
      $url .= ($section ? '&' : '?') . 'p=' . $index;
    }

    return $url;
  }
}

$base = './';
$user = NULL;
$error = '';
$success = '';

switch ($config['auth_type']) {
  case 'basic':
    $user = $_SERVER['PHP_AUTH_USER'];
    break;
}

if (@$_GET['page'] == QUOTES_DEFAULT_PAGE) {
  header('Location: ./', true, 301);
}

$create = !file_exists($config['db_path']);
$pdo = new PDO('sqlite:' . $config['db_path']);
$cleverly = new Cleverly();

if ($config['pretty_print']) {
  $cleverly->preserveIndent = true;
}

$cleverly->setTemplateDir(__DIR__ . '/' . $config['template_path']);

if ($create) {
  $pdo->exec(
    <<<EOF
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

  if ($config['table_fts']) {
    quotes_create_fts();
  }

  $pdo->exec(
    <<<EOF
CREATE TABLE `$config[table_votes]` (
  `quote` integer NOT NULL,
  `user` varchar(64) NOT NULL,
  `direction` integer NOT NULL,
  `updated` datetime NOT NULL
)
EOF
  );
}

$order = '';
$section = @$_GET['page'] ? $_GET['page'] : QUOTES_DEFAULT_PAGE;

if (!isset($config['page_titles'][$section])) {
  $section = 'error';
}

if (isset($_GET['search'])) {
  if (!$config['table_fts']) {
    $error = 'Full-text search is not enabled.';
  } else if (!$_GET['search']) {
    $error = 'Please enter a search query.';
  } else {
    $section = 'search';
  }
}

if (isset($_POST['quote'])) {
  if ($_POST['quote']) {
    $statement = $pdo->prepare(
      <<<EOF
INSERT INTO `$config[table_quotes]` (
  `quote`,
  `tags`,
  `user`,
  `created`
)
VALUES (
  :quote,
  :tags,
  :user,
  DATETIME('now')
)
EOF
    );

    $statement->execute(array(
      ':quote' => $_POST['quote'],
      ':tags' => trim(preg_replace('/\W+/', ' ', @$_POST['tags'])),
      ':user' => $user
    ));

    $_GET['q'] = $pdo->lastInsertId();
    $success =
        "Successfully added <a href=\"$base?q=$_GET[q]\">quote $_GET[q]</a>.";
  } else {
    $error = 'Please enter a quote.';
  }
}

if (isset($_GET['q'])) {
  $quote = (int)$_GET['q'];

  $parameters = array(
    ':user' => $user,
    ':quote' => $quote
  );

  switch (@$_GET['action']) {
    case 'upvote':
    case 'downvote':
    case 'unvote':
      $statement = $pdo->prepare(
        <<<EOF
SELECT `direction`
FROM `votes`
WHERE `quote` = :quote
  AND `user` = :user
EOF
      );

      $statement->execute($parameters);

      if ($voted = (int)$statement->fetch(PDO::FETCH_COLUMN)) {
        $statement = $pdo->prepare(
          <<<EOF
UPDATE `$config[table_quotes]`
SET `score` = `score` - $voted
WHERE `id` = :quote
EOF
        );

        $statement->execute(array(
          ':quote' => $quote
        ));
      }

      $statement = $pdo->prepare(
        <<<EOF
DELETE FROM `votes`
WHERE `quote` = :quote
  AND `user` = :user
EOF
      );

      $statement->execute($parameters);

      if ($_GET['action'] != 'unvote') {
        $voted = $_GET['action'] == 'upvote' ? 1 : -1;

        $statement = $pdo->prepare(
          <<<EOF
INSERT INTO `votes` (
  `quote`,
  `user`,
  `direction`,
  `updated`
)
VALUES (
  :quote,
  :user,
  $voted,
  DATETIME('now')
)
EOF
        );

        $statement->execute($parameters);

        $statement = $pdo->prepare(
          <<<EOF
UPDATE `$config[table_quotes]`
SET `score` = `score` + $voted
WHERE `id` = :quote
EOF
        );

        $statement->execute(array(
          ':quote' => $quote
        ));
      }

      break;
  }

  $section = 'single';
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
  case 'search':
    $order = <<<EOF
INNER JOIN `$config[table_fts]` ON `$config[table_quotes]`.`id` = `$config[table_fts]`.`rowid`
WHERE `$config[table_fts]` MATCH :query
ORDER BY `$config[table_fts]`.`rank`
EOF;
    break;
  case 'single':
    $order = 'WHERE `id` = ' . (int)$_GET['q'];
    break;
  case 'top':
    $order = 'ORDER BY `score` DESC';
    break;
}

$index = (int)@$_GET['p'];
$limit = "LIMIT $config[quotes_per_page] OFFSET " .
    $index * $config['quotes_per_page'];
$nav = '';

foreach ($config['page_titles'] as $page => $title) {
  $vars = array(
    'link_name' => $page,
    'link_title' => $title,
    'link_url' => generate_url($page)
  );

  $nav .= $cleverly->fetch(
    $page == $section ? 'nav_link_active.tpl' : 'nav_link.tpl', $vars
  );
}

$statement = $pdo->prepare(
  <<<EOF
SELECT COUNT(*)
FROM `$config[table_quotes]`
EOF
);

$statement->execute();

$num_index = (int)(
  ($statement->fetch(PDO::FETCH_COLUMN) - 1) / $config['quotes_per_page']
) + 1;

$min_index = max(1, $index - 5);
$max_index = min($num_index - 2, $index + 5);
$pager = generate_link($section, $index, 0);

if ($index > 6) {
  $pager .= ' &hellip; ';
}

for ($i = $min_index; $i <= $max_index; $i++) {
  $pager .= generate_link($section, $index, $i);
}

if ($index < $num_index - 7) {
  $pager .= ' &hellip; ';
}

if ($num_index != 0) {
  $pager .= generate_link($section, $index, $num_index - 1);
}

$quote = 0;
$voted = 0;
$page_title = @$config['page_titles'][$section];

$cleverly->display('index.tpl', $config + array(
  'page_title' => $page_title ? $page_title : ucfirst($section),
  'pager' => function() {
    global $pager;

    echo $pager;
  },
  'nav' => function() {
    global $nav;

    echo $nav;
  },
  'main' => function() {
    global $cleverly;
    global $config;
    global $section;

    switch ($section) {
      case 'browse':
      case 'latest':
      case 'top':
        $cleverly->display('main_paged.tpl');
        break;
      case 'random':
      case 'search':
      case 'single':
        $cleverly->display('main_unpaged.tpl');
        break;
      case 'add':
        $cleverly->display('main_add.tpl');
        break;
      case 'admin':
        $cleverly->display('main_admin.tpl');
        break;
    }
  },
  'messages' => function() {
    global $cleverly;
    global $error;
    global $success;

    if ($error) {
      $cleverly->display('message_error.tpl', array(
        'message' => $error
      ));
    }

    if ($success) {
      $cleverly->display('message_success.tpl', array(
        'message' => $success
      ));
    }
  },
  'quotes' => function() {
    global $cleverly;
    global $config;
    global $limit;
    global $offset;
    global $order;
    global $pdo;
    global $quote;
    global $section;
    global $user;
    global $voted;

    $statement = $pdo->prepare(
      <<<EOF
SELECT `$config[table_quotes]`.`id` AS `id`,
  `$config[table_quotes]`.`quote` AS `quote`,
  `$config[table_quotes]`.`tags` AS `tags`,
  `$config[table_quotes]`.`score` AS `score`,
  `$config[table_quotes]`.`user` AS `user`,
  `$config[table_quotes]`.`created` AS `created`,
  `$config[table_votes]`.`direction` AS `direction`
FROM `$config[table_quotes]`
LEFT JOIN `$config[table_votes]` ON `$config[table_votes]`.`quote` = `$config[table_quotes]`.`id`
  AND `$config[table_votes]`.`user` = :user
$order
$limit
$offset
EOF
    );

    $parameters = array(
      ':user' => $user
    );

    if ($section === 'search') {
      $parameters[':query'] = $_GET['search'];
    }

    $statement->execute($parameters);

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $quote = (int)@$row['id'];
      $voted = (int)@$row['direction'];

      $cleverly->display('quotes_quote.tpl', array(
        'quote_id' => $row['id'],
        'quote_date' => $row['created'],
        'quote_score' => str_replace('-', '&minus;', $row['score']),
        'quote_tags' => (string) $row['tags'],
        'quote_text' => nl2br(htmlentities($row['quote'], NULL, 'UTF-8')),
        'quote_url' => '?q=' . $row['id']
      ));
    }
  },
  'score' => function() {
    global $base;
    global $cleverly;
    global $pdo;
    global $quote;
    global $voted;

    $parameters = array(
      'upvote_url' => "$base?action=upvote&q=$quote",
      'downvote_url' => "$base?action=downvote&q=$quote",
      'unvote_url' => "$base?action=unvote&q=$quote"
    );

    switch ($voted) {
      case -1:
        $cleverly->display('score_control_downvoted.tpl', $parameters);
        break;
      case 0:
        $cleverly->display('score_control.tpl', $parameters);
        break;
      case 1:
        $cleverly->display('score_control_upvoted.tpl', $parameters);
        break;
    }
  },
  'search' => function() {
    global $cleverly;
    global $config;

    if ($config['table_fts']) {
      $cleverly->display('search.tpl', array(
        'search_query' => htmlentities(@$_GET['search'], null, 'UTF-8')
      ));
    }
  }
));
?>
