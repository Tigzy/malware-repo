<?php 
  require_once(__DIR__."/src/config.php");
  require_once(__DIR__."/src/lib/usercake/init.php");
  if (!UCUser::CanUserAccessUrl($_SERVER['PHP_SELF'])) { die();}
  
  $errors = array();
  $successes = array();
  
  global $user_db, $user_settings;
  
  //User has confirmed they want their password changed 
  if(!empty($_GET["confirm"]))
  {
    $token = trim($_GET["confirm"]);
    
    if($token == "" || !$user_db->ValidateActivationToken($token, True))
    {
      $errors[] = lang("FORGOTPASS_INVALID_TOKEN");
    }
    else
    {
      $rand_pass 	= getUniqueCode(15); //Get unique code
      $secure_pass 	= generateHash($rand_pass); //Generate random hash
      $userdetails 	= UCUser::GetByAPIKey($token);
      $userdetails 	= new UCUser($userdetails);      
      $hooks 		= array( array("#GENERATED-PASS#","#USERNAME#"), array($rand_pass,$userdetails->DisplayName()) );
      $mail 		= new UCMail("your-lost-password.txt",$hooks);		
      
      if(!$mail->IsValid())
      {
        $errors[] = lang("MAIL_TEMPLATE_BUILD_ERROR");
      }
      else
      {	
        if(!$mail->send($userdetails->Email(),"Your new password"))
        {
          $errors[] = lang("MAIL_ERROR");
        }
        else
        {
          if(!$userdetails->UpdatePasswordFromToken($secure_pass,$token))
          {
            $errors[] = lang("SQL_ERROR");
          }
          else
          {	
          	if(!$userdetails->ToggleLostPasswordRequest(0))
            {
              $errors[] = lang("SQL_ERROR");
            }
            else {
              $successes[]  = lang("FORGOTPASS_NEW_PASS_EMAIL");
            }
          }
        }
      }
    }
  }
  
  //User has denied this request
  if(!empty($_GET["deny"]))
  {
    $token = trim($_GET["deny"]);
    
    if($token == "" || !$user_db->ValidateActivationToken($token, True))
    {
      $errors[] = lang("FORGOTPASS_INVALID_TOKEN");
    }
    else
    {      
      $userdetails = UCUser::GetByAPIKey($token);
      $userdetails = new UCUser($userdetails);
      
      if(!$userdetails->ToggleLostPasswordRequest(0))
      {
        $errors[] = lang("SQL_ERROR");
      }
      else {
        $successes[] = lang("FORGOTPASS_REQUEST_CANNED");
      }
    }
  }
  
  //Forms posted
  if(!empty($_POST))
  {
  	$email 		= sanitize($_POST["email"]);
    $username 	= sanitize($_POST["username"]);
    
    //Perform some validation
    //Feel free to edit / change as required
    
    if(trim($email) == "")
    {
      $errors[] = lang("ACCOUNT_SPECIFY_EMAIL");
    }
    //Check to ensure email is in the correct format / in the db
    else if(!UCMail::isAddressValid($email) || !$user_db->UserEmailInUse($email))
    {
      $errors[] = lang("ACCOUNT_INVALID_EMAIL");
    }
    
    if(trim($username) == "")
    {
      $errors[] = lang("ACCOUNT_SPECIFY_USERNAME");
    }
    else if(!$user_db->UserUserNameExists($username))
    {
      $errors[] = lang("ACCOUNT_INVALID_USERNAME");
    }
    
    if(count($errors) == 0)
    {      
      //Check that the username / email are associated to the same account
      if(!$user_db->UserNameLinkedToEmail($email,$username))
      {
        $errors[] =  lang("ACCOUNT_USER_OR_EMAIL_INVALID");
      }
      else
      {
        //Check if the user has any outstanding lost password requests
        $userdetails = UCUser::GetByUserName($username);
        $userdetails = new UCUser($userdetails[0]);
        
        if($userdetails->LostPasswordRequest() == 1)
        {
          $errors[] = lang("FORGOTPASS_REQUEST_EXISTS");
        }
        else
        {
          //Email the user asking to confirm this change password request
          //We can use the template builder here          
          //We use the activation token again for the url key it gets regenerated everytime it's used.          
          
          $confirm_url 	= lang("CONFIRM")."\n".$user_settings->WebsiteUrl()."forgot-password.php?confirm=".$userdetails->Activationtoken();
          $deny_url 	= lang("DENY")."\n".$user_settings->WebsiteUrl()."forgot-password.php?deny=".$userdetails->Activationtoken();  
          $hooks 		= array( array("#CONFIRM-URL#","#DENY-URL#","#USERNAME#"), array($confirm_url,$deny_url,$userdetails->UserName()) );
          $mail 		= new UCMail("lost-password-request.txt",$hooks);
          
          if(!$mail->IsValid())
          {
            $errors[] = lang("MAIL_TEMPLATE_BUILD_ERROR");
          }
          else
          {
            if(!$mail->send($userdetails->Email(),"Lost password request"))
            {
              $errors[] = lang("MAIL_ERROR");
            }
            else
            {
              //Update the DB to show this account has an outstanding request
              if(!$userdetails->ToggleLostPasswordRequest(1)) {
                $errors[] = lang("SQL_ERROR");
              }
              else {                
                $successes[] = lang("FORGOTPASS_REQUEST_SUCCESS");
              }
            }
          }
        }
      }
    }
  }
?> 

<!DOCTYPE html>
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
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/iCheck/square/blue.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="index.php"><?php echo $user_settings->WebsiteName() ?></a>
  </div>
  <!-- /.login-logo -->
  <div class="login-box-body">
    <p class="login-box-msg">Password recevery</p> 
    
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

    <form name='newLostPass' action="<?php echo $_SERVER['PHP_SELF'] ?>"" method="post">
      <div class="form-group has-feedback">
        <input type="text" id="username" name="username" class="form-control" placeholder="Username">
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="email" id="email" name="email" class="form-control" placeholder="Email">
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      <div class="row">
        <div class="col-xs-8">
          <!--<div class="checkbox icheck">
            <label>
              <input type="checkbox"> Remember Me
            </label>
          </div>-->
        </div>
        <!-- /.col -->
        <div class="col-xs-4">
          <button type="submit" class="btn btn-primary btn-block btn-flat">Submit</button>
        </div>
        <!-- /.col -->
      </div>
    </form>

    <!--<div class="social-auth-links text-center">
      <p>- OR -</p>
      <a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i> Sign in using
        Facebook</a>
      <a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i> Sign in using
        Google+</a>
    </div>-->
    <!-- /.social-auth-links -->

    <!--<a href="forgot-password.php">I forgot my password</a><br>-->
    <!--<a href="register.php" class="text-center">Register a new membership</a>-->

  </div>
  <!-- /.login-box-body -->
</div>
<!-- /.login-box -->

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
