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
  
  //Prevent the user visiting the logged in page if he is not logged in
  if(!$user) { header("Location: login.php"); die(); }
  
  $avatar = "";
  $errors = array();
  $successes = array();
  
  //Encode avatar
  if(isset($_FILES['userfile']) && !empty($_FILES['userfile']) && !empty($_FILES['userfile']['name']))
  {
      $allowed_ext  = array('jpg','jpeg','png','gif');
      $file_name    = $_FILES['userfile']['name'];
      $filename_exploded = explode('.',$file_name);
      $file_ext = strtolower(end($filename_exploded));
      
      $file_size    = $_FILES['userfile']['size'];
      $file_tmp     = $_FILES['userfile']['tmp_name'];
  
      $type         = pathinfo($file_tmp, PATHINFO_EXTENSION);
      $data         = file_get_contents($file_tmp);      
  
      if( in_array($file_ext, $allowed_ext) === false )
      {
        $errors[]= 'Extension not allowed';
      }  
      if( $file_size > 2097152 )
      {
        $errors[]= 'File size must be under 2mb';  
      }
      if( empty($errors) )
      {
        $avatar = base64_encode($data);
      } 
  }
  
  // Submit data
  if(!empty($_POST))
  {    
    $password = $_POST["password"];
    $password_new = $_POST["passwordc"];
    $password_confirm = $_POST["passwordcheck"];	
    
    $errors = array();
    $email = $_POST["email"];
    
    //Confirm the hashes match before updating a users password
    $entered_pass = generateHash($password,$user->Password());
    
    // Verify password
    if (trim($password) == "")
    {
      $errors[] = lang("ACCOUNT_SPECIFY_PASSWORD");
    }
    else if($entered_pass != $user->Password())
    {
      //No match
      $errors[] = lang("ACCOUNT_PASSWORD_INVALID");
    }	
    
    // Update email
    if($email != $user->Email())
    {
      if(trim($email) == "")
      {
        $errors[] = lang("ACCOUNT_SPECIFY_EMAIL");
      }
      else if(!isValidEmail($email))
      {
        $errors[] = lang("ACCOUNT_INVALID_EMAIL");
      }
      else if(emailExists($email))
      {
        $errors[] = lang("ACCOUNT_EMAIL_IN_USE", array($email));	
      }	
      
      //End data validation
      if(count($errors) == 0)
      {
      	$user->UpdateEmail($email);
        $successes[] = lang("ACCOUNT_EMAIL_UPDATED");
      }
    }
    
    // Update password
    if ($password_new != "" OR $password_confirm != "")
    {
      if(trim($password_new) == "")
      {
        $errors[] = lang("ACCOUNT_SPECIFY_NEW_PASSWORD");
      }
      else if(trim($password_confirm) == "")
      {
        $errors[] = lang("ACCOUNT_SPECIFY_CONFIRM_PASSWORD");
      }
      else if(minMaxRange(8,50,$password_new))
      {	
        $errors[] = lang("ACCOUNT_NEW_PASSWORD_LENGTH",array(8,50));
      }
      else if($password_new != $password_confirm)
      {
        $errors[] = lang("ACCOUNT_PASS_MISMATCH");
      }
      
      //End data validation
      if(count($errors) == 0)
      {
        //Also prevent updating if someone attempts to update with the same password
        $entered_pass_new = generateHash($password_new, $user->Password());
        
        if($entered_pass_new == $user->Password())
        {
          //Don't update, this fool is trying to update with the same password Â¬Â¬
          $errors[] = lang("ACCOUNT_PASSWORD_NOTHING_TO_UPDATE");
        }
        else
        {
          //This function will create the new hash and update the hash_pw property.
          $user->UpdatePassword($password_new);
          $successes[] = lang("ACCOUNT_PASSWORD_UPDATED");
        }
      }
    }
    
    // Update avatar
    if($avatar != "")
    {
      if (!base64_decode($avatar))
      {
        $errors[] = lang("ACCOUNT_INVALID_AVATAR");
      }
      
      //End data validation
      if(count($errors) == 0)
      {
      	$user->UpdateAvatar($avatar);
        $successes[] = lang("ACCOUNT_AVATAR_UPDATED");
      }
    }
    
    if(count($errors) == 0 AND count($successes) == 0){
      $errors[] = lang("NOTHING_TO_UPDATE");
    }
  }
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
        <!-- Horizontal Form -->
        <div class="box box-info">
          <div class="box-header with-border">
            <h3 class="box-title">User Configuration</h3>
          </div>   
          
          <!-- /.box-header -->
          <!-- form start -->          
          <form name='updateAccount' enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" class="form-horizontal">
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
                <label for="password" class="col-sm-2 control-label">Password</label>
                <div class="col-sm-10">
                  <input type="password" id="password" name="password" class="form-control" placeholder="Password">
                </div>
              </div>              
              <div class="form-group">
                <label for="email" class="col-sm-2 control-label">Email</label>
                <div class="col-sm-10">
                  <input type="text" id="email" name="email" value='<?php echo $user->Email() ?>' class="form-control" placeholder="Email">
                </div>
              </div>              
              <div class="form-group">
                <label for="passwordc" class="col-sm-2 control-label">New Password</label>
                <div class="col-sm-10">
                  <input type="password" id="passwordc" name="passwordc" class="form-control" placeholder="New">
                </div>
              </div>              
              <div class="form-group">
                <label for="passwordcheck" class="col-sm-2 control-label">Confirm Password</label>
                <div class="col-sm-10">
                  <input type="password" id="passwordcheck" name="passwordcheck" class="form-control" placeholder="Confirm">
                </div>
              </div>              
              <div class="form-group">
                <label for="avatar" class="col-sm-2 control-label">Avatar</label>
                <div class="col-sm-2">
                  <img  height="50" width="50" src="data:image/png;base64,<?php echo $user->Avatar() ?>" class="img-circle" alt="User Image">
                </div>
                <div class="col-sm-8">
                  <input name="userfile" type="file" />
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
