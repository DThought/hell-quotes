{include_php file='../lib/include.php'}
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
{php}
print_head('Hell Quotes');
{/php}  </head>
  <body>
{php}
print_header();
{/php}    <div id="main">
      <h1>{$title}</h1>
      {include from=$messages}
{php}
$subtitles = array(
  'Never Forget',
  'Quoth the Raven',
  'The Walls Have Ears',
  'What Did You Say'
);

$subtitle = $subtitles[mt_rand(0, count($subtitles) - 1)];

echo <<<EOF
      <h2>$subtitle</h2>

EOF;
{/php}      <ul class="nav pull-left">
        {include from=$nav}
      </ul>
      {include from=$search}
      {include from=$main}
    </div>
{php}
print_footer(
  'Copyright &copy; 2015 Will Yu',
  'A service of Blacker House'
);
{/php}  </body>
</html>
