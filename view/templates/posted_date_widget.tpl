<script>

function showHideDates() {
        if( $('#posted-date-dropdown').is(':visible')) {
                $('#posted-date-dropdown').hide();
                $('#posted-date-collapse').html(window.showMore);
                
        }
        else {
                $('#posted-date-dropdown').show();
                $('#posted-date-collapse').html(window.showFewer);
            }
        }
</script>		


<div id="datebrowse-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<script>function dateSubmit(dateurl) { window.location.href = dateurl; } </script>
	<ul id="posted-date-selector" class="datebrowse-ul">
		{{foreach $dates as $y => $arr}}
		{{if $y == $cutoff_year}}
		</ul>
		<div id="posted-date-dropdown" style="display: none;">
		<ul id="posted-date-selector-drop" class="datebrowse-ul">
		{{/if}} 
		<li id="posted-date-selector-year-{{$y}}" class="tool">
			<a class="datebrowse-link" href="#" onclick="openClose('posted-date-selector-{{$y}}'); return false;">{{$y}}</a>
		</li>
		<div id="posted-date-selector-{{$y}}" style="display: none;">
			<ul class="posted-date-selector-months datebrowse-ul">
				{{foreach $arr as $d}}
				<li class="tool">
					<a class="datebrowse-link" href="#" onclick="dateSubmit('{{$url}}/{{$d.1}}/{{$d.2}}'); return false;">{{$d.0}}</a></li>
				</li>
				{{/foreach}}
			</ul>
		</div>
		{{/foreach}}
		{{if $cutoff}}
		</div>
                <ul class="datebrowse-ul">
		<li onclick="showHideDates(); return false;" id="posted-date-collapse" class="fakelink tool">{{$showmore}}</li>
                </ul>
		{{/if}}
	</ul>
</div>
