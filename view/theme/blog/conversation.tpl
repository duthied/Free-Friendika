{{ for $threads as $thread }}
<div id="tread-wrapper-$thread.id" class="tread-wrapper">
        {{if $mode == display}}
        	{{ for $thread.items as $item }}
                {{ if $item.type == tag }}
                    {{ inc wall_item_tag.tpl }}{{ endinc }}
                {{ else }}
                    {{ inc $item.template }}{{ endinc }}
                {{ endif }}
          {{ endfor }}
        {{ else}}
            
           
           {{ inc $thread.items.0.template with $item=$thread.items.0 }}{{ endinc }}
            <a href="$thread.items.0.plink.href">Commenti: {{if $thread.num_comments}}$thread.num_comments{{ else }}0{{ endif }}</a>
            <hr>
	{{ endif }}
</div>
{{ endfor }}

<div id="conversation-end"></div>

{{ if $dropping }}
<a href="#" onclick="deleteCheckedItems();return false;">
	<span class="icon s22 delete text">$dropping</span>
</a>
{{ endif }}

