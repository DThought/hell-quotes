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
      <h2>{php}
$subtitles = array(
  'Never Forget',
  'Quoth the Raven',
  'The Walls Have Ears',
  'What Did You Say'
);

echo $subtitles[mt_rand(0, count($subtitles) - 1)];
{/php}</h2>
      <ul class="nav pull-left">
        {$nav}
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
