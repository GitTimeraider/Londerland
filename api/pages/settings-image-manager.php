<?php
$GLOBALS['londerlandPages'][] = 'settings_image_manager';
function get_page_settings_image_manager($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	if ((!$Londerland->hasDB())) {
		return false;
	}
	if (!$Londerland->qualifyRequest(1, true)) {
		return false;
	}
	return '
<script>
	buildImageManagerView();
	londerlandLoadLibrary("dropzone").then(function() {
	new Dropzone("#new-image-form", {
		url: "api/v2/image",
		headers:{ "formKey": local("g","formKey") },
		init: function() {
		this.on("complete", function(file) {
			if(file["status"] === "success"){
				buildImageManagerView();
			}else{
				let response = JSON.parse(file.xhr.responseText);
				message("Upload Error", response.response.message,activeInfo.settings.notifications.position,"#FFF","error","5000");
			}
		});
		this.on("error", function(file, response) {
			$(file.previewElement).find(".dz-error-message").text(response?.response?.message ?? response);
		});
	  }
	});
	});
</script>
<div class="card bg-org card-info">
	<div class="card-header">
		<span lang="en">View Images</span>
		<button type="button" class="btn btn-info btn-circle float-end popup-with-form m-r-5" href="#new-image-form" data-effect="mfp-3d-unfold"><i class="fa fa-upload"></i> </button>
	</div>
	<div class="card-wrapper collapse show" aria-expanded="true">
		<div class="card-body bg-org" >
			<div id="gallery-content">
				<div id="gallery-content-center" class="settings-image-manager-list"></div>
			</div>
		</div>
	</div>
</div>
<form action="#" id="new-image-form" class="mfp-hide white-popup-block mfp-with-anim dropzone" enctype="multipart/form-data">
	<h1 lang="en">Upload Image</h1>
	<div class="fallback">
		<input name="file" type="file" multiple />
	</div>
	<div class="clearfix"></div>
</form>
';
}