{section name=articleIndex loop=$articles}
 	<a href="{$link[title]}"><h1>{$articles[articleIndex].title}</h1></a>
 	<p>{$articles[articleIndex].description}<br>
    Categor&iacute;as:
    {section name=catIndex loop=$articles[articleIndex].category}
        {if $smarty.section.catIndex.last}
            {$articles[articleIndex].category[catIndex]}
        {else}
            {$articles[articleIndex].category[catIndex]},
        {/if}
    {/section}<br>
    <small>Autor: {$articles[articleIndex].author}</small><br>
 	<small>publicado por {$articles[articleIndex].pubDate}</small></p>
{/section}
