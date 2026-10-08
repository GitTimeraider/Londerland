<?php
$GLOBALS['londerlandPages'][] = 'settings_user_manage_users';
function get_page_settings_user_manage_users($Londerland)
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
	$(function() {
		let groups = {};
		const lockedValues = {0: window.lang.translate("No"), 1: window.lang.translate("Yes")};
		const userError = function(title) {
			return function(xhr) {
				message(title, xhr.responseJSON.response.message, activeInfo.settings.notifications.position, "#FFF", "error", "10000");
				console.error("Londerland Function: API Connection Failed");
			};
		};
		window.refreshManageUsers = function() {
			$.ajax({ url: "api/v2/users?includeGroups", dataType: "json" }).done(function(response) {
				groups = {};
				$.each(response.response.data.groups, function(i, group) {
					groups[group.group_id] = group.group;
				});
				window.manageUsersTable.setData(response.response.data.users);
			});
		};
		window.manageUsersTable = new Tabulator("#manage-users-table", {
			index: "id",
			layout: "fitColumns",
			responsiveLayout: "hide",
			placeholder: window.lang.translate("Loading... or Not found"),
			pagination: true,
			paginationSize: parseInt($("#pageLength").val(), 10),
			paginationButtonCount: 5,
			columns: [
				{ title: window.lang.translate("Avatar"), field: "image", width: 70, hozAlign: "center", headerSort: false, responsive: 2,
					formatter: function(cell) {
						return $("<img alt=\"user-img\" class=\"rounded-circle\" width=\"45\" style=\"cursor: pointer\">").attr({ src: cell.getValue(), title: window.lang.translate("Change avatar") })[0];
					},
					cellClick: function(e, cell) {
						changeUserAvatar(cell.getRow().getData().id, cell.getValue(), function(image) {
							cell.setValue(image, true);
						});
					}
				},
				{ title: window.lang.translate("Username"), field: "username", editor: "input", validator: "required", minWidth: 150 },
				{ title: window.lang.translate("Email"), field: "email", editor: "input", validator: "required", minWidth: 200 },
				{ title: window.lang.translate("Date Registered"), field: "register_date", responsive: 2,
					formatter: function(cell) {
						let value = cell.getValue();
						if (value && typeof value == "object") {
							value = value.date;
						}
						return value ? moment(value).format("ll") + " " + moment(value).format("LT") : "";
					}
				},
				{ title: window.lang.translate("Group"), field: "group_id", editor: "list",
					editorParams: function() {
						return { values: groups };
					},
					formatter: function(cell) {
						return groups[cell.getValue()] ?? cell.getValue();
					}
				},
				{ title: window.lang.translate("Locked"), field: "locked", width: 100, editor: "list", editorParams: { values: lockedValues },
					formatter: function(cell) {
						const value = cell.getValue();
						return (value == 0 || value == null || value == "" || value == " ") ? lockedValues[0] : lockedValues[1];
					}
				},
				{ title: window.lang.translate("Password"), field: "password", width: 110, hozAlign: "center", headerSort: false,
					editor: "input", editorParams: { elementAttributes: { type: "password", placeholder: "Enter new password" } },
					formatter: function() {
						return "<i class=\"mdi mdi-account-key\"></i>";
					}
				},
				{ title: window.lang.translate("Action"), width: 90, hozAlign: "center", headerSort: false,
					formatter: function() {
						return "<button type=\"button\" class=\"btn btn-sm btn-danger\"><i class=\"fa fa-trash\"></i></button>";
					},
					cellClick: function(e, cell) {
						const user = cell.getRow().getData();
						if (user.protected) {
							return;
						}
						Swal.fire({
							title: window.lang.translate("Delete ") + user.username + "?",
							icon: "warning",
							showCancelButton: true,
							cancelButtonText: window.lang.translate("No"),
							confirmButtonText: window.lang.translate("Yes"),
							customClass: { popup: "bg-org" },
							confirmButtonColor: "#DD6B55"
						}).then(function(result) {
							if (result.isConfirmed) {
								londerlandAPI2("DELETE", "api/v2/users/" + user.id, null, true).done(function() {
									window.refreshManageUsers();
									message("User Deleted", "", activeInfo.settings.notifications.position, "#FFF", "success", "5000");
								}).fail(userError("User Deleted Error"));
							}
						});
					}
				}
			]
		});
		window.manageUsersTable.on("tableBuilt", window.refreshManageUsers);
		window.manageUsersTable.on("cellEdited", function(cell) {
			const field = cell.getField();
			const value = cell.getValue();
			if (field === "password") {
				cell.setValue("", true);
				if (value === "" || value == null) {
					return;
				}
			}
			const id = cell.getRow().getData().id;
			if (typeof id == "undefined") {
				alert("Could not get ID");
				return;
			}
			londerlandAPI2("PUT", "api/v2/users/" + id, { [field]: value }, true).done(function(data) {
				message("User Updated", data.response.message, activeInfo.settings.notifications.position, "#FFF", "success", "5000");
			}).fail(function(xhr) {
				cell.restoreOldValue();
				userError("User Error")(xhr);
			});
		});
		$("#pageLength").on("change", function() {
			window.manageUsersTable.setPageSize(parseInt(this.value, 10));
		});
	});
</script>
<div class="card bg-org card-info">
	<div class="card-header">
		<span lang="en">MANAGE USERS</span>
		<button type="button" class="btn btn-info btn-circle float-end popup-with-form" href="#new-user-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
		<div id="pageDiv" class="d-none d-sm-block">
			<div class="item-pager-panel float-end me-2">
					<select id="pageLength" class="form-select">
						<option>5</option>
						<option selected="">10</option>
						<option>15</option>
						<option>30</option>
						<option>60</option>
						<option>180</option>
					</select>
			</div>
		</div>
	</div>
	<div id="manage-users-table"></div>
	<div class="clearfix"></div>
</div>
<form id="new-user-form" class="mfp-hide white-popup-block mfp-with-anim">
	<h1 lang="en">Add New User</h1>
	<fieldset style="border:0;">
		<div class="form-group">
			<label class="form-label" for="new-user-form-inputUsername" lang="en">Username</label>
			<input type="text" class="form-control" id="new-user-form-inputUsername" name="username" required="" autofocus>
		</div>
		<div class="form-group">
			<label class="form-label" for="new-user-form-inputEmail" lang="en">Email</label>
			<input type="email" class="form-control" id="new-user-form-inputEmail" name="email"  required="">
		</div>
		<div class="form-group">
			<label class="form-label" for="new-user-form-inputPassword" lang="en">Password</label>
			<input type="password" class="form-control" id="new-user-form-inputPassword" name="password"  required="">
		</div>
	</fieldset>
	<button class="btn btn-sm btn-info rounded-pill float-end border-0 addNewUser" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add User</span></button>
	<div class="clearfix"></div>
</form>
<form id="edit-user-form" class="mfp-hide white-popup-block mfp-with-anim">
	<input type="hidden" name="id" value="">
	<h1 lang="en">Edit User</h1>
	<fieldset style="border:0;">
		<div class="form-group">
			<label class="form-label" for="edit-user-form-inputUsername" lang="en">Username</label>
			<input type="text" class="form-control" id="edit-user-form-inputUsername" name="username" required="" autofocus>
		</div>
		<div class="form-group">
			<label class="form-label" for="edit-user-form-inputEmail" lang="en">Email</label>
			<input type="text" class="form-control" id="edit-user-form-inputEmail" name="email" required="" autofocus>
		</div>
		<div class="form-group">
			<label class="form-label" for="edit-user-form-inputPassword" lang="en">Password</label>
			<input type="password" class="form-control" id="edit-user-form-inputPassword" name="password"  required="">
		</div>
		<div class="form-group">
			<label class="form-label" for="edit-user-form-inputPassword2" lang="en">Password Again</label>
			<input type="password" class="form-control" id="edit-user-form-inputPassword2" name="password2"  required="">
		</div>
	</fieldset>
	<button class="btn btn-sm btn-info rounded-pill float-end border-0 editUserAdmin" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit User</span></button>
	<div class="clearfix"></div>
</form>
';
}