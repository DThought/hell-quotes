<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>{$title}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="{$template_path}/quotes.css" />
  </head>
  <body>
    <h1>{$title}</h1>
    <ul>
      {include name=nav}
    </ul>
    <h2>{$page_title}</h2>
    {include name=main}
  </body>
</html>
