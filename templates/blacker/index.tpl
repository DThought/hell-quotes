{include_php file='../../../lib/include.php'}
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
{php}
print_head('Hell Quotes');
{/php}
	</head>
	<body>
{php}
print_header();
{/php}
		<div id="main">
			<h1>{$title}</h1>
			{include name=messages}
			<h2>Quoth the Raven</h2>
			<ul class="nav pull-left">
				{include name=nav}
			</ul>
			{include name=main}
		</div>
{php}
print_footer(
	'Copyright &copy; 2015 Will Yu',
	'A service of Blacker House'
);
{/php}
	</body>
