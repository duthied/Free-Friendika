<!DOCTYPE html >
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
    <div id="page">
        <?php 
            if(x($page,'nav')){
                // header image
                $img = $a->get_baseurl()."/view/theme/blog/headers/willow.jpg";
                echo str_replace("~blog.header.image~", $img, $page['nav']);
            }
        ?>
        <aside><?php if(x($page,'aside')) echo $page['aside']; ?></aside>
        <section><?php if(x($page,'content')) echo $page['content']; ?>
            <div id="page-footer"></div>
        </section>
        <right_aside><?php if(x($page,'right_aside')) echo $page['right_aside']; ?></right_aside>
        <footer><?php if(x($page,'footer')) echo $page['footer']; ?></footer>
    </div>
</body>
</html>

