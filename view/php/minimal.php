<!DOCTYPE html >
<html>
<head>
  <title><?php if(!empty($page['title'])) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo Friendica\Core\System::baseUrl() ?>";</script>
  <?php if(!empty($page['htmlhead'])) echo $page['htmlhead'] ?>
</head>
<body>
	<section class="minimal" style="margin:0px!important; padding:0px!important; float:none!important;display:block!important;"><?php if(!empty($page['content'])) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
</body>
</html>
