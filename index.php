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
  $filters = array();
  if (isset($_GET["tag"])) {
  	$filters["tag"] = $_GET["tag"];
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

  <!-- Generic page styles -->
  <link rel="stylesheet" href="plugins/jQueryUpload/css/style.css">
  <!-- blueimp Gallery styles -->
  <link rel="stylesheet" href="plugins/jQueryUpload/css/blueimp-gallery.min.css">
  <!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
  <link rel="stylesheet" href="plugins/jQueryUpload/css/jquery.fileupload.css">
  <link rel="stylesheet" href="plugins/jQueryUpload/css/jquery.fileupload-ui.css">
  <!-- CSS adjustments for browsers with JavaScript disabled -->
  <noscript><link rel="stylesheet" href="plugins/jQueryUpload/css/jquery.fileupload-noscript.css"></noscript>
  <noscript><link rel="stylesheet" href="plugins/jQueryUpload/css/jquery.fileupload-ui-noscript.css"></noscript>
  <!-- jqPagination styles -->
  <link rel="stylesheet" href="plugins/jQueryUpload/css/jqpagination.css" />	
  <!-- tags -->
  <link rel="stylesheet" type="text/css" href="plugins/jQueryUpload/css/tagmanager.css" />
  <!-- Pace style -->
  <link rel="stylesheet" href="plugins/pace/pace.min.css">
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
  
  <style type="text/css">
    .table-responsive {
		min-height: 400px !important;
	}
	
	ul#dropdown-item-actions,
	ul#dropdown-item-actions {
	    z-index: 10000;
	}
  </style>
  
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

    <!-- Main content -->
    <section class="content">

	<!-- Your Page Content Here -->
	<div class="panel panel-info">
		<div class="panel-heading">Repository Information</div>
		<div class="panel-body">
			<span style="font-weight: bold; color: black;">MRF v<?php echo $GLOBALS["config"]["version"]?></span>
			<br/>
			<?php if (IsModuleEnabled("cuckoo")) { ?>
			<span>Powered by Cuckoo Sandbox: </span>
			<a id="cuckoo-status-href" href="#" title="Cuckoo">
				<span id="cuckoo-status" style="font-weight: bold; color: black;"> Not available</span>
			</a>	
			<?php } ?>	
			<br>
			<span>Samples: </span>
			<span id="files-count" style="font-weight: bold; color: black;"> Not available</span>
		</div>
	</div>
	<!-- The file upload form used as target for the file upload widget -->
	<form id="fileupload" action="api.php?action=uploadfiles" method="POST" enctype="multipart/form-data">	
		<div id="upload" class="tab-pane fade in active">
			<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
			<div class="row fileupload-buttonbar">
				<div class="col-lg-7">
					<!-- The fileinput-button span is used to style the file input field as button -->
					<span class="btn btn-success fileinput-button">
						<i class="glyphicon glyphicon-plus"></i>
						<span>Add files...</span>
						<input type="file" name="files[]" multiple>
					</span>
					<button type="submit" class="btn btn-primary start">
						<i class="glyphicon glyphicon-upload"></i>
						<span>Start upload</span>
						<span class="badge" id="btn-upload-all-badge"></span>
					</button>
					<button type="reset" class="btn btn-warning cancel">
						<i class="glyphicon glyphicon-ban-circle"></i>
						<span>Cancel upload</span>
					</button>
					<button type="button" class="btn btn-danger delete">
						<i class="glyphicon glyphicon-trash"></i>
						<span>Delete</span>
					</button>
					<div class="btn-group">
					  <button type="button" class="btn btn-default">Download</button>
					  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    <span class="caret"></span>
					    <span class="sr-only">Toggle Dropdown</span>
					  </button>
					  <ul class="dropdown-menu">
					    <li>
					    	<a href="#" class="menu-button-download" OnClick="bulk_download(false)">
								<i class="glyphicon glyphicon-download"></i>
								<span>Download ZIP</span>
							</a>
						</li>
						<!-- Disabled until password protected is implemented -->
						<li style="display: none;">
					    	<a href="#" class="menu-button-download-pw" OnClick="bulk_download(true)">
								<i class="glyphicon glyphicon-download"></i>
								<span>Download ZIP (Pass: malware)</span>
							</a>
						</li>
					  </ul>
					</div>
					<button type="button" class="btn btn-default" OnClick="refreshRepo()">
						<i class="glyphicon glyphicon-refresh"></i>
						<span>Refresh</span>
					</button>
					<input type="checkbox" class="toggle">
					<!-- The global file processing state -->
					<span class="fileupload-process"></span>					
				</div>
				<!-- The global progress state -->
				<div class="col-lg-5 fileupload-progress fade">
					<!-- The global progress bar -->
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
						<div class="progress-bar progress-bar-info" style="width:0%;"></div>
					</div>
					<!-- The extended global progress state -->
					<div class="progress-extended">&nbsp;</div>
				</div>
			</div>
		</div>	
			
		<div id="search" class="panel-group">
		  <div class="panel panel-info">
		    <div class="panel-heading">
		      <h4 class="panel-title">
		        <a data-toggle="collapse" href="#collapse_search"><span class="glyphicon glyphicon-search"></span> Search</a>
		      </h4>
		    </div>
		    <div id="collapse_search" class="panel-collapse collapse">
			    <div class="panel-body">
			    <div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<span class="btn btn-default fileinput-button" OnClick="clear_search()">
							<i class="glyphicon glyphicon-ban-circle"></i>
							<span>Clear</span>
						</span>
					</div>
				</div>
				<div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="uploader-descr"><span class="glyphicon glyphicon-user"></span> Uploader</span>
							<input type="text" id="uploader-descr-input" class="form-control" placeholder="some user" aria-describedby="uploader-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="date-descr"><span class="glyphicon glyphicon-time"></span> Date</span>
							<input type="text" id="date-descr-input" class="form-control" placeholder="2015-12-21" aria-describedby="date-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
				</div>
				<div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="vendor-descr"><span class="glyphicon glyphicon-glass"></span> Threat Name</span>
							<input type="text" id="vendor-descr-input" class="form-control" placeholder="Tr.Zeus" aria-describedby="vendor-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="comment-descr"><span class="glyphicon glyphicon-pencil"></span> Comment</span>
							<input type="text" id="comment-descr-input" class="form-control" placeholder="some comment" aria-describedby="comment-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
				</div>
				<div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="md5-descr"><span class="glyphicon glyphicon-map-marker"></span> MD5</span>
							<input type="text" id="md5-descr-input" class="form-control" placeholder="ba35799770abde5da0315e60694ce42e" aria-describedby="md5-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="name-descr"><span class="glyphicon glyphicon-file"></span> Filename</span>
							<input type="text" id="name-descr-input" class="form-control" placeholder="filename.exe" aria-describedby="name-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
				</div>	
				<div class="row" style="padding-bottom: 10px">
					<?php if (IsModuleEnabled("virustotal")) { ?>
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="vt-descr"><span class="glyphicon glyphicon-eye-open"></span> VirusTotal</span>
							<input type="text" id="vt-descr-input" class="form-control" placeholder=">10" aria-describedby="vt-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
					<?php } ?>
					<?php if (IsModuleEnabled("cuckoo")) { ?>
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="cuckoo-descr"><span class="glyphicon glyphicon-fire"></span> Cuckoo</span>
							<select id="cuckoo-descr-input" aria-describedby="cuckoo-descr" class="selectpicker form-control" data-live-search="true" onchange="delayed_get_files()">
								<option value="none"> </option>
								<option value="no-results">No Results</option>
							    <option value="results">Results</option>
							    <option value="scanning">Scanning</option>
							</select>
						</div>
					</div>
					<?php } ?>
				</div>	
				<div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="size-descr"><span class="glyphicon glyphicon-signal"></span> Size</span>
							<input type="text" id="size-descr-input" class="form-control" placeholder=">1000" aria-describedby="size-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="fav-descr"><span class="glyphicon glyphicon-star"></span> Favorite</span>
							<select id="fav-descr-input" aria-describedby="fav-descr" class="selectpicker form-control" data-live-search="true" onchange="delayed_get_files()">
								<option value="none"> </option>
							    <option value="no-fav">No</option>
							    <option value="fav">Yes</option>
							</select>
						</div>
					</div>
				</div>	
				<div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="tags-descr"><span class="glyphicon glyphicon-tags"></span> Tags</span>
							<input type="text" id="tags-descr-input" class="form-control" placeholder="tag" aria-describedby="tags-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
                    <div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="urls-descr"><span class="glyphicon glyphicon-globe"></span> URLs</span>
							<input type="text" id="urls-descr-input" class="form-control" placeholder="url" aria-describedby="urls-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
				</div>
				<div class="row" style="padding-bottom: 10px">
					<div class="control-group col col-lg-4">
						<div class="input-group">
							<span class="input-group-addon" id="sha256-descr"><span class="glyphicon glyphicon-map-marker"></span> SHA256</span>
							<input type="text" id="sha256-descr-input" class="form-control" placeholder="5ed702ca0e6b87ec2f6503cb9e62ef27e94259608ca87d4abfe1aa08cf967fbb" aria-describedby="md5-descr" onkeyup="delayed_get_files()">
						</div>
					</div>
				</div>
				</div>				    
		    </div>
		  </div>
		</div>
		<div id='alert'></div>			
		<div class="pagination">
			<a href="#" class="first" data-action="first">&laquo;</a>
			<a href="#" class="previous" data-action="previous">&lsaquo;</a>
			<input type="text" readonly="readonly" data-max-page="40" />
			<a href="#" class="next" data-action="next">&rsaquo;</a>
			<a href="#" class="last" data-action="last">&raquo;</a>
		</div>			
		<!-- The table listing the files available for upload/download -->
		<div class="table-responsive">
            <table role="presentation" class="table table-hover table-striped">
                <!--<thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Date</th>
                        <th>Threat Name</th>
                        <th>Hash MD5</th>
                        <th>Filename</th>
                        <th>Size</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>-->
                <tbody class="files"></tbody>
            </table>
        </div>
	</form>
		
		<!-- The template to display files available for upload -->
		<script id="template-upload" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
			<tr class="template-upload fade">
				<td colspan="4">
					<span class="name">{%=file.name%}</span>
				</td>
				<td>
					<span class="size">Processing...</span>
				</td>
				<td colspan=4>
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="min-width: 50px;">
						<div class="progress-bar progress-bar-info"></div>
					</div>
				</td>
				<td>
					<div class="checkbox">
						<?php if (IsModuleEnabled("virustotal")) { ?>
						<label class="checkbox-inline">
							<input type="checkbox" id="vtsubmit_{%=file.index%}" value="" <?php if ($GLOBALS["config"]["modules"]["virustotal"]["automatic_upload"]) { ?> checked <?php } ?> >
							<span class="label label-warning">VirusTotal</span>
						</label>
						<?php } ?>
						<?php if (IsModuleEnabled("cuckoo")) { ?>
						<label class="checkbox-inline">
							<input type="checkbox" id="cuckoosubmit_{%=file.index%}" value="">
							<span class="label label-info">Cuckoo</span>
						</label>
						<?php } ?>
					</div>
				</td>
				<td>
					<input type="text" id="tags_upload_{%=file.index%}" placeholder="add tag..." class="tm-input tm-input-success tm-input-small"/>
				</td>
				<td colspan="2">
					{% if (!i && !o.options.autoUpload) { %}
						<button class="btn btn-primary btn-xs start" disabled>
							<i class="glyphicon glyphicon-upload"></i>
							<span>Start</span>
						</button>
					{% } %}
					{% if (!i) { %}
						<button class="btn btn-warning btn-xs cancel">
							<i class="glyphicon glyphicon-ban-circle"></i>
							<span>Cancel</span>
						</button>
					{% } %}
				</td>
			</tr>
		{% } %}
		</script>
		
		<!-- The template to display files available for download -->
		<script id="template-download" type="text/x-tmpl">		
		{% for (var i=0, file; file=o.files[i]; i++) { %}
			{% if (file.error) { %}
				<tr class="template-download fade" id="row_{%=file.md5%}">    
					<td colspan="4">
						<span class="name">{%=file.filename%}</span>
					</td>
					<td colspan="9">  
						<div><span class="label label-danger">Error</span> {%=file.error%}</div>
					</td>
				</tr>
            {% continue; } %}	
			<tr class="template-download fade" id="row_{%=file.md5%}"> 				         
                {% if (file.deleteUrl) { %}
                    <td class="visible-md visible-lg visible-xl"><input type="checkbox" id="select_{%=file.md5%}" name="delete" value="1" class="toggle" style="vertical-align: middle;" data-toggle="tooltip" title="Select for action"></td>
                {% } %}
				<td class="visible-md visible-lg visible-xl">
                {% if (file.favorite) { %}
                    <a href="#fav_{%=file.md5%}" OnClick="favorite('{%=file.md5%}')" data-toggle="tooltip" title="Favorite"><span id="fav_star_{%=file.md5%}" class="glyphicon glyphicon-star" style="font-size: 1.5em; vertical-align: middle;"></span></a>
                {% } else { %}
                    <a href="#fav_{%=file.md5%}" OnClick="favorite('{%=file.md5%}')" data-toggle="tooltip" title="Favorite"><span id="fav_star_{%=file.md5%}" class="glyphicon glyphicon-star-empty" style="font-size: 1.5em; vertical-align: middle;"></span></a>
                {% } %}		
				{% if (file.locked) { %}
                    <span class="glyphicon glyphicon-lock" style="font-size: 1.0em; vertical-align: middle; color: #f39c12;" data-toggle="tooltip" title="Locked"></span>
                {% } %}				
				</td>
                <td>
                    <a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]; ?>sample.php?hash={%=file.md5%}" target="_blank" data-toggle="tooltip" title="Open"><span class="glyphicon glyphicon-open" style="font-size: 1.5em; vertical-align: middle;"></span></a>
                </td>
                <td class="visible-md visible-lg visible-xl">
					{% if (file.user_avatar && file.user_avatar.length > 0) { %}
					<img alt="" height="24px" width="24px" class="img-circle" src="data:image/png;base64,{%=file.user_avatar%}" data-toggle="tooltip" title="Uploader: {%=file.user_name%}">
					{% } else { %}	
					<img alt="" height="24px" width="24px" class="img-circle" src="<?php echo $GLOBALS["config"]["urls"]["baseUrl"] ."dist/img/noavatar.jpg"?>" data-toggle="tooltip" title="Uploader: {%=file.user_name%}">
					{% } %}	
					{% if (file.icon && file.icon.length > 0) { %}
					<img alt="" height="24px" width="24px" class="img" src="data:image/png;base64,{%=file.icon%}" data-toggle="tooltip" title="Icon">
					{% } %}	
				</td>
				<td>
					<span class="name" data-toggle="tooltip" title="Upload date">{%=file.date%}</span>
				</td>
				<td class="visible-md visible-lg visible-xl">
					{% if (file.criticity == 1) { %}
						<span id="vendor_{%=file.md5%}" data-toggle="tooltip" title="Vendor Name: {%=file.threat%}" class="label label-danger">{%=file.threat%}</span>
					{% } else if (file.criticity == 2) { %}
						<span id="vendor_{%=file.md5%}" data-toggle="tooltip" title="Vendor Name: {%=file.threat%}" class="label label-warning">{%=file.threat%}</span>
					{% } else { %}					
						<span id="vendor_{%=file.md5%}" data-toggle="tooltip" title="Vendor Name: {%=file.threat%}" class="label label-default">{%=file.threat%}</span>
					{% } %}	
				</td>
                <td class="visible-md visible-lg visible-xl">
                    <div class="col-md-2">
                        <input type="text" id="tags_{%=file.md5%}" placeholder="add tag..." class="tm-input tm-input-small"/>		
                    </div>
                </td>			
				<td>
					<span class="name">
						{% if (file.url) { %}
							<a href="{%=file.url%}" data-toggle="tooltip" title="MD5: {%=file.md5%}" download="{%=file.md5%}">{%=file.md5%}</a>
						{% } else { %}
							<span>{%=file.md5%}</span>
						{% } %}
					</span>					
				</td>                
				<td>
                    {% if (file.filename.length > 25) { %}
					    <span data-toggle="tooltip" title="File name: {%=file.filename%}" class="name">{%=file.filename.substring(0,25).concat('...')%}</span>
                    {% } else { %}
                        <span data-toggle="tooltip" title="File name: {%=file.filename%}" class="name">{%=file.filename%}</span>
                    {% } %}
				</td>
				<td class="visible-sm visible-md visible-lg visible-xl">
					<span data-toggle="tooltip" title="File size: {%=o.formatFileSize(file.size)%}" class="size">{%=o.formatFileSize(file.size)%}</span>
				</td>
				<?php if (IsModuleEnabled("virustotal")) { ?>
				<td class="visible-sm visible-md visible-lg visible-xl">					
					{% if (file.virustotal_status == 1) { %}
						{% if (file.virustotal_score < 10) { %}
							<a href="{%=file.virustotal_link%}" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: {%=file.virustotal_score%}" ><span id="vt_score_{%=file.md5%}" class="label label-success">{%=file.virustotal_score%}/55</span></a>			
						{% } else if (file.virustotal_score >= 10 && file.virustotal_score < 20) { %}
							<a href="{%=file.virustotal_link%}" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: {%=file.virustotal_score%}" ><span id="vt_score_{%=file.md5%}" class="label label-warning">{%=file.virustotal_score%}/55</span></a>	
						{% } else { %}
							<a href="{%=file.virustotal_link%}" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: {%=file.virustotal_score%}" ><span id="vt_score_{%=file.md5%}" class="label label-danger">{%=file.virustotal_score%}/55</span></a>	
						{% } %}
					{% } else if (file.virustotal_status == 0) { %}
						<a href="#" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: File unknown" ><span id="vt_score_{%=file.md5%}" class="label label-warning">Unknown</span></a>						
					{% } else if (file.virustotal_status == -6) { %}
						<a href="#" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: File not checked" ><span id="vt_score_{%=file.md5%}" class="label label-default">Not Checked</span></a>	
					{% } else if (file.virustotal_status == -5) { %}
						<a href="#" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: File too big" ><span id="vt_score_{%=file.md5%}" class="label label-primary">Too big</span></a>	
					{% } else if (file.virustotal_status == -3) { %}
						<a href="#" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: API limit reached" ><span id="vt_score_{%=file.md5%}" class="label label-primary">API Error</span></a>	
					{% } else if (file.virustotal_status == -2) { %}
						<a href="{%=file.virustotal_link%}" id="vt_score_link{%=file.md5%}" target="_blank" data-toggle="tooltip" title="VirusTotal score: Currently scanning..."><span id="vt_score_{%=file.md5%}" class="label label-primary">Scanning</span></a>
					{% } else { %}
						<a href="#" id="vt_score_link_{%=file.md5%}" target="_blank" data-toggle="tooltip" title="Error" ><span id="vt_score_{%=file.md5%}" class="label label-primary">Error</span></a>	
					{% } %}			
				</td>
				<?php } ?>
                <?php if (IsModuleEnabled("cuckoo")) {	 ?>
                <td class="visible-sm visible-md visible-lg visible-xl">
                    {% if (file.cuckoo_status == 0 && file.cuckoo_link ) { %}
                        <a href="{%=file.cuckoo_link%}" id="ck_link{%=file.md5%}" target="_blank" data-toggle="tooltip" title="Cuckoo results" style="font-weight: bold; color: green;"><span id="ck_{%=file.md5%}" class="label label-success">Results</span></a>
                    {% } else if (file.cuckoo_status == -1) { %}
                        <a href="#" id="ck_link{%=file.md5%}" target="_blank" data-toggle="tooltip" title="Cuckoo scanning" style="font-weight: bold; color: green;"><span id="ck_{%=file.md5%}" class="label label-warning">Scanning</span></a>
                    {% } else { %}
                        <a href="#" id="ck_link{%=file.md5%}" target="_blank" data-toggle="tooltip" title="Cuckoo nothing" style="font-weight: bold; color: green;"><span id="ck_{%=file.md5%}" class="label label-primary">None</span></a>
                    {% } %}														
                </td>
                <?php } ?>
				<td>
					<div class="btn-group">
                    {% if (i >= o.files.length - 10 && i >= 10) { %}
                    <div class="dropup">
                    {% } else { %}
                    <div class="dropdown">
                    {% } %}		
					<button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
						<i class="glyphicon glyphicon-chevron-down"></i>
						<span class="sr-only">Toggle Dropdown</span>
					</button>
					<ul id="dropdown-item-actions" class="dropdown-menu dropdown-menu-right" role="menu">					
						<li><a href="<?php echo $GLOBALS["config"]["urls"]["baseUrl"]; ?>sample.php?hash={%=file.md5%}" target="_blank" >
							<i id="edit_img_{%=file.md5%}" class="glyphicon glyphicon-open"></i>
							<span>Open</span>
						</a></li>                    		
						<li><a href="#" class="menu-button-edit" id="edit_{%=file.md5%}" data-id="{%=file.md5%}" OnClick="edit_data('{%=file.md5%}');" style="display: none;">
							<i id="edit_img_{%=file.md5%}" class="glyphicon glyphicon-edit"></i>
							<span id="edit_text_{%=file.md5%}">Quick Edit</span>
						</a></li>
						{% if (file.deleteUrl) { %}
							<li><a href="#" class="menu-button-delete delete" id="delete_{%=file.md5%}" OnClick="delete_sample('{%=file.md5%}','{%=file.deleteUrl%}','{%=file.deleteType%}','{file.deleteWithCredentials}');">
								<i class="glyphicon glyphicon-trash"></i>
								<span>Delete</span>
							</a></li>				
						{% } %}
                        <li class="divider"></li>
                        <li><a href="#" class="menu-button-comment" id="comment_{%=file.md5%}" data-id="{%=file.md5%}" data-comment-value="{%=file.comment%}" data-toggle="modal" data-target="#commentModal">
                            <i class="glyphicon glyphicon-pencil"></i>
                            <span>Comment</span>
                        </a></li> 
                        <li><a href="#" class="menu-button-urls" id="urls_{%=file.md5%}" data-id="{%=file.md5%}" data-urls-value="{%=file.urls%}" data-toggle="modal" data-target="#urlModal">
                            <i class="glyphicon glyphicon-globe"></i>
                            <span>Manage URLs</span>
                        </a></li> 
						<?php if (IsModuleEnabled("virustotal")) {	 ?>
						<li class="divider"></li>
                        <li><a href="#" class="menu-button-vt-comment" id="comment_vt_{%=file.md5%}" data-id="{%=file.md5%}" data-toggle="modal" data-target="#commentVTModal">
                            <i class="glyphicon glyphicon-pencil"></i>
                            <span>VT Comment</span>	
                        </a></li>
						{% if (file.virustotal_status == 1 || file.virustotal_status == 0 || file.virustotal_status == -6) { %}
						<li><a href="#" class="menu-button-scan-vt" id="vt_scan_{%=file.md5%}" type="button" OnClick="vt_scan('{%=file.md5%}');">							
                            {% if (file.virustotal_status == 0 || file.virustotal_status == -6) { %}
                            <i class="glyphicon glyphicon-upload"></i>
							<span id="vt_scan_text_{%=file.md5%}">VT Scan</span>
                            {% } else { %}
                            <i class="glyphicon glyphicon-repeat"></i>
                            <span id="vt_scan_text_{%=file.md5%}">VT Rescan</span>
                            {% } %}	
						</a></li>
						{% } %} 
						<?php }	 ?>   
                        <?php if (IsModuleEnabled("cuckoo")) {	 ?>
                        {% if (file.cuckoo_status != -1) { %}
						<li class="divider"></li>
						<li><a href="#" class="menu-button-urls" id="ck_scan_{%=file.md5%}" data-id="{%=file.md5%}" data-toggle="modal" data-target="#submitCuckooModal">
                            {% if (file.cuckoo_status == 0) { %}
                            <i class="glyphicon glyphicon-repeat"></i>
                            <span id="ck_scan_text_{%=file.md5%}">Cuckoo Rescan</span>
                            {% } else { %}
                            <i class="glyphicon glyphicon-upload"></i>
                            <span id="ck_scan_text_{%=file.md5%}">Cuckoo Scan</span>
                            {% } %}
                        </a></li>
						{% } %}
                        <?php } ?>
					</ul>
					</div>	
                    </div>								
				</td>
			</tr>			
		{% } %}	
		</script>
        
        <!-- Old code for row expansion -->
        <!--<td>
                <a href="#more_{%=file.md5%}" data-toggle="collapse"><span class="glyphicon glyphicon-plus" style="font-size: 1em; vertical-align: middle;"></span></a>
            </td>-->
        <!--<tr id="more_{%=file.md5%}" class="collapse in">
            <td colspan="100%">
                <div class="panel panel-info">
                <div class="panel-body form-group" style="margin-bottom: 0px;">	
                </div>
                </div>
            </td>
        </tr>-->
                
        <div id="commentModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">	
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Comment: (Click to edit)</h4>
                    </div>
                    <div class="modal-body" id="body_comment">
                        <div id="p_comment" style='width: 100%; height: 400px; margin-top: 20px; overflow: scroll;'></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="urlModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">	
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">URLs:</h4>
                    </div>
                    <div class="modal-body" id="body_urls">
                        <div id="surveyForm" class="form-horizontal">                            									 											 
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success edit" OnClick="modal_send_urls();">
                            <i class="glyphicon glyphicon-send"></i>
                            <span>Update</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="commentVTModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">	
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Comment:</h4>
                    </div>
                    <div class="modal-body" id="body_vt_comment">
                        <textarea id="t_commentvt" style="width: 100%; height: 100px"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success edit" OnClick="vt_comment();">
                            <i class="glyphicon glyphicon-send"></i>
                            <span>Send</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
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
                        <button type="button" class="btn btn-success edit" OnClick="cuckoo_scan_modal();">
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
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="plugins/jQueryUpload/js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="plugins/jQueryUpload/js/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="plugins/jQueryUpload/js/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS is not required, but included for the responsive demo navigation -->
<!--<script src="plugins/jQueryUpload/js/bootstrap.min.js"></script>-->
<!-- blueimp Gallery script -->
<script src="plugins/jQueryUpload/js/jquery.blueimp-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="plugins/jQueryUpload/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload-video.js"></script>
<!-- The File Upload validation plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="plugins/jQueryUpload/js/jquery.fileupload-ui.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->

<!-- Bootstrap 3.3.6 -->
<!-- Bootstrap needs to be placed AFTER jquery-ui because of tootltip conflicts -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>

<!-- jqPagination scripts -->
<script src="plugins/jQueryUpload/js/jquery.jqpagination.js"></script>
<!-- tags -->
<script type="text/javascript" src="plugins/jQueryUpload/js/tagmanager.js"></script>
<!-- PACE -->
<script data-pace-options='{"ajax":false,"document":false,"eventLag":false,"startOnPageLoad":false}' src="plugins/pace/pace.min.js"></script>
<!-- The main application script -->
<script src="dist/js/main.js"></script>

<script>
$(function() {
	var filters = <?php echo json_encode($filters) ?>;
	initRepo(filters);
	//Uncomment this for auto-refresh, though this isn't recommended.
	//setInterval(refreshRepo, 5000);
});
</script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
     Both of these plugins are recommended to enhance the
     user experience. Slimscroll is required when using the
     fixed layout. -->
</body>
</html>
