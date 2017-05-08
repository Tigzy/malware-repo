<!-- Main Header -->
<header class="main-header">

<!-- Logo -->
<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ?>" class="logo">
	<!-- mini logo for sidebar mini 50x50 pixels -->
	<span class="logo-mini"><?php echo $user_settings->WebsiteShortName() ?></span>
	<!-- logo for regular state and mobile devices -->
	<span class="logo-lg"><?php echo $user_settings->WebsiteName() ?></span>
</a>

<!-- Header Navbar -->
<nav class="navbar navbar-static-top" role="navigation">
	<!-- Sidebar toggle button-->
	<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
	<span class="sr-only">Toggle navigation</span>
	</a>
	<!-- Navbar Right Menu -->
	<div class="navbar-custom-menu">
	<ul class="nav navbar-nav">
		<!-- User Account Menu -->
		<li class="dropdown user user-menu">
		<!-- Menu Toggle Button -->
		<?php if($user) { ?> 
		<a href="#" class="dropdown-toggle" data-toggle="dropdown">
			<!-- The user image in the navbar-->
			<!--<img src="dist/img/user2-160x160.jpg" class="user-image" alt="User Image">-->
			<?php if (!empty($user->Avatar())) { ?>
			<img src="data:image/png;base64,<?php echo $user->Avatar()?>" class="user-image" alt="User Image">
			<?php } else { ?>
			<img src="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ."dist/img/noavatar.jpg"?>" class="user-image" alt="User Image">
			<?php } ?>
			<!-- hidden-xs hides the username on small devices so only the image appears. -->
			<span class="hidden-xs"><?php echo $user->DisplayName()?></span>
		</a>            
		<ul class="dropdown-menu">
			<!-- The user image in the menu -->
			<li class="user-header">
			<?php if (!empty(UCUser::getCurrentUser()->Avatar())) { ?>
			<img src="data:image/png;base64,<?php echo $user->Avatar()?>" class="img-circle" alt="User Image">
			<?php } else { ?>
			<img src="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ."dist/img/noavatar.jpg"?>" class="img-circle" alt="User Image">
			<?php } ?>
			<p>
				<?php echo $user->DisplayName()?>
				<small><?php echo $user->Title()?></small>
			</p>
			</li>
			<!-- Menu Body -->
			<!--<li class="user-body">
			<!--<div class="row">
				<div class="col-xs-4 text-center">
				<a href="#">Followers</a>
				</div>
				<div class="col-xs-4 text-center">
				<a href="#">Sales</a>
				</div>
				<div class="col-xs-4 text-center">
				<a href="#">Friends</a>
				</div>
			</div>-->
			<!-- /.row -->
			<!--</li>-->
			<!-- Menu Footer-->
			<li class="user-footer">
			<div class="pull-left">
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>account.php" class="btn btn-default btn-flat">Profile</a>
			</div>
			<div class="pull-right">
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>logout.php" class="btn btn-default btn-flat">Sign out</a>
			</div>
			</li>
		</ul>
		<?php } else { ?>
		<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>login.php" class="dropdown-toggle">
			<!-- The user image in the navbar-->
			<img src="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ."dist/img/noavatar.jpg"?>" class="user-image" alt="User Image">
			<!-- hidden-xs hides the username on small devices so only the image appears. -->
			<span class="hidden-xs">Login</span>
		</a>           
		<?php } ?>
		</li>
		<!-- Control Sidebar Toggle Button -->
		<li>
		<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
		</li>
	</ul>
	</div>
</nav>
</header>