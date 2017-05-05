<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

<!-- sidebar: style can be found in sidebar.less -->
<section class="sidebar">

	<!-- Sidebar user panel (optional) -->
	<?php //Links for logged in user
	if($user) { ?> 
	<div class="user-panel">
	<div class="pull-left image">
		<?php if (!empty($user->Avatar())) { ?>
		<img src="data:image/png;base64,<?php echo $user->Avatar()?>" class="img-circle" alt="User Image">
		<?php } else { ?>
		<img
			src="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ."dist/img/noavatar.jpg"?>"
			class="img-circle" alt="User Image">
		<?php } ?>
	</div>
	<div class="pull-left info">
		<p><?php echo $user->DisplayName()?></p>
		<!-- Status -->
		<a href="#"><i class="fa fa-circle text-success"></i> Online</a>
	</div>
	</div>
	<?php } ?>

	<!-- search form (Optional) -->
	<!--<form action="#" method="get" class="sidebar-form">
	<div class="input-group">
		<input type="text" name="q" class="form-control" placeholder="Search...">
			<span class="input-group-btn">
			<button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
			</button>
			</span>
	</div>
	</form>-->
	<!-- /.search form -->

	<!-- Sidebar Menu -->
	<ul class="sidebar-menu">
	<li class="header"></li>
	<!-- Optionally, you can add icons to the links -->
	<?php
	foreach ($GLOBALS["config"]["leftnav"] as $value) {		
		$is_dir = is_array($value["link"]);
		if ($is_dir) { 
		$active = false;
		foreach ($value["link"] as $sub_value) { 
			if($_SERVER['PHP_SELF'] == $sub_value["link"]) {
				$active = true;
				break;
			}
        } ?>	
		<li <?php if( $active ) echo 'class="active treeview"'; else echo 'class="treeview"'; ?>>
			<a href="#">
	            <i class="<?php echo $value["icon"]?>"></i>
	            <span><?php echo $value["name"]?></span>
        	</a>
        	<ul class="treeview-menu">
        		<?php foreach ($value["link"] as $sub_value) { 
        		$active_sub = $_SERVER['PHP_SELF'] == $sub_value["link"]; ?>
	            <li <?php if( $active_sub ) echo 'class="active"' ?>>
					<a href="<?php echo $sub_value["link"]?>" target="<?php echo(isset($value["target"]) ? $value["target"] : "_self") ?>">
						<i class="<?php echo $sub_value["icon"]?>"></i> 
						<span><?php echo $sub_value["name"]?></span>
					</a>
				</li>
	            <?php } ?>
          </ul>
		</li>
		<?php } else { 
		$active = $_SERVER['PHP_SELF'] == $value["link"]; ?>	
		<li <?php if( $active ) echo 'class="active"' ?>>
			<a href="<?php echo $value["link"]?>" target="<?php echo(isset($value["target"]) ? $value["target"] : "_self") ?>">
				<i class="<?php echo $value["icon"]?>"></i> 
				<span><?php echo $value["name"]?></span>
			</a>
		</li>
		<?php } ?>
	<?php } ?>	
	<!--<li><a href="#"><i class="fa fa-link"></i> <span>Another Link</span></a></li>-->	
	<!--<li class="treeview">
		<a href="#"><i class="fa fa-link"></i> <span>Multilevel</span>
		<span class="pull-right-container">
			<i class="fa fa-angle-left pull-right"></i>
		</span>
		</a>
		<ul class="treeview-menu">
		<li><a href="#">Link in level 2</a></li>
		<li><a href="#">Link in level 2</a></li>
		</ul>
	</li>-->
	</ul>
	<!-- /.sidebar-menu -->
</section>
<!-- /.sidebar -->
</aside>