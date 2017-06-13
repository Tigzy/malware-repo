<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->

<?php 
  require_once(__DIR__."/src/config.php");
  require_once(__DIR__."/src/lib/usercake/init.php");
  if (!UCUser::CanUserAccessUrl($_SERVER['PHP_SELF'])){die();}
  $user = UCUser::getCurrentUser();
?> 

<?php 
  $sample = "";
  if (isset($_GET["hash"])) {
  	$sample = $_GET["hash"];
  }
  
  if (empty($sample)) {
  	header("HTTP/1.0 404 Not Found");
  	exit;
  }
?>

<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo "Sample : " . $sample ?></title>
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
  <!-- Pace style -->
  <link rel="stylesheet" href="plugins/pace/pace.min.css">
  <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
        page. However, you can choose any other skin. Make sure you
        apply the skin class to the body tag so the changes take effect.
  -->
  <link rel="stylesheet" href="dist/css/skins/skin-blue.min.css">	
  <!-- Generic page styles -->
  <link rel="stylesheet" href="plugins/jQueryUpload/css/style.css">
  <!-- tags -->
  <link rel="stylesheet" type="text/css" href="plugins/jQueryUpload/css/tagmanager.css" />
  
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
			<h1>
				Sample <small>Detailed View</small>
			</h1>
			<ol class="breadcrumb">
				<li><a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]; ?>index.php"><i class="fa fa-home"></i> Home</a></li>
				<li class="active"><?php echo $sample ?></li>
			</ol>
		</section>
	
		<!-- Main content -->
		<section class="content">
		    <!--<div class="nav-tabs-custom">-->
			<ul class="nav nav-tabs">
			  <li class="active"><a data-toggle="tab" href="#info-tab">Info</a></li>
			  <?php if (IsModuleEnabled("pedata")) {	 ?>
			  <li id="pedata-selector" style="display: none;"><a data-toggle="tab" href="#pedata-tab">PE Data</a></li>		
			  <?php } ?>
			  <?php if (IsModuleEnabled("officedata")) {	 ?>	  
			  <li id="officedata-selector" style="display: none;"><a data-toggle="tab" href="#officedata-tab">Office Data</a></li>
			  <?php } ?>
			  <?php if (IsModuleEnabled("pdfdata")) {	 ?>
			  <li id="pdfdata-selector" style="display: none;"><a data-toggle="tab" href="#pdfdata-tab">PDF Data</a></li>
			  <?php } ?>
			  <li><a data-toggle="tab" href="#hexdata-tab">Hex</a></li>
			</ul>
			
			<div class="tab-content" style="margin-top: 10px;">			
			  <!-- Info View -->
			  <div id='alert'></div>
			  <div id="info-tab" class="tab-pane fade in active">
			    <div class="row">
			    
			    	<!-- Left panel -->
					<section class="col-lg-6 connectedSortable">	

				          <div class="box box-primary">
				            <div class="box-header with-border">
				              <h3 class="box-title">General Information</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
				                </button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				                <div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-11">
										<div class="input-group">
											<span class="input-group-addon" id="uploader-descr"><span class="glyphicon glyphicon-user"></span> Uploader</span>
											<select id="sample-uploader" aria-describedby="uploader-descr" class="selectpicker form-control" data-live-search="true"></select>
										</div>
									</div>
									<div class="control-group col col-lg-1" style="padding-top: 5px;">
										<img id="sample-uploader-img" alt="" height="24px" width="24px" class="img-circle" src="">
									</div>
								</div>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="vendor-descr"><span class="glyphicon glyphicon-glass"></span> Threat Name</span>
											<input type="text" id="sample-vendor" class="form-control" aria-describedby="vendor-descr">
										</div>
									</div>
								</div>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-11">
										<div class="input-group">
											<span class="input-group-addon" id="name-descr"><span class="glyphicon glyphicon-file"></span> Name</span>
											<input type="text" id="sample-name" class="form-control" aria-describedby="name-descr" readonly>
										</div>
									</div>
									<div class="control-group col col-lg-1" style="padding-top: 5px;">
										<img id="sample-img" alt="" height="24px" width="24px" class="img" src="">
									</div>
								</div>
								<?php if (IsModuleEnabled("mime")) {	 ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="mime-descr"><span class="glyphicon glyphicon-question-sign"></span> MIME Type</span>
											<input type="text" id="sample-mime" class="form-control" aria-describedby="mime-descr" readonly>
										</div>
									</div>
								</div>
								<?php } ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="md5-descr"><span class="glyphicon glyphicon-map-marker"></span> MD5</span>
											<input type="text" id="sample-md5" class="form-control" aria-describedby="md5-descr" readonly>
										</div>
									</div>
								</div>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="sha256-descr"><span class="glyphicon glyphicon-map-marker"></span> SHA256</span>
											<input type="text" id="sample-sha256" class="form-control" aria-describedby="sha256-descr" readonly>
										</div>
									</div>
								</div>
								<?php if (IsModuleEnabled("ssdeep")) {	 ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="ssdeep-descr"><span class="glyphicon glyphicon-map-marker"></span> SSDEEP</span>
											<input type="text" id="sample-ssdeep" class="form-control" aria-describedby="ssdeep-descr" readonly>
										</div>
									</div>
								</div>
								<?php } ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="date-descr"><span class="glyphicon glyphicon-time"></span> Upload Date</span>
											<input type="text" id="sample-date" class="form-control" aria-describedby="date-descr" readonly>
										</div>
									</div>
								</div>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="size-descr"><span class="glyphicon glyphicon-signal"></span> Size</span>
											<input type="text" id="sample-size" class="form-control" aria-describedby="size-descr" readonly>
										</div>
									</div>
								</div>
				            </div>
				          </div>	
				          		
					</section>
					
					<!-- Right panel -->
					<section class="col-lg-6 connectedSortable">
					
						<div class="box box-primary">
				            <div class="box-header with-border">
				              <h3 class="box-title">Actions</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <!-- .bg-red, .bg-yellow, .bg-aqua, .bg-blue, .bg-light-blue, .bg-green, .bg-navy, .bg-teal, .bg-olive, .bg-lime, .bg-orange, .bg-fuchsia, .bg-purple, .bg-maroon, .bg-black, .bg-red-active, .bg-yellow-active, .bg-aqua-active, .bg-blue-active, .bg-light-blue-active, .bg-green-active, .bg-navy-active, .bg-teal-active, .bg-olive-active, .bg-lime-active, .bg-orange-active, .bg-fuchsia-active, .bg-purple-active, .bg-maroon-active, .bg-black-active -->
				            <div class="box-body">
				            	<a class="btn btn-app bg-yellow" OnClick="toggle_lock('<?php echo $sample ?>')">
					              <i id="sample-lock" class="fa fa-unlock"></i><span id="lock-button"> Lock</span>
					            </a>
				                <a class="btn btn-app bg-purple" OnClick="sample_reload('<?php echo $sample ?>', false)">
					              <i class="fa fa-refresh"></i> Reload
					            </a>
					            <a id="sample-download" class="btn btn-app bg-purple">
					              <i class="fa fa-download"></i> Download
					            </a>					            
					            <a class="btn btn-app bg-maroon" OnClick="toggle_favorite('<?php echo $sample ?>')">
					              <i id="sample-fav" class="fa fa-star-o"></i> Favorite
					            </a>
					            <?php if (IsModuleEnabled("virustotal")) {	 ?>
					            <a class="btn btn-app bg-blue" OnClick="sample_vt_scan('<?php echo $sample ?>')">
					              <i class="fa fa-search"></i> VT Scan
					            </a>
					            <?php } ?>
					            <?php if (IsModuleEnabled("cuckoo")) {	 ?>
					            <a class="btn btn-app bg-blue" data-id="<?php echo $sample ?>" data-toggle="modal" data-target="#submitCuckooModal">
					              <i class="fa fa-cogs"></i> Cuckoo Scan
					            </a>
					            <?php } ?>
					            <?php if (IsModuleEnabled("pedata")) {	 ?>
					            <a id="pedata-action" class="btn btn-app bg-blue" OnClick="sample_pedata_scan('<?php echo $sample ?>')" style="display: none;">
					              <i class="fa fa-rocket"></i> PE Data
					            </a>
					            <?php } ?>
					            <?php if (IsModuleEnabled("officedata")) {	 ?>
					            <a id="officedata-action" class="btn btn-app bg-blue" OnClick="sample_officedata_scan('<?php echo $sample ?>')" style="display: none;">
					              <i class="fa fa-file-word-o"></i> Office Data
					            </a>
					            <?php } ?>
					            <?php if (IsModuleEnabled("pdfdata")) {	 ?>
					            <a id="pdfdata-action" class="btn btn-app bg-blue" OnClick="sample_pdfdata_scan('<?php echo $sample ?>')" style="display: none;">
					              <i class="fa fa-file-pdf-o"></i> PDF Data
					            </a>
					            <?php } ?>
					            <a id="sample-remove" class="btn btn-app bg-green" OnClick="sample_update('<?php echo $sample ?>')">
					              <i class="fa fa-send"></i> Update
					            </a>
					            <a id="sample-remove" class="btn btn-app bg-red" OnClick="sample_delete()">
					              <i class="fa fa-remove"></i> Delete
					            </a>
				            </div>
				        </div>	
				        
				        <div class="box box-primary">
				            <div class="box-header with-border">
				              <h3 class="box-title">Advanced Information</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <!-- .bg-red, .bg-yellow, .bg-aqua, .bg-blue, .bg-light-blue, .bg-green, .bg-navy, .bg-teal, .bg-olive, .bg-lime, .bg-orange, .bg-fuchsia, .bg-purple, .bg-maroon, .bg-black, .bg-red-active, .bg-yellow-active, .bg-aqua-active, .bg-blue-active, .bg-light-blue-active, .bg-green-active, .bg-navy-active, .bg-teal-active, .bg-olive-active, .bg-lime-active, .bg-orange-active, .bg-fuchsia-active, .bg-purple-active, .bg-maroon-active, .bg-black-active -->
				            <div class="box-body">				                
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<input type="text" placeholder="add tag..." class="tm-input"/>
										</div>
									</div>
								</div>
								<?php if (IsModuleEnabled("virustotal")) {	 ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="vt-score-descr"><span class="glyphicon glyphicon-eye-open"></span> VirusTotal</span>
											<a href="#" target="_blank" id="sample-vt" class="form-control" aria-describedby="vt-score-descr"><span id="sample-vt-text" class="label label-success" style="font-size: 12px;"></span></a>
										</div>
									</div>
								</div>
								<?php } ?>
								<?php if (IsModuleEnabled("cuckoo")) {	 ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="cuckoo-descr"><span class="glyphicon glyphicon-fire"></span> Cuckoo</span>
											<a href="#" target="_blank" id="sample-cuckoo" class="form-control" aria-describedby="cuckoo-descr"><span id="sample-cuckoo-text" class="label label-success" style="font-size: 12px;"></span></a>
										</div>
									</div>
								</div>
								<?php } ?>
								<?php if (IsModuleEnabled("pedata")) {	 ?>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="pdbpath-descr"><span class="glyphicon glyphicon-floppy-disk"></span> PDB Path</span>
											<input type="text" id="sample-pdbpath" class="form-control" aria-describedby="pdbpath-descr" readonly>
										</div>
									</div>
								</div>
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="imphash-descr"><span class="glyphicon glyphicon-map-marker"></span> Imports Hash</span>
											<input type="text" id="sample-imphash" class="form-control" aria-describedby="imphash-descr" readonly>
										</div>
									</div>
								</div>	
								<div class="row" style="padding-bottom: 10px">
									<div class="control-group col col-lg-12">
										<div class="input-group">
											<span class="input-group-addon" id="signer-descr"><span class="glyphicon glyphicon-user"></span> Signer</span>
											<input type="text" id="sample-signer" class="form-control" aria-describedby="signer-descr" readonly>
										</div>
									</div>
								</div>	
								<?php } ?>
				            </div>
				        </div>						
					
					</section>
					
				</div>
				<div class="row">
					<section class="col-lg-6 connectedSortable">	
					
						<div class="box box-primary">
				            <div class="box-header with-border">
				              <h3 class="box-title">Comment</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
				                </button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				                <textarea id='t_comment' style='width: 100%; height: 300px;'></textarea>
				            </div>
				        </div>	
										
					</section>
					
					<section class="col-lg-6 connectedSortable">	
					
						<div class="box box-primary">
				            <div class="box-header with-border">
				              <h3 class="box-title">Related URLs</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
				                </button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<div id="surveyForm" method="post" class="form-horizontal">                            									 											 
		                            <div class="form-group" id="url_node_first">
		                                <div class="col-xs-4">
		                                    <input type="text" placeholder="Description" id="name_first" class="form-control" value="" />
		                                </div>
		                                <div class="col-xs-7">
		                                    <input type="text" placeholder="http://domain.tld" id="url_first" class="form-control" value="" />
		                                </div>
		                                <div class="col-xs-1">
		                                    <button type="button" class="btn btn-default" OnClick="modal_add_url_area();">
		                                        <i class="glyphicon glyphicon-plus"></i>
		                                    </button>
		                                </div>
		                            </div>
		                        
		                            <!-- The option field template containing an option field and a Remove button -->
		                            <div class="form-group hide" id="urltemplate">
		                                <div class="col-xs-4">
		                                    <input class="form-control" type="text" id="name_next" placeholder="Description" />
		                                </div>
		                                <div class="col-xs-7">
		                                    <input class="form-control" type="text" id="url_next" placeholder="http://domain.tld" />
		                                </div>
		                                <div class="col-xs-1">
		                                    <button type="button" class="btn btn-default" OnClick="modal_remove_url_area($(this));">
		                                        <i class="glyphicon glyphicon-minus"></i>
		                                    </button>
		                                </div>
		                            </div>
		                        </div>
				            </div>
				        </div>	
										
					</section>
				</div>	
			  </div>
			  
			  <!-- PE Data -->			  
			  <div id="pedata-tab" class="tab-pane fade">
			  	<div class="row" id="pe-div-warnings">
					<section class="col-lg-12 connectedSortable">
						 <ul id="pe-div-warnings-content" class="list-group">						  
						</ul>						
					</section>
				</div>
				<div class="row" id="div-peheaders">
					<section class="col-lg-3 connectedSortable">
						<div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">DOS Header</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<div class="table-responsive">
									  <table id="dosheader" class="table table-striped table-hover">
									  	<tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				        <div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">File Header</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive">
									  <table id="fileheader" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>	
					</section>
					<section class="col-lg-3 connectedSortable">
						<div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Optional Header</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive">
									  <table id="optionalheader" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>	
					</section>
					<section class="col-lg-6 connectedSortable">
						<div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Sections </h3>				              
				              <div class="box-tools pull-right">
				              	<span id="sections-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive">
									  <table id="sections" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				        <div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Digital Signature </h3>				              
				              <div class="box-tools pull-right">
				                <span id="digisig-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive">
									  <table id="digisig" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				        <div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Resources</h3>				
				              <div class="box-tools pull-right">
				                <span id="resources-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive" style="max-height: 400px; overflow: auto;">
									  <table id="resources" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				        <div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Strings (Resources)</h3>				
				              <div class="box-tools pull-right">
				                <span id="strings-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive" style="max-height: 400px; overflow: auto;">
									  <table id="strings" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				        <div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Debug</h3>				
				              <div class="box-tools pull-right">
				                <span id="debug-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	 <div class="table-responsive" style="max-height: 400px; overflow: auto;">
									  <table id="pdbpath" class="table table-striped table-hover">
									    <tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>					        
				        <div class="box box-default">
				            <div class="box-header with-border">
				              <h3 class="box-title">Strings (Raw)</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
				                </button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				                <textarea id='t_pestrings_raw' style='width: 100%; height: 300px;' readonly></textarea>
				            </div>
				        </div>				        
					</section>
				</div>		
			  </div>
			  
			  <!-- Office Data -->			  
			  <div id="officedata-tab" class="tab-pane fade">
			    <div class="row" id="office-div-warnings">
					<section class="col-lg-12 connectedSortable">
						 <ul id="office-div-warnings-content" class="list-group">						  
						</ul>						
					</section>
				</div>	
				<div class="row" id="div-officedata">
					<section class="col-lg-5 connectedSortable">
						<div class="box box-default">
							<div class="box-header with-border">
				              <h3 class="box-title">Counters</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<div class="table-responsive">
									  <table id="officedata_counters" class="table table-striped table-hover">
									  	<tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				    </section>
					<section class="col-lg-7 connectedSortable">
						<div class="box box-default">
							<div class="box-header with-border">
				              <h3 class="box-title">Macros</h3>				
				              <div class="box-tools pull-right">
				                <span id="macros-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<div class="table-responsive">
									  <table id="officedata_macros" class="table table-striped table-hover">
									  	<tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				    </section>				    
				</div>
				<div class="row">
					<section class="col-lg-12 connectedSortable">
						<div id="box-officedata_content" class="box box-default">
							<div class="box-header with-border">
				              <h3 class="box-title">Deobfuscated Code</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<textarea id='officedata_content' style='width: 100%; height: 600px;' readonly></textarea>
				            </div>
				        </div>						
					</section>
				</div>	
			  </div>
			  
			  <!-- PDF Data -->			  
			  <div id="pdfdata-tab" class="tab-pane fade">
			    <div class="row" id="pdf-div-warnings">
					<section class="col-lg-12 connectedSortable">
						 <ul id="pdf-div-warnings-content" class="list-group">						  
						</ul>						
					</section>
				</div>	
				<div class="row" id="div-pdfdata">		
					<section class="col-lg-12 connectedSortable">
						<div class="box box-primary">
				            <div class="box-header with-border">
				              <h3 class="box-title">General information</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
				                </button>
				                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				                <textarea id='t_pdfinfo' style='width: 100%; height: 300px;' readonly></textarea>
				            </div>
				        </div>	
						<div class="box box-default">
							<div class="box-header with-border">
				              <h3 class="box-title">Streams</h3>				
				              <div class="box-tools pull-right">
				                <span id="streams-badge" class="badge">0</span>
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<div class="table-responsive">
									  <table id="pdfdata_streams" class="table table-striped table-hover">
									  	<tbody>								      
									    </tbody>
									  </table>
								</div>
				            </div>
				        </div>
				    </section>			    
				</div>	
			  </div>
			  
			  <!-- Hex View -->			  
			  <div id="hexdata-tab" class="tab-pane fade">
				<div class="row">
					<section class="col-lg-12 connectedSortable">
						<div id="box-hexdata_content" class="box box-default">
							<div class="box-header with-border">
				              <h3 class="box-title">Hex Code (<?php echo $GLOBALS["config"]["ui"]["hex_max_length"]; ?> first bytes)</h3>				
				              <div class="box-tools pull-right">
				                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				              </div>
				            </div>
				            <div class="box-body">
				            	<textarea id='hexdata_content' style='width: 100%; height: 600px; font-family: Courier; font-size:12px;' readonly></textarea>
				            </div>
				        </div>
					</section>
				</div>		
			  </div>			  
			</div>
		  <!--</div>-->
		  
		  <div id="submitCuckooModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">	
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Cuckoo Submit:</h4>
                    </div>
                    <div class="modal-body" id="body_cuckoo_submit">
                    	<div class="form-group">
							<label for="select_cuckoo_machine">Select machine:</label>
							<select class="form-control" id="select_cuckoo_machine">
						  	</select>
						</div> 
                        <div id="select_cuckoo_options">						 
						</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success edit" OnClick="sample_cuckoo_scan_modal();">
                            <i class="glyphicon glyphicon-send"></i>
                            <span>Submit</span>
                        </button>
                    </div>
                </div>
            </div>
          </div>
	
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
<!-- AdminLTE App -->
<script src="dist/js/app.min.js"></script>
<!-- TinyMCE -->
<script src="plugins/tinymce/js/tinymce/tinymce.min.js"></script>

<!-- MRF -->
<!--<script src="plugins/jQueryUpload/js/vendor/jquery.min.js"></script>-->
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<!--<script src="plugins/jQueryUpload/js/vendor/jquery.ui.widget.js"></script>-->
<script src="plugins/jQueryUI/jquery-ui.min.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->

<!-- Bootstrap 3.3.6 -->
<!-- Bootstrap needs to be placed AFTER jquery-ui because of tootltip conflicts -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>

<!-- tags -->
<script type="text/javascript" src="plugins/jQueryUpload/js/tagmanager.js"></script>
<!-- PACE -->
<script data-pace-options='{"ajax":false,"document":false,"eventLag":false,"startOnPageLoad":false}' src="plugins/pace/pace.min.js"></script>
<!-- The main application script -->
<script src="dist/js/main.js"></script>

<script>
$(function() {
	initSample('<?php echo $sample ?>');
});
</script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
     Both of these plugins are recommended to enhance the
     user experience. Slimscroll is required when using the
     fixed layout. -->
</body>
</html>
