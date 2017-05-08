<?php 
  require_once(__DIR__."/src/config.php");
  require_once(__DIR__."/src/lib/usercake/init.php");
  if (!UCUser::CanUserAccessUrl($_SERVER['PHP_SELF'])) { die();}
  
  //Prevent the user visiting the logged in page if he/she is already logged in
  if(UCUser::IsUserLoggedIn()) { header("Location: account.php"); die(); }
  
  $errors = array();
  $successes = array();
  
  //Forms posted
  if(!empty($_POST))
  {    
    $email 				= trim($_POST["email"]);
    $username 			= trim($_POST["username"]);
    $displayname 		= trim($_POST["displayname"]);
    $password 			= trim($_POST["password"]);
    $confirm_pass 		= trim($_POST["passwordc"]);
    $captcha 			= md5($_POST["captcha"]);    
    
    if ($captcha != $_SESSION['captcha'])
    {
      $errors[] = lang("CAPTCHA_FAIL");
    }
    if(minMaxRange(5,25,$username))
    {
      $errors[] = lang("ACCOUNT_USER_CHAR_LIMIT",array(5,25));
    }
    if(!ctype_alnum($username)){
      $errors[] = lang("ACCOUNT_USER_INVALID_CHARACTERS");
    }
    if(minMaxRange(5,25,$displayname))
    {
      $errors[] = lang("ACCOUNT_DISPLAY_CHAR_LIMIT",array(5,25));
    }
    if(!ctype_alnum($displayname)){
      $errors[] = lang("ACCOUNT_DISPLAY_INVALID_CHARACTERS");
    }
    if(minMaxRange(8,50,$password) && minMaxRange(8,50,$confirm_pass))
    {
      $errors[] = lang("ACCOUNT_PASS_CHAR_LIMIT",array(8,50));
    }
    else if($password != $confirm_pass)
    {
      $errors[] = lang("ACCOUNT_PASS_MISMATCH");
    }
    if(!UCMail::isAddressValid($email))
    {
      $errors[] = lang("ACCOUNT_INVALID_EMAIL");
    }
    //End data validation
    if(count($errors) == 0)
    {	
      //Construct a user object
      $user = UCUser::NewUser($username,$displayname,$password,$email);
      
      //Checking this flag tells us whether there were any errors such as possible data duplication occured
      if($user->has_errors)
      {
        if($user->username_taken) 		$errors[] = lang("ACCOUNT_USERNAME_IN_USE",array($username));
        if($user->displayname_taken) 	$errors[] = lang("ACCOUNT_DISPLAYNAME_IN_USE",array($displayname));
        if($user->email_taken) 	  		$errors[] = lang("ACCOUNT_EMAIL_IN_USE",array($email));		
      }
      else
      {
        //Attempt to add the user to the database, carry out finishing tasks like emailing the user (if required)
        if(!$user->Add())
        {
          $errors[] = lang("SQL_ERROR");
        }
      }
    }
    if(count($errors) == 0) 
    {
      if ($user->registration_type = 1) {
      	$successes[]= lang("ACCOUNT_REGISTRATION_COMPLETE_TYPE1");
      } 
      else if ($user->registration_type = 2) {
      	$successes[]= lang("ACCOUNT_REGISTRATION_COMPLETE_TYPE2");
      }
    }
  }
?> 

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php $user_settings->WebsiteName() ?></title>
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
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/iCheck/square/blue.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="hold-transition register-page">
<div class="register-box">
  <div class="register-logo">
    <a href="index.php"><?php $user_settings->WebsiteName() ?></a>
  </div>

  <div class="register-box-body">
    <p class="login-box-msg">Register a new membership</p>

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

    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
      <div class="form-group has-feedback">
        <input type="text" id="username" name="username" class="form-control" placeholder="User name">
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="text" id="displayname" name="displayname" class="form-control" placeholder="Display name">
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="email" id="email" name="email" class="form-control" placeholder="Email">
        <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="password" id="password" name="password" class="form-control" placeholder="Password">
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="password" id="passwordc" name="passwordc" class="form-control" placeholder="Retype password">
        <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <img src='src/lib/usercake/captcha.php'>
      </div>
      <div class="form-group has-feedback">
        <input type="text" id="captcha" name="captcha" class="form-control" placeholder="Security code">
        <span class="glyphicon glyphicon-ok form-control-feedback"></span>
      </div>
      <div class="row">
        <div class="col-xs-8">
          <!--<div class="checkbox icheck">
            <label>
              <input type="checkbox"> I agree to the <a href="#">terms</a>
            </label>
          </div>-->
        </div>
        <!-- /.col -->
        <div class="col-xs-4">
          <button type="submit" class="btn btn-primary btn-block btn-flat">Register</button>
        </div>
        <!-- /.col -->
      </div>
    </form>

    <!--<div class="social-auth-links text-center">
      <p>- OR -</p>
      <a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i> Sign up using
        Facebook</a>
      <a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i> Sign up using
        Google+</a>
    </div>-->

    <a href="login.php" class="text-center">I already have a membership</a>
  </div>
  <!-- /.form-box -->
</div>
<!-- /.register-box -->

<!-- jQuery 2.2.3 -->
<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<!-- Bootstrap 3.3.6 -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>
<!-- iCheck -->
<script src="plugins/iCheck/icheck.min.js"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' // optional
    });
  });
</script>
</body>
</html>
