<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->

<?php 
  require_once(__DIR__."/src/config.php");
  require_once(__DIR__."/src/lib/usercake/init.php");
  if (!UCUser::CanUserAccessUrl($_SERVER['PHP_SELF'])) { die();}
  $user = UCUser::getCurrentUser();
  $errors = array();
  $successes = array();
  $userId = $_GET['id'];
  
  //Check if selected user exists
  if(!UCUser::IdExists($userId)){ header("Location: admin_users.php"); die(); }
  
  $userdetails = new UCUser($userId); //Fetch user details
  
  //Forms posted
  if(!empty($_POST))
  {	
    //Delete selected account
    if(!empty($_POST['delete']))
    {
      $deletions = $_POST['delete'];
      if ($deletion_count = $user_db->UsersDelete($deletions)) {
        $successes[] = lang("ACCOUNT_DELETIONS_SUCCESSFUL", array($deletion_count));
      }
      else {
        $errors[] = lang("SQL_ERROR");
      }
    }
    else
    {
      //Update display name
      if ($userdetails->DisplayName() != $_POST['display'])
      {
        $displayname = trim($_POST['display']);
        
        //Validate display name
        if($user_db->UserDisplayNameInUse($displayname))
        {
          $errors[] = lang("ACCOUNT_DISPLAYNAME_IN_USE",array($displayname));
        }
        elseif(minMaxRange(5,25,$displayname))
        {
          $errors[] = lang("ACCOUNT_DISPLAY_CHAR_LIMIT",array(5,25));
        }
        elseif(!ctype_alnum($displayname)){
          $errors[] = lang("ACCOUNT_DISPLAY_INVALID_CHARACTERS");
        }
        else {
       	  if ($userdetails->UpdateDisplayName($displayname)){
            $successes[] = lang("ACCOUNT_DISPLAYNAME_UPDATED", array($displayname));
          }
          else {
            $errors[] = lang("SQL_ERROR");
          }
        }
        
      }
      else {
        $displayname = $userdetails->DisplayName();
      }
      
      //Activate account
      if(isset($_POST['activate']) && $_POST['activate'] == "activate")
      {
      	if ($user_db->UserSetActive($userdetails->Activationtoken())){
          $successes[] = lang("ACCOUNT_MANUALLY_ACTIVATED", array($displayname));
        }
        else {
          $errors[] = lang("SQL_ERROR");
        }
      }
      
      //Update email
      if ($userdetails->Email() != $_POST['email'])
      {
        $email = trim($_POST["email"]);
        
        //Validate email
        if(!UCMail::isAddressValid($email))
        {
          $errors[] = lang("ACCOUNT_INVALID_EMAIL");
        }
        elseif($user_db->UserEmailInUse($email))
        {
          $errors[] = lang("ACCOUNT_EMAIL_IN_USE",array($email));
        }
        else 
        {
          if ($userdetails->UpdateEmail($email)){
            $successes[] = lang("ACCOUNT_EMAIL_UPDATED");
          }
          else {
            $errors[] = lang("SQL_ERROR");
          }
        }
      }
      
      //Update title
      if ($userdetails->Title() != $_POST['title'])
      {
        $title = trim($_POST['title']);
        
        //Validate title
        if(minMaxRange(1,50,$title)){
          $errors[] = lang("ACCOUNT_TITLE_CHAR_LIMIT",array(1,50));
        }
        else {
          if ($userdetails->UpdateTitle($title)){
            $successes[] = lang("ACCOUNT_TITLE_UPDATED", array ($displayname, $title));
          }
          else {
            $errors[] = lang("SQL_ERROR");
          }
        }
      }
      
      //Remove permission level
      if(!empty($_POST['removePermission']))
      {
        $remove = $_POST['removePermission'];
        if ($deletion_count = UCPermission::RemoveUserPermission($userId, $remove)){
          $successes[] = lang("ACCOUNT_PERMISSION_REMOVED", array ($deletion_count));
        }
        else {
          $errors[] = lang("SQL_ERROR");
        }
      }
      
      if(!empty($_POST['addPermission']))
      {
        $add = $_POST['addPermission'];
        if ($addition_count = UCPermission::AddUserPermission($userId, $add)){
          $successes[] = lang("ACCOUNT_PERMISSION_ADDED", array ($addition_count));
        }
        else {
          $errors[] = lang("SQL_ERROR");
        }
      }
      
      $userdetails = new UCUser($userId);
    }
  }
  
  $userPermission = UCPermission::GetUserPermissions($userId);
  $permissionData = UCPermission::GetPermissions();
?> 

<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo $user_settings->WebsiteName() ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="plugins/ionicons/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
        page. However, you can choose any other skin. Make sure you
        apply the skin class to the body tag so the changes take effect.
  -->
  <link rel="stylesheet" href="dist/css/skins/skin-blue.min.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<!--
BODY TAG OPTIONS:
=================
Apply one or more of the following classes to get the
desired effect
|---------------------------------------------------------|
| SKINS         | skin-blue                               |
|               | skin-black                              |
|               | skin-purple                             |
|               | skin-yellow                             |
|               | skin-red                                |
|               | skin-green                              |
|---------------------------------------------------------|
|LAYOUT OPTIONS | fixed                                   |
|               | layout-boxed                            |
|               | layout-top-nav                          |
|               | sidebar-collapse                        |
|               | sidebar-mini                            |
|---------------------------------------------------------|
-->
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php  include(__DIR__."/top-nav.php"); ?> 
  <?php  include(__DIR__."/left-nav.php"); ?> 
  
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header"> 
           
      <div id='content'>
        <div id='main'>
          
          <!-- Horizontal Form -->
          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">User Administration</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start --> 
            <form name='adminUser' action="<?php echo($_SERVER['PHP_SELF'] . "?id=" . $userId) ?>" method="post" class="form-horizontal"> 
              <div class="box-body"> 
                 
                <?php
                foreach($errors as $error) { ?>
                  <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?php echo $error ?>
                  </div>      
                <?php } ?>
                
                <?php
                foreach($successes as $success) { ?>
                  <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?php echo $success ?>
                  </div>      
                <?php } ?>
                 
                <div class="form-group">
                    <label for="user_avatar" class="col-sm-2 control-label">Avatar</label>
                    <div class="col-sm-10">
                        <?php if (!empty($userdetails->Avatar())) { ?>
                            <img src="data:image/png;base64,<?php echo $userdetails->Avatar() ?>" width="64" height="64" class="img-circle" alt="User Image">
                        <?php } else { ?>
                            <img src="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ."dist/img/noavatar.jpg"?>" width="64" height="64" class="img-circle" alt="User Image">
                        <?php } ?>
                    </div>                    
                </div>                 
                <div class="form-group">
                  <label for="user_id" class="col-sm-2 control-label">User ID</label>
                  <div class="col-sm-10">
                    <input type="text" id="user_id" value='<?php echo $userdetails->Id() ?>' class="form-control" disabled>
                  </div>
                </div>
                <div class="form-group">
                  <label for="user_name" class="col-sm-2 control-label">Username</label>
                  <div class="col-sm-10">
                    <input type="text" id="user_name" value='<?php echo $userdetails->UserName() ?>' class="form-control" disabled>
                  </div>
                </div> 
                <div class="form-group">
                  <label for="user_display_name" class="col-sm-2 control-label">Display Name</label>
                  <div class="col-sm-10">
                    <input type="text" id="user_display_name" name='display' value='<?php echo $userdetails->DisplayName() ?>' class="form-control">
                  </div>
                </div>
                <div class="form-group">
                  <label for="user_email" class="col-sm-2 control-label">Email</label>
                  <div class="col-sm-10">
                    <input type="text" id="user_email" name='email' value='<?php echo $userdetails->Email() ?>' class="form-control">
                  </div>
                </div>
                <div class="form-group">
                  <label for="user_email" class="col-sm-2 control-label">Email</label>
                  <div class="col-sm-10">
                    <?php
                    //Display activation link, if account inactive
                    if ($userdetails->Active() == '1'){
                      echo '<input type="text" id="user_email" value="Yes" class="form-control" disabled>';
                    }
                    else{
                      echo '<input type="text" id="user_email" value="No" class="form-control" disabled>';          
                    }
                    ?>
                  </div>
                </div>
                <?php if ($userdetails->Active() != '1'){ ?>
                <div class="form-group"> 
                  <label for="user_activate" class="col-sm-2 control-label">Activate</label>
                  <div class="col-sm-10">               
                    <div class="checkbox">
                      <input type='checkbox' name='activate' id='activate' value='activate'>
                    </div>
                  </div>
                </div>  
                <?php } ?>
                <div class="form-group">
                  <label for="user_title" class="col-sm-2 control-label">Title</label>
                  <div class="col-sm-10">
                    <input type="text" id="user_title" name='title' value='<?php echo $userdetails->Title() ?>' class="form-control">
                  </div>
                </div>
                <div class="form-group">
                  <label for="user_signup_date" class="col-sm-2 control-label">Sign Up</label>
                  <div class="col-sm-10">
                    <?php echo '<input type="text" id="user_signup_date" value="' .date("j M, Y", $userdetails->SignupTimeStamp()). '" class="form-control" disabled>'; ?>
                  </div>
                </div>
                <div class="form-group">
                  <label for="user_last_signin_date" class="col-sm-2 control-label">Last Sign In</label>
                  <div class="col-sm-10">
                    <?php 
                      //Last sign in, interpretation
                      if ($userdetails->LastSignIn() == '0'){
                        echo '<input type="text" id="user_last_signin_date" value="Never" class="form-control" disabled>';
                      }
                      else {
                        echo '<input type="text" id="user_last_signin_date" value="' .date("j M, Y", $userdetails->LastSignIn()). '" class="form-control" disabled>';
                      }
                    ?>
                  </div>
                </div> 
                <div class="form-group"> 
                  <label for="user_delete" class="col-sm-2 control-label">Delete</label>
                  <div class="col-sm-10">               
                    <div class="checkbox">
                      <input type='checkbox' name='delete["<?php echo $userdetails->Id() ?>"]' id='delete["<?php echo $userdetails->Id() ?>"]' value='<?php echo $userdetails->Id() ?>'>
                    </div>
                  </div>
                </div>
                <div class="form-group"> 
                  <label for="remove_permission" class="col-sm-2 control-label">Remove Permission</label>
                  <div class="col-sm-10">               
                    <div class="checkbox">
                      <?php
                      //List of permission levels user is apart of
                      foreach ($permissionData as $v1) {
                      	if (UCPermission::IsPermissionSet($v1, $userPermission)) {
                          echo "<br><input type='checkbox' name='removePermission[".$v1->Id()."]' id='removePermission[".$v1->Id()."]' value='".$v1->Id()."'> ".$v1->Name();
                        }
                      }
                      ?>
                    </div>
                  </div>
                </div> 
                <div class="form-group"> 
                  <label for="add_permission" class="col-sm-2 control-label">Add Permission</label>
                  <div class="col-sm-10">               
                    <div class="checkbox">
                      <?php
                      //List of permission levels user is not apart of
                      foreach ($permissionData as $v1) {
                      	if (!UCPermission::IsPermissionSet($v1, $userPermission)) {
                      	  echo "<br><input type='checkbox' name='addPermission[".$v1->Id()."]' id='addPermission[".$v1->Id()."]' value='".$v1->Id()."'> ".$v1->Name();
                        }
                      }
                      ?>
                    </div>
                  </div>
                </div>          
              </div>
              <!-- /.box-body -->
               <div class="box-footer">
                 <button type="submit" class="btn btn-info pull-right">Update</button>
               </div>
               <!-- /.box-footer -->   
            </form>
          </div>  
        </div>
      </div>
      
      <!-- Breadcrumb -->
     <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
      </ol>-->
    </section>

    <!-- Main content -->
    <section class="content">

      <!-- Your Page Content Here -->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <?php  include(__DIR__."/footer.php"); ?> 
  <?php  include(__DIR__."/right-nav.php"); ?> 
  
</div>
<!-- ./wrapper -->

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.2.3 -->
<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<!-- Bootstrap 3.3.6 -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/app.min.js"></script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
     Both of these plugins are recommended to enhance the
     user experience. Slimscroll is required when using the
     fixed layout. -->
</body>
</html>
