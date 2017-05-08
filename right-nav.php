<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
	<!-- Create the tabs -->
	<ul class="nav nav-tabs nav-justified control-sidebar-tabs">
		<li class="active"><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-user"></i></a></li>
		<?php if (UCUser::IsUserAdmin()){ ?>
		<li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
		<?php } ?>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<?php if($user) { ?>
		<!-- Home tab content -->
		<div class="tab-pane active" id="control-sidebar-home-tab">
		<h3 class="control-sidebar-heading">User Information</h3>
		<ul class="control-sidebar-menu">
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>account.php">
					<i class="menu-icon fa fa-user bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading">My Account</h4>		
						<p>user information</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>user_settings.php">
					<i class="menu-icon fa fa-cog bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading">Account Settings</h4>		
						<p>modify username, avatar, ...</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>logout.php">
					<i class="menu-icon fa fa-cog bg-red"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading">Logout</h4>		
						<p>say goodbye</p>
					</div>
				</a>
			</li>
		</ul>
		<?php } else { ?>
		<!-- Home tab content -->
		<div class="tab-pane active" id="control-sidebar-home-tab">
		<h3 class="control-sidebar-heading">User Information</h3>
		<ul class="control-sidebar-menu">
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>login.php">
					<i class="menu-icon fa fa-key bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Login</h4>		
						<p>login with your email and password</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>register.php">
					<i class="menu-icon fa fa-pencil-square-o bg-yellow"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Register</h4>		
						<p>create account</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>forgot-password.php">
					<i class="menu-icon fa fa-user-secret bg-yellow"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Forgot Password</h4>		
						<p>retrieve your login credentials</p>
					</div>
				</a>
			</li>
		</ul>
		<?php } ?>
		<!-- /.control-sidebar-menu -->
	
		<!--<h3 class="control-sidebar-heading">Tasks Progress</h3>
		<ul class="control-sidebar-menu">
			<li>
			<a href="javascript::;">
				<h4 class="control-sidebar-subheading">
				Custom Template Design
				<span class="pull-right-container">
					<span class="label label-danger pull-right">70%</span>
				</span>
				</h4>
	
				<div class="progress progress-xxs">
				<div class="progress-bar progress-bar-danger" style="width: 70%"></div>
				</div>
			</a>
			</li>
		</ul>-->
		<!-- /.control-sidebar-menu -->
	
		</div>
		<!-- /.tab-pane -->
		<?php if (UCUser::IsUserAdmin()){ ?>
		<!-- Settings tab content -->
		<div class="tab-pane" id="control-sidebar-settings-tab">
		<h3 class="control-sidebar-heading">Administration</h3>
		<ul class="control-sidebar-menu">
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>admin_configuration.php">
					<i class="menu-icon fa fa-cog bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Configuration</h4>		
						<p>general configuration</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>admin_users.php">
					<i class="menu-icon fa fa-users bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Users</h4>		
						<p>manage registered users</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>admin_permissions.php">
					<i class="menu-icon fa fa-lock bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Permissions</h4>		
						<p>manage groups permissions</p>
					</div>
				</a>
			</li>
			<li>
				<a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]?>admin_pages.php">
					<i class="menu-icon fa fa-lock bg-green"></i>	
					<div class="menu-info">
						<h4 class="control-sidebar-subheading"> Pages</h4>		
						<p>manage pages access</p>
					</div>
				</a>
			</li>
		</ul>		
		</div>
		<?php } ?>
		<!-- /.tab-pane -->
	</div>
</aside>
<!-- /.control-sidebar -->
<!-- Add the sidebar's background. This div must be placed
	immediately after the control sidebar -->
<div class="control-sidebar-bg"></div>