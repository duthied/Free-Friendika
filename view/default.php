<!DOCTYPE html >
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
	<?php if(x($page,'nav')) echo $page['nav']; ?>
	<aside><?php if(x($page,'aside')) echo $page['aside']; ?></aside>
	<section><?php if(x($page,'content')) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
	<right_aside><?php if(x($page,'right_aside')) echo $page['right_aside']; ?></right_aside>
	<footer><?php if(x($page,'footer')) echo $page['footer']; ?></footer>
</body>
</html>

