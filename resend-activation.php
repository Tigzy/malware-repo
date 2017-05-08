<?php 
  require_once(__DIR__."/src/config.php");
  require_once(__DIR__."/src/lib/usercake/init.php");
  if (!UCUser::CanUserAccessUrl($_SERVER['PHP_SELF'])) { die();}
  $user = UCUser::getCurrentUser();
  $errors = array();
  $successes = array();
  
  //Prevent the user visiting the logged in page if he/she is already logged in
  if($user) { header("Location: account.php"); die(); }
  
  //Forms posted
  if(!empty($_POST) && $user_settings->EmailActivation())
  {
    $email = $_POST["email"];
    $username = $_POST["username"];
    
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
      $errors[] =  lang("ACCOUNT_SPECIFY_USERNAME");
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
        $errors[] = lang("ACCOUNT_USER_OR_EMAIL_INVALID");
      }
      else
      {
        $userdetails = UCUser::GetByUserName($username);
        $userdetails = new UCUser($userdetails[0], True);
        
        //See if the user's account is activation
        if($userdetails->Active() == 1)
        {
          $errors[] = lang("ACCOUNT_ALREADY_ACTIVE");
        }
        else
        {
          if ($user_settings->ResendActivationThreshold() == 0) {
            $hours_diff = 0;
          }
          else {
            $last_request = $userdetails->LastActivationRequest();
            $hours_diff = round((time() - $last_request) / (3600 * $user_settings->ResendActivationThreshold()),0);
          }
          
          if($resend_activation_threshold !=0 && $hours_diff <= $user_settings->ResendActivationThreshold())
          {
          	$errors[] = lang("ACCOUNT_LINK_ALREADY_SENT",array($user_settings->ResendActivationThreshold()));
          }
          else
          {
            //For security create a new activation url;
            $new_activation_token = $user_db->GenerateActivationToken();
            
            if(!UCUser::UpdateLastActivationRequest($new_activation_token,$username,$email))
            {
              $errors[] = lang("SQL_ERROR");
            }
            else
            {
              $activation_url = $user_settings->WebsiteUrl()."activate-account.php?token=".$new_activation_token;
              $hooks = array( array("#ACTIVATION-URL","#USERNAME#"), array($activation_url,$userdetails->DisplayName()) );
              $mail = new UCMail("resend-activation.txt",$hooks);
              
              if(!$mail->IsValid())
              {
                $errors[] = lang("MAIL_TEMPLATE_BUILD_ERROR");
              }
              else
              {
              	if(!$mail->send($userdetails->Email(),"Activate your ".$user_settings->WebsiteName()." Account"))
                {
                  $errors[] = lang("MAIL_ERROR");
                }
                else
                {
                  //Success, user details have been updated in the db now mail this information out.
                  $successes[] = lang("ACCOUNT_NEW_ACTIVATION_SENT");
                }
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
  <title><?php echo $websiteName ?></title>
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
    
    <?php
    //Show disabled if email activation not required
    if(!$user_settings->EmailActivation()) { 
        echo lang("FEATURE_DISABLED");
    } else { ?>  

      <form name='resendActivation' action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
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
          </div>
          <!-- /.col -->
          <div class="col-xs-4">
            <button type="submit" class="btn btn-primary btn-block btn-flat">Submit</button>
          </div>
          <!-- /.col -->
        </div>
      </form>
      
    <?php } ?>

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
