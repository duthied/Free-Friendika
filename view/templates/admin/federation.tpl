<script src="{{$baseurl}}/library/Chart.js-1.0.2/Chart.min.js"></script>
<canvas id="FederationChart" class="federation-graph"></canvas>
<div id="adminpage">
    <h1>{{$title}} - {{$page}}</h1>
    <p>{{$intro}}</p>
    {{if not $autoactive}}
	<p class="error-message">{{$hint}}</p>
    {{/if}}
    <p>{{$legendtext}}
    <ul>
    {{foreach $counts as $c}}
	{{if $c[0]['total'] > 0}}
	    <li>{{$c[0]['platform']}} ({{$c[0]['total']}})</li>
	{{/if}}
    {{/foreach}}
    </ul>
    </p>
</div>
<script>
var FedData = [
{{foreach $counts as $c}}
    { value: {{$c[0]['total']}}, label: "{{$c[0]['platform']}}", color: '{{$c[3]}}', highlight: "#EE90A1", },
{{/foreach}}
];
var ctx = document.getElementById("FederationChart").getContext("2d");
var myDoughnutChart = new Chart(ctx).Doughnut(FedData, { animateRotate : false, });
</script>

<table id="federation-stats">
{{foreach $counts as $c}}
    {{if $c[0]['total'] > 0}}
    <tr>
	    <th>{{$c[0]['platform']}}</th>
	    <th><strong>{{$c[0]['total']}}</strong></td>
	    <td>{{$c[0]['network']}}</td>
    </tr>
    <tr>
    <td colspan="3" class="federation-data">
    <canvas id="{{$c[2]}}Chart" class="federation-network-graph"></canvas>
    <script>
    var {{$c[2]}}data = [
    {{foreach $c[1] as $v}}
	{ value: {{$v['total']}}, label: '{{$v['version']}}', color: "{{$c[3]}}", highlight: "#EE90A1",},
    {{/foreach}}
    ];
    var ctx = document.getElementById("{{$c[2]}}Chart").getContext("2d");
    var my{{$c[2]}}DoughnutChart = new Chart(ctx).Doughnut({{$c[2]}}data, {animateRotate : false,});
    </script>
    <ul class="federation-stats">
    {{foreach $c[1] as $v}}
	<li>{{if ($c[0]['platform']==='Friendica' and  $version===$v['version']) }}<span class="version-match">{{$v['version']}}</span>{{else}}{{$v['version']}}{{/if}} ({{$v['total']}})</li>
    {{/foreach}}
    </ul>
    </td>
    </tr>
    {{/if}}
{{/foreach}}
</table>
