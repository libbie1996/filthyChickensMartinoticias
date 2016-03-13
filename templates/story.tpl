<h1>{$title}</h1>
<p>{$intro}</p>
{img src="{$img}" alt="{$imgAlt}"}
{section name=content loop=$content}
 	<p>{$content[content]}</p>
{/section}