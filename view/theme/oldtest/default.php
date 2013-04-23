<?php
	if (is_ajax()) {
	 	$t = $a->template_engine('jsonificator');
		//echo "<hr><pre>"; var_dump($t->data); killme();
		echo json_encode($t->data);
		killme();
	} 
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php if(x($page,'title')) echo $page['title'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="<?php echo  $a->get_baseurl() ?>/view/theme/test/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  </head>
  <body>
    
    	
		<div class="navbar navbar-fixed-top">
		  <div class="navbar-inner">
		    <div class="container">
		 
		      <!-- .btn-navbar is used as the toggle for collapsed navbar content -->
		      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		      </a>
		 
		      <!-- Be sure to leave the brand out there if you want it shown -->
		      <a class="brand" href="#"><span data-bind="text: nav()"></span></a>
		 
		      <!-- Everything you want hidden at 940px or less, place within here -->
		      <div class="nav-collapse navbar-responsive-collapse collapse">
		        <ul class="nav"  data-bind="foreach: nav().nav">
		        	<li><a data-bind="href: url, text: text"></a></li>
		        </ul>
		      </div>
		 
		    </div>
		  </div>
		</div>    	
    	
    
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="<?php echo  $a->get_baseurl() ?>/view/theme/test/js/bootstrap.min.js"></script>
    <script src="<?php echo  $a->get_baseurl() ?>/view/theme/test/js/knockout-2.2.1.js"></script>
    <script src="<?php echo  $a->get_baseurl() ?>/view/theme/test/app/app.js"></script>
  </body>
</html>