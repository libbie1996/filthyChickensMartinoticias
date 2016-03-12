{section name=title loop=$titles}
 	<a href="{$link[title]}"><h1>{$titles[title]}</h1></a>
 	<p>{$description[title]}<br>
 	<small>published on {$pubDate[title]}</small></p><br />
{/section}