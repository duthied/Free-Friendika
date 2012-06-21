<header id="branding" role="banner">
    <hgroup>
        <h1 id="site-title"><span><a href="$baseurl">Friendica</a></span></h1>
        <h2 id="site-description">Previewing Another WordPress Blog</h2>
    </hgroup>
    
    <div id="site-location">$sitelocation</div>
    
    <a id="headerimage" href="$baseurl">
        <img width="1000" height="288" alt="" src="~blog.header.image~">
    </a>
    
     <form id="searchform" action="$baseurl/search" method="get" role="search">
        <label class="assistive-text" for="search">Search</label>
        <input id="search" class="field" type="text" placeholder="Search" name="search">
        <input id="searchsubmit" class="submit" type="submit" value="Search" name="submit">
    </form>
    
    <nav id="access" role="navigation">
        <div class="menu">
            <ul>
                {{ if $nav.home }}
                    <li class="$sel.home"><a id="nav-home-link" class="nav-commlink $nav.home.2" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a></li>
                    <span id="home-update" class="nav-ajax-left"></span>
                {{ endif }}

                {{ if $nav.community }}<li class="$sel.community"><a id="nav-community-link" class="nav-commlink $nav.community.2 " href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a></li>{{ endif }}

                {{ if $nav.login }}<li class="$sel.login"><a id="nav-login-link" class="nav-login-link $nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a></li>{{ endif }}
            
                {{ if $nav.register }}<li class="$sel.register"><a id="nav-register-link" class="nav-commlink $nav.register.2 " href="$nav.register.0" title="$nav.register.3" >$nav.register.1</a></li>{{ endif }}
            
                {{ if $nav.apps }}<li class="$sel.apps"><a id="nav-apps-link" class="nav-link $nav.apps.2" href="$nav.apps.0" title="$nav.apps.3" >$nav.apps.1</a></li>{{ endif }}

        {#<li><a id="nav-search-link" class="nav-link $nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a></li> #}

                {{ if $nav.directory }}<li class="$sel.directory"><a id="nav-directory-link" class="nav-link $nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a></li>{{ endif }}

                {{ if $nav.help }} <li class="$sel.help"><a id="nav-help-link" class="nav-link $nav.help.2" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a></li>{{ endif }}
            </ul>
        </div>
    </nav>
    {{ if $nav.logout }}
    <nav id="tools" role="navigation">
        <div class="user">
            <ul>
                {{ if $userinfo }}
                    <li><a href="$nav.home.0" title="$sitelocation"><img src="$userinfo.icon" alt="$userinfo.name">$userinfo.name</a>
                        <ul id="nav-user-menu" class="menu-popup">
                            {{ for $nav.usermenu as $usermenu }}
                                <li><a class="$usermenu.2" href="$usermenu.0" title="$usermenu.3">$usermenu.1</a></li>
                            {{ endfor }}
                            
                            
                            {{ if $nav.contacts }}<li><a class="$nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a></li>{{ endif }}	

                            {{ if $nav.manage }}<li><a class="$nav.manage.2 sep" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a></li>{{ endif }}				
                            
                            {{ if $nav.settings }}<li><a class="$nav.settings.2 sep" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a></li>{{ endif }}
                            {{ if $nav.admin }}<li><a class="$nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a></li>{{ endif }}
                            
                            {{ if $nav.logout }}<li><a class="$nav.logout.2 sep" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a></li>{{ endif }}
                        </ul>
                    </li>
                {{ endif }}
                
                    {{ if $nav.network }}
                    <li class="$sel.network">
                        <a class="$nav.network.2" href="$nav.network.0" title="$nav.network.3" >$nav.network.1</a>
                        <span id="net-update" class="nav-notify"></span>
                    </li>
                    {{ endif }}
                
                {{ if $nav.notifications }}
                    <li class="$sel.notifications">
                        <a id="nav-notifications-linkmenu" class="nav-commlink" href="$nav.notifications.0" rel="#nav-notifications-menu" title="$nav.notifications.1">$nav.notifications.1</a>
                        <span id="notify-update" class="nav-ajax-left"></span>
                        <ul id="nav-notifications-menu" class="menu-popup">
                            <li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
                            <li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
                            <li class="empty">$emptynotifications</li>
                        </ul>
                    </li>
                {{ endif }}	
                
                {{ if $nav.introductions }}
                    <li class="$sel.introductions">
                        <a id="nav-notify-link" class="nav-commlink $nav.introductions.2" href="$nav.introductions.0" title="$nav.introductions.3" >$nav.introductions.1</a>
                        <span id="intro-update" class="nav-ajax-left"></span>
                    </li>
                {{ endif }}
	
                {{ if $nav.messages }}
                    <li class="$sel.messages">
                        <a id="nav-messages-link" class="nav-commlink $nav.messages.2" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a>
                        <span id="mail-update" class="nav-ajax-left"></span>
                    </li>
                {{ endif }}
            </ul>		
        </div>
    </nav>
    {{ endif }}
</header>

<script type="text/plain"  id="nav-notifications-template"  rel="template">
	<li class="{4}"><a href="{0}"><img src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a></li>
</script>
