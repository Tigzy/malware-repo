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
  
  //Forms posted
  if(!empty($_POST))
  {
    $cfgId = array();
    $newSettings = $_POST['settings'];
    $errors = array();
    $successes = array();
    
    //Validate new site name
    if ($newSettings[$user_settings->WebsiteNameId()] != $user_settings->WebsiteName()) {
      $newWebsiteName = $newSettings[$user_settings->WebsiteNameId()];
      if(minMaxRange(1,150,$newWebsiteName))
      {
        $errors[] = lang("CONFIG_NAME_CHAR_LIMIT",array(1,150));
      }
      else if (count($errors) == 0) {
      	$cfgId[] = $user_settings->WebsiteNameId();
      	$cfgValue[$user_settings->WebsiteNameId()] = $newWebsiteName;
        $user_settings->SetWebsiteName( $newWebsiteName );
      }
    }
    
    //Validate new short site name
    if ($newSettings[$user_settings->WebsiteShortNameId()] != $user_settings->WebsiteShortName()) {
    	$newWebsiteShortName = $newSettings[$user_settings->WebsiteShortNameId()];
    	if(minMaxRange(1,150,$newWebsiteShortName))
    	{
    		$errors[] = lang("CONFIG_NAME_CHAR_LIMIT",array(1,150));
    	}
    	else if (count($errors) == 0) {
    		$cfgId[] = $user_settings->WebsiteShortNameId();
    		$cfgValue[$user_settings->WebsiteShortNameId()] = $newWebsiteShortName;
    		$user_settings->SetWebsiteShortName($newWebsiteShortName);
    	}
    }
    
    //Validate new site email address
    if ($newSettings[$user_settings->EmailAddressId()] != $user_settings->EmailAddress()) {
      $newEmail = $newSettings[$user_settings->EmailAddressId()];
      if(minMaxRange(1,150,$newEmail))
      {
        $errors[] = lang("CONFIG_EMAIL_CHAR_LIMIT",array(1,150));
      }
      elseif(!UCMail::isAddressValid($newEmail))
      {
        $errors[] = lang("CONFIG_EMAIL_INVALID");
      }
      else if (count($errors) == 0) {
      	$cfgId[] = $user_settings->EmailAddressId();
      	$cfgValue[$user_settings->EmailAddressId()] = $newEmail;
        $user_settings->SetEmailAddress($newEmail);
      }
    }
    
    //Validate email activation selection
    if ($newSettings[$user_settings->EmailActivationId()] != $user_settings->EmailActivation()) {
      $newActivation = $newSettings[$user_settings->EmailActivationId()];
      if($newActivation != "true" AND $newActivation != "false")
      {
        $errors[] = lang("CONFIG_ACTIVATION_TRUE_FALSE");
      }
      else if (count($errors) == 0) {
      	$cfgId[] = $user_settings->EmailActivationId();
      	$cfgValue[$user_settings->EmailActivationId()] = $newActivation;
        $user_settings->SetEmailActivation($newActivation);
      }
    }
    
    //Validate new email activation resend threshold
    if ($newSettings[$user_settings->ResendActivationThresholdId()] != $user_settings->ResendActivationThreshold()) {
      $newResend_activation_threshold = $newSettings[$user_settings->ResendActivationThresholdId()];
      if($newResend_activation_threshold > 72 OR $newResend_activation_threshold < 0)
      {
        $errors[] = lang("CONFIG_ACTIVATION_RESEND_RANGE",array(0,72));
      }
      else if (count($errors) == 0) {
      	$cfgId[] = $user_settings->ResendActivationThresholdId();
      	$cfgValue[$user_settings->ResendActivationThresholdId()] = $newResend_activation_threshold;
        $user_settings->SetResendActivationThreshold($newResend_activation_threshold);
      }
    }
    
    //Validate new language selection
    if ($newSettings[$user_settings->LanguageId()] != $user_settings->Language()) {
      $newLanguage = $newSettings[$user_settings->LanguageId()];
      if(minMaxRange(1,150,$language))
      {
        $errors[] = lang("CONFIG_LANGUAGE_CHAR_LIMIT",array(1,150));
      }
      elseif (!file_exists($newLanguage)) {
        $errors[] = lang("CONFIG_LANGUAGE_INVALID",array($newLanguage));				
      }
      else if (count($errors) == 0) {
      	$cfgId[] = $user_settings->LanguageId();
      	$cfgValue[$user_settings->LanguageId()] = $newLanguage;
        $user_settings->SetLanguage($newLanguage);
      }
    }
    
    //Update configuration table with new settings
    if (count($errors) == 0 AND count($cfgId) > 0) {
      $user_db->SettingSet($cfgId, $cfgValue);
      $successes[] = lang("CONFIG_UPDATE_SUCCESSFUL");
    }
  }
  
  $languages = $user_settings->LanguageFiles(); //Retrieve list of language files
  //$templates = getTemplateFiles(); //Retrieve list of template files
  $permissionData = UCPermission::GetPermissions(); //Retrieve list of all permission levels
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
            <h3 class="box-title">Admin Configuration</h3>
          </div>
          <!-- /.box-header -->
          <!-- form start -->          
          <form name='adminConfiguration' action="<?php echo $_SERVER['PHP_SELF'] ?>"" method="post" class="form-horizontal">
            <div class="box-body">  
              <div class="form-group">
                <label for="website_name" class="col-sm-2 control-label">Website Name</label>
                <div class="col-sm-10">
                  <input type="text" id="website_name" name='settings[<?php echo $user_settings->WebsiteNameId() ?>]' value='<?php echo $user_settings->WebsiteName() ?>' class="form-control" placeholder="Website Name">
                </div>
              </div>
              <div class="form-group">
                <label for="website_short_name" class="col-sm-2 control-label">Website Short Name</label>
                <div class="col-sm-10">
                  <input type="text" id="website_short_name" name='settings[<?php echo $user_settings->WebsiteShortNameId() ?>]' value='<?php echo $user_settings->WebsiteShortName() ?>' class="form-control" placeholder="Website Short Name">
                </div>
              </div>  
              <div class="form-group">
                <label for="email" class="col-sm-2 control-label">Email</label>
                <div class="col-sm-10">
                  <input type="text" id="email" name='settings[<?php echo $user_settings->EmailAddressId() ?>]' value='<?php echo $user_settings->EmailAddress() ?>' class="form-control" placeholder="Email">
                </div>
              </div>
              <div class="form-group">
                <label for="activation_threshold" class="col-sm-2 control-label">Activation Threshold</label>
                <div class="col-sm-10">
                  <input type="text" id="activation_threshold" name='settings[<?php echo $user_settings->ResendActivationThresholdId() ?>]' value='<?php echo $user_settings->ResendActivationThreshold() ?>' class="form-control" placeholder="Activation Threshold">
                </div>
              </div>
              <div class="form-group">
                <label for="language" class="col-sm-2 control-label">Language</label>
                <div class="col-sm-10">
                  <select type="text" id="language" name='settings[<?php echo $user_settings->LanguageId() ?>]' class="form-control">
                    <?php
                    //Display language options
                    foreach ($languages as $optLang) {
                      if ($optLang == $language) {
                        echo "<option value='".$optLang."' selected>$optLang</option>";
                      }
                      else {
                        echo "<option value='".$optLang."'>$optLang</option>";
                      }
                    }
                    ?>        
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="activation" class="col-sm-2 control-label">Email Activation</label>
                <div class="col-sm-10">
                  <select type="text" id="activation" name='settings[<?php echo $user_settings->EmailActivationId() ?>]' class="form-control">
                    <?php
                    //Display email activation options
                    if ($user_settings->EmailActivation() == "true"){ ?>
                        <option value='true' selected>True</option>
                        <option value='false'>False</option>
                      
                    <?php } else { ?>
                        <option value='true'>True</option>
                        <option value='false' selected>False</option>
                    <?php } ?>  
                  </select>
                </div>
              </div>           
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
              <button type="submit" class="btn btn-info pull-right">Submit</button>
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
