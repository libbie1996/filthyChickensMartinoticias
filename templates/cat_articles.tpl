{section name=index loop=$articles}
 	<a href="{$link[title]}"><h1>{$articles[index].title}</h1></a>
 	<p>{$articles[index].description}<br>
    <small>written by {$articles[index].author}</small><br>
 	<small>published on {$articles[index].pubDate}</small></p><br />
{/section}
