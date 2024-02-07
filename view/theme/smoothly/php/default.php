<!DOCTYPE html >
<html>
<head>
  <title><?php if(!empty($page['title'])) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo Friendica\DI::baseUrl() ?>";</script>
  <script type="text/javascript">
	function ScrollToBottom(){
	window.scrollTo(0,document.body.scrollHeight);
	}
  </script>
  <?php if(!empty($page['htmlhead'])) echo $page['htmlhead'] ?>
</head>
<body>
	<header>
		<?php if(!empty($page['header'])) echo $page['header']; ?>
	</header>

	<?php if(!empty($page['nav'])) echo $page['nav']; ?>

	<aside><?php if(!empty($page['aside'])) echo $page['aside']; ?></aside>

	<section>
		<?php if(!empty($page['content'])) echo $page['content']; ?>
		<div id="pause"></div> <!-- The pause/resume Ajax indicator -->
		<div id="page-footer"></div>
	</section>

	<right_aside><?php if(!empty($page['right_aside'])) echo $page['right_aside']; ?></right_aside>

	<footer id="footer">
	<?php if(!empty($page['footer'])) echo $page['footer']; ?>
	</footer>

	<tools id="tools">
	<?php if (!empty($page['tools'])) echo $page['tools']; ?>
	<div id="scrollup" >
	<a class="item-scrollup" href="javascript:scrollTo(0,100000)"><img src="view/theme/smoothly/images/down.png" alt="to bottom" title="to bottom" /></a>
	<a class="item-scrollup" href="javascript:scrollTo(0,0)"><img src="view/theme/smoothly/images/up.png" alt="to top" title="to top" /></a>
	<a class="item-scrollup" href="logout"><img src="view/theme/smoothly/images/power.png" alt="power" title="power" /></a>
	</div>
	</tools>

	<?php if (!empty($page['bottom'])) echo $page['bottom']; ?>
</body>
</html>
