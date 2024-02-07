<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional //EN">
<html>
<head>
	<title>{{$title}}</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body>
	<table style="border:1px solid #ccc">
	<tbody>
		<tr>
			<td style="background:#084769; color:#FFFFFF; font-weight:bold; font-family:'lucida grande', tahoma, verdana,arial, sans-serif; padding: 4px 8px; vertical-align: middle; font-size:16px; letter-spacing: -0.03em; text-align: left;">
				<img style="width:32px;height:32px; float:left;" src="{{$banner}}" alt="Friendica Banner">
				<div style="padding:7px; margin-left: 5px; float:left; font-size:18px;letter-spacing:1px;">{{$product}}</div>
				<div style="clear: both;"></div>
			</td>
		</tr>
		<tr>
			<td>
				{{$htmlversion nofilter}}
			</td>
		</tr>
	</tbody>
	</table>
</body>
</html>
