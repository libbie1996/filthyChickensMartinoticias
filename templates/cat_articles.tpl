{section name=index loop=$articles}
 	<a href="{$link[title]}"><h1>{$articles[index].title}</h1></a>
 	<p>{$articles[index].description}<br>
    <small>Autor: {$articles[index].author}</small><br>
 	<small>publicidad por {$articles[index].pubDate}</small></p><br />
{/section}
