<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>
<canvas id="FederationChart" style="width: 400px; height: 400px; float: right; margin: 20px;"></canvas>
<div id="adminpage">
    <h1>{{$title}} - {{$page}}</h1>
    <p>{{$intro}}</p>
    {{if not $autoactive}}
	<p class="error-message">{{$hint}}</p>
    {{/if}}
    <p>{{$legendtext}}
    <ul>
    {{foreach $counts as $c}}
    <li>{{$c[0]['platform']}} ({{$c[0]['count(*)']}})</li>
    {{/foreach}}
    </ul>
    </p>
</div>
<script>
var FedData = [
{{foreach $counts as $c}}
    { value: {{$c[0]['count(*)']}}, label: "{{$c[0]['platform']}}", color: "#90EE90", highlight: "#EE90A1", },
{{/foreach}}
];
var ctx = document.getElementById("FederationChart").getContext("2d");
var myDoughnutChart = new Chart(ctx).Doughnut(FedData, 
  {
	animateRotate : false,
  });
document.getElementById('FederationLegend').innerHTML = myDoughnutChart.generateLegend();
</script>

<table style="width: 100%">
{{foreach $counts as $c}}
<tr>
	<th>{{$c[0]['platform']}}</th>
	<th><strong>{{$c[0]['count(*)']}}</strong></td>
	<td>{{$c[0]['network']}}</td>
</tr>
<tr>
<td colspan="3" style="border-bottom: 1px solid #000;">
<canvas id="{{$c[2]}}Chart" style="width: 240px; height: 240px; float: left;
margin: 20px;"></canvas>
<script>
var {{$c[2]}}data = [
{{foreach $c[1] as $v}}
    { value: {{$v['count(*)']}}, label: '{{$v['version']}}', color: "#90EE90", highlight: "#EE90A1",},
{{/foreach}}
];
var ctx = document.getElementById("{{$c[2]}}Chart").getContext("2d");
var my{{$c[2]}}DoughnutChart = new Chart(ctx).Doughnut({{$c[2]}}data,
{animateRotate : false,});
</script>
<ul class="federation-stats">
{{foreach $c[1] as $v}}
<li>{{if ($c[0]['platform']==='Friendica' and  $version===$v['version']) }}<span class="version-match">{{$v['version']}}</span>{{else}}{{$v['version']}}{{/if}} ({{$v['count(*)']}})</li>
{{/foreach}}
</ul>
</td>
</tr>
{{/foreach}}
</table>
