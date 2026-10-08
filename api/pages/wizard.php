<?php
$GLOBALS['londerlandPages'][] = 'settings_wizard';
function get_page_wizard($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	$suggestedDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $Londerland->random_ascii_string(10) . DIRECTORY_SEPARATOR;
	$mysqliDisabled = extension_loaded('mysqli') ? '' : 'disabled';
	$mysqliLabel = extension_loaded('mysqli') ? '' : ' [PHP module not installed]';
	return '
<script>
    (function() {
        // Field rules per step; empty optional fields are skipped like formValidation did
        const wizardRules = {
            username: { required: "The username is required", length: [3, 30, "The username must be more than 2 and less than 30 characters long"], regexp: [/^[a-zA-Z0-9_\.\@]+$/, "The username can only consist of alphabetical, number, at sign, dot and underscore"] },
            license: { regexp: [/^[a-zA-Z0-9_\.]+$/, "Please choose a license"] },
            email: { required: "The email address is required", email: "The input is not a valid email address" },
            hashKey: { required: "The hash key is required", length: [3, 30, "The hash key must be more than 2 and less than 30 characters long"] },
            dbPath: { required: "The database location is required" },
            dbName: { required: "The Database Name is required", length: [2, 30, "The Database Name must be more than 1 and less than 30 characters long"], regexp: [/^[a-zA-Z0-9_\.]+$/, "The Database Name can only consist of alphabetical, number, dot and underscore"] },
            api: { required: "The API Key is required", length: [20, 20, "The API Key must be 20 characters long"] },
            registrationPassword: { required: "The registration password is required" },
            password: { required: "The password is required", differentFrom: ["username", "The password cannot be the same as username"] }
        };
        function wizardFieldError(field) {
            const rules = wizardRules[field.name];
            const value = $(field).val() ?? "";
            if (!rules) {
                return null;
            }
            if (value === "") {
                return rules.required ?? null;
            }
            if (rules.length && (value.length < rules.length[0] || value.length > rules.length[1])) {
                return rules.length[2];
            }
            if (rules.regexp && !rules.regexp[0].test(value)) {
                return rules.regexp[1];
            }
            if (rules.email && !/^[^\s@]+@[^\s@]+$/.test(value)) {
                return rules.email;
            }
            if (rules.differentFrom && value === $("#validation [name=" + rules.differentFrom[0] + "]").val()) {
                return rules.differentFrom[1];
            }
            return null;
        }
        function wizardValidatePane(pane) {
            let valid = true;
            pane.find(":input[name]").each(function() {
                const error = wizardFieldError(this);
                const group = $(this).closest(".form-group");
                group.find(".invalid-feedback").remove();
                $(this).toggleClass("is-invalid", !!error);
                if (error) {
                    valid = false;
                    group.append($("<div class=\"invalid-feedback d-block\"></div>").text(window.lang.translate(error)));
                }
            });
            return valid;
        }
        const wizard = $("#adminValidator");
        const wizardPanes = wizard.find(".wizard-pane");
        const wizardSteps = wizard.find(".wizard-steps > li");
        let wizardStep = 0;
        function wizardShow(step) {
            wizardStep = step;
            wizardPanes.removeClass("active").eq(step).addClass("active");
            wizardSteps.each(function(index) {
                $(this).toggleClass("current", index === step).toggleClass("done", index < step).removeClass("error");
            });
            wizard.find(".wizard-back").toggleClass("disabled", step === 0);
            wizard.find(".wizard-next").toggleClass("d-none", step === wizardPanes.length - 1);
            wizard.find(".wizard-finish").toggleClass("d-none", step !== wizardPanes.length - 1);
        }
        function wizardFinish() {
            message("Submitting Wizard");
            $(".white-box").block({ message: "<h3><i class=\"fa fa-close\"></i> Submitting Wizard Data...</h3>" });
            const post = $("#validation").serializeToJSON();
            londerlandAPI2("POST", "api/v2/wizard", post).done(function() {
                message("Wizard Data accepted");
                $(".white-box").unblock();
                location.reload();
            }).fail(function(xhr) {
                LonderlandApiError(xhr, "API Error");
                $(".white-box").unblock();
            });
        }
        wizard.on("click", ".wizard-back", function(e) {
            e.preventDefault();
            if (wizardStep > 0) {
                wizardShow(wizardStep - 1);
            }
        });
        wizard.on("click", ".wizard-next, .wizard-finish", function(e) {
            e.preventDefault();
            if (!wizardValidatePane(wizardPanes.eq(wizardStep))) {
                wizardSteps.eq(wizardStep).addClass("error");
                return;
            }
            if (wizardStep < wizardPanes.length - 1) {
                wizardShow(wizardStep + 1);
            } else {
                wizardFinish();
            }
        });
        wizard.on("input change", ":input.is-invalid", function() {
            if (!wizardFieldError(this)) {
                $(this).removeClass("is-invalid").closest(".form-group").find(".invalid-feedback").remove();
            }
        });
        wizardShow(0);
        generateAPI();
        $( ".wizardInput" ).on("focusout change", function() {
            var value = $(this).val();
            var name = $(this).attr(\'name\');
            if (typeof value !== \'undefined\' && typeof name !== \'undefined\') {
                $(\'#verify-\'+name).text(value);
            }
        });
        $(document).on("click", ".wizard-test-database-connection", function() {
            message("Checking Connection","",activeInfo.settings.notifications.position,"#FFF","info","10000");
			let post = $( \'#validation\' ).serializeToJSON();
			londerlandAPI2(\'POST\',\'api/v2/test/database\',post).done(function(data) {
				try {
					let response = data.response;
					messageSingle(response.message,"",activeInfo.settings.notifications.position,"#FFF","success","10000");
				}catch(e) {
					londerlandCatchError(e,data);
				}
			}).fail(function(xhr) {
				LonderlandApiError(xhr, "API Error");
			})
		});
		$(document).on("click", ".database-driver-selector", function () {
			$("#form-dbHost").parent().parent().toggleClass("hidden");
			$("#form-dbUsername").parent().parent().toggleClass("hidden");
			$("#form-dbPassword").parent().parent().toggleClass("hidden");
			$(".wizard-test-database-connection").parent().toggleClass("hidden");
			let path = $(".wizard-suggested-path").html();
			$("#form-dbPath").focus();
			$("#form-dbPath").val(path);
			$("#form-dbPath").focusout();
			$("#verify-dbPath").text(path);
			$("#verify-driver").text($(this).val());
			$("#form-dbHost").focus();
			message("Using MySQLi","Database Path becomes path for logs etc.. (Still configurable)",activeInfo.settings.notifications.position,"#FFF","info","10000");
		});
		$(document).on("click", ".copy-dbPath", function () {
			let path = $(this).attr("data-clipboard-text");
			$("#form-dbPath").focus();
			$("#form-dbPath").val(path);
			$("#form-dbPath").focusout();
			$("#verify-dbPath").text(path);
			$("#form-dbName").focus();
		});
    })();
</script>
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-xl-3 col-lg-4 col-md-4 col-12">
            <h4 class="page-title">Londerland Setup Wizard</h4>
        </div>
        <!-- /.col-xl-12 -->
    </div>
    <!--.row-->
    <div class="row">
        <div class="col-md-12">
            <div class="white-box">
                <h3 class="box-title m-b-0" lang="en">Admin Creation</h3>
                <div class="wizard" id="adminValidator">
                    <ul class="wizard-steps list-unstyled" role="tablist">
                        <li role="tab">
                            <h4><span><i class="ti-direction"></i></span><item lang="en">Install Type</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-user"></i></span><item lang="en">Admin Info</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-key"></i></span><item lang="en">Security</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-server"></i></span><item lang="en">Database</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-check"></i></span><item lang="en">Verify</item></h4>
                        </li>
                    </ul>
                    <form class="form-horizontal" id="validation" name="validation" onsubmit="return false;">
                        <div class="wizard-content">
                            <div class="wizard-pane active" role="tabpanel">
	                            <div class="card card-info">
                                    <div class="card-header">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Notice</span>
                                        <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="card-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="card-wrapper collapse show" aria-expanded="true">
                                        <div class="card-body">
                                            <p lang="en">Personal has everything unlocked - no restrictions</p>
                                            <p lang="en">Business has Media items hidden [Plex, Emby etc...]</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="license" lang="en">Install Type</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-direction"></i></div>
                                        <select name="license" class="form-control wizardInput" id="form-license">
                                            <option lang="en">Choose License</option>
                                            <option lang="en" value="personal">Personal</option>
                                            <option lang="en" value="business">Business</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Notice</span>
                                        <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="card-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="card-wrapper collapse show" aria-expanded="true">
                                        <div class="card-body">
                                            <p lang="en">If using Plex or Emby - It is suggested that you use the username and email of the Admin account.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="username" lang="en">Username</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-user"></i></div>
                                        <input type="text" class="form-control wizardInput" name="username" id="form-username">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="email" lang="en">Email</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-email"></i></div>
                                        <input type="text" class="form-control wizardInput" name="email" id="form-email">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="passwrod" lang="en">Password</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-lock"></i></div>
                                        <input type="password" class="form-control wizardInput" name="password" id="form-password">
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Notice</span>
                                        <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="card-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="card-wrapper collapse show" aria-expanded="true">
                                        <div class="card-body">
                                            <p lang="en">The Hash Key will be used to decrypt all passwords etc... on the server. [User-Generated]</p>
                                            <p lang="en">The Registration Password will lockout the registration field with this password. [User-Generated]</p>
                                            <p lang="en">The API Key will be used for all calls to londerland for the UI. [Auto-Generated]</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="key" lang="en">Hash Key</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-key"></i></div>
                                        <input type="password" class="form-control wizardInput" name="hashKey" id="form-hashKey">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="key" lang="en">Registration Password</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-key"></i></div>
                                        <input type="password" class="form-control wizardInput" name="registrationPassword" id="form-registrationPassword">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="key" lang="en">API Key</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-key"></i></div>
                                        <input type="password" class="form-control wizardInput disabled" name="api" id="form-api">
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="card card-danger">
                                    <div class="card-header">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Attention</span>
                                        <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="card-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="card-wrapper collapse show" aria-expanded="true">
                                        <div class="card-body">
                                            <p lang="en">The Database will contain sensitive information.  Please place in directory outside of root Web Directory.</p>
                                            <p lang="en">Suggested Directory: <code class="wizard-suggested-path">' . $suggestedDirectory . '</code> <a class="btn default btn-outline clipboard copy-dbPath p-a-5" data-clipboard-text="' . $suggestedDirectory . '" href="javascript:void(0);"><i class="ti-clipboard"></i></a></p>
                                            <p lang="en">Current Directory: <code>' . dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . '</code> <a class="btn default btn-outline clipboard copy-dbPath p-a-5" data-clipboard-text="' . dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . '" href="javascript:void(0);"><i class="ti-clipboard"></i></a></p>
                                            <p lang="en">Parent Directory: <code>' . dirname(__DIR__, 3) . '</code> <a class="btn default btn-outline clipboard copy-dbPath p-a-5" data-clipboard-text="' . dirname(__DIR__, 3) . '" href="javascript:void(0);"><i class="ti-clipboard"></i></a></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
									<label class="form-label" lang="en">Database Driver</label>
									<div class="radio-list">
										<label class="radio-inline p-0">
											<div class="radio radio-info">
												<input type="radio" class="database-driver-selector" name="driver" id="db-driver-sqlite3" value="sqlite3" checked="checked">
												<label for="db-driver-sqlite3">sqlite3</label>
											</div>
										</label>
										<label class="radio-inline  p-0">
											<div class="radio radio-info">
												<input type="radio" class="database-driver-selector" name="driver" id="db-driver-mysqli" value="mysqli" ' . $mysqliDisabled . '>
												<label for="db-driver-mysqli">mysqli' . $mysqliLabel . '</label>
											</div>
										</label>
									</div>
								</div>
								<div class="form-group hidden">
                                    <label for="dbHost" lang="en">Database Host</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-server"></i></div>
                                        <input type="text" class="form-control wizardInput" name="dbHost" id="form-dbHost" placeholder="host and/or port">
                                    </div>
                                </div>
                                <div class="form-group hidden">
                                    <label for="dbUsername" lang="en">Database Username</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-server"></i></div>
                                        <input type="text" class="form-control wizardInput" name="dbUsername" id="form-dbUsername" placeholder="">
                                    </div>
                                </div>
                                <div class="form-group hidden">
                                    <label for="dbPassword" lang="en">Database Password</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-server"></i></div>
                                        <input type="password" class="form-control wizardInput" name="dbPassword" id="form-dbPassword" placeholder="">
                                    </div>
                                </div>
                                <div class="form-group hidden">
                                    <button class="btn w-100 btn-info wizard-test-database-connection" lang="en">Test Database Connection</button>
                                </div>
                                <div class="form-group">
                                    <label for="dbName" lang="en">Database Name</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-server"></i></div>
                                        <input type="text" class="form-control wizardInput" name="dbName" id="form-dbName" placeholder="orgDBname">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="dbPath" lang="en">Database Location</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="ti-server"></i></div>
                                        <input type="text" class="form-control wizardInput" name="dbPath" id="form-dbPath" placeholder="Enter path or copy from above">
                                        <button class="btn btn-info testPath" lang="en" type="button">Test / Create Path</button>
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">License:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-license"></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Username:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-username"></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Email:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-email"></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Password:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-password"></span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Database Location:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-dbPath">  </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Database Name:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-dbName">  </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Hash Key:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-hashKey">pass</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Registration Password:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-registrationPassword">pass</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">API Key:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-api">pass</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Database Driver:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-driver">sqlite3</p>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Database Host:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-dbHost">Not used...</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Database Username:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static" id="verify-dbUsername">Not used...</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label col-lg-3" lang="en">Database Password:</label>
                                            <div class="col-lg-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-dbPassword">Not used...</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--/row-->
                            </div>
                        </div>
                    </form>
                    <div class="wizard-buttons d-flex justify-content-between">
                        <a class="btn btn-secondary wizard-back" href="#" lang="en">Back</a>
                        <a class="btn btn-info wizard-next" href="#" lang="en">Next</a>
                        <a class="btn btn-success wizard-finish d-none" href="#" lang="en">Finish</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!--./row-->
</div>
<!-- /.container-fluid -->
';
}