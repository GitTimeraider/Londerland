/*jslint browser: true*/
/*global $, jQuery, alert*/
var idleTime = 0;
var hasCookie = false;
var loginAttempts = 0;
$(document).ajaxComplete(function () {
    pageLoad();
});
$(document).ready(function () {
    pageLoad();
    $(document).on('click', '.clipboard, #internal-clipboard', function() {
        let text = $(this).attr('data-clipboard-text') || '';
        copyToClipboard(text).then(function() {
            message('Clipboard',text,activeInfo.settings.notifications.position,'#FFF','info','5000');
        });
    });
    "use strict";
    var body = $("body");

    $(window).scroll(function(){
        if ($(this).scrollTop() > 100) {
            $('#scroll').fadeIn();
        } else {
            $('#scroll').fadeOut();
        }
    });
    $('#scroll').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 600);
        return false;
    });

    $(function () {
        //$("#preloader").fadeOut();
        var set = function () {
            var topOffset = 40,
                width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width,
                height = ((window.innerHeight > 0) ? window.innerHeight : this.screen.height) - 1;
            if (width < 768) {
                $('div.navbar-collapse').addClass('collapse');
                topOffset = 100; /* 2-row-menu */
            } else {
                $('div.navbar-collapse').removeClass('collapse');
            }

            /* ===== This is for resizing window ===== */

            if (width < 768) {
                body.addClass('content-wrapper');
                $(".sidebar-nav").css("overflow-x", "visible").parent().css("overflow", "visible");
            } else {
                body.removeClass('content-wrapper');
            }

            height = height - topOffset;
            if (height < 1) {
                height = 1;
            }
            if (height > topOffset) {
                $("#page-wrapper").css("min-height", (height) + "px");
                //$("#page-wrapper").css("max-height", (height) + "px");

            }
        },
        url = window.location,
        element = $('ul.nav a').filter(function () {
            return this.href === url || url.href.indexOf(this.href) === 0;
        }).addClass('activez').parent().parent().addClass('ok').parent();
        if (element.is('li')) {
            element.addClass('activezo');
        }
        $(window).ready(set);
        $(window).bind("resize", set);
    });
    body.trigger("resize");
    //Increment the idle time counter every minute.
    var idleInterval = setInterval(timerIncrement, 60000); // 1 minute
    hasCookie = (getCookie('londerlandToken')) ? true : false;
    //Zero the idle timer on mouse movement.
    $(this).mousemove(function (e) {
        idleTime = 0;
    });
    $(this).keypress(function (e) {
        idleTime = 0;
    });
    myLazyLoad = new LazyLoad({
        elements_selector: ".lazyload"
    });
    /* ===== Collapsible Panels JS ===== */
    (function ($, window, document) {
        var panelSelector = '[data-perform="card-collapse"]',
            panelRemover = '[data-perform="card-dismiss"]';
        $(panelSelector).each(function () {
            var collapseOpts = {
                    toggle: false
                },
                parent = $(this).closest('.card'),
                wrapper = parent.find('.card-wrapper'),
                child = $(this).children('i');
            if (!wrapper.length) {
                wrapper = parent.children('.card-header').nextAll().wrapAll('<div/>').parent().addClass('card-wrapper');
                collapseOpts = {};
            }
            wrapper.collapse(collapseOpts).on('hide.bs.collapse', function () {
                child.removeClass('ti-minus').addClass('ti-plus');
            }).on('show.bs.collapse', function () {
                child.removeClass('ti-plus').addClass('ti-minus');
            });
        });

        /* ===== Collapse Panels ===== */

        $(document).on('click', panelSelector, function (e) {
            e.preventDefault();
            var parent = $(this).closest('.card'),
                wrapper = parent.find('.card-wrapper');
                $(this).children('i').toggleClass('ti-plus').toggleClass('ti-minus');
            wrapper.collapse('toggle');
        });

        /* ===== Remove Panels ===== */

        $(document).on('click', panelRemover, function (e) {
            e.preventDefault();
            var removeParent = $(this).closest('.card');

            function removeElement() {
                var col = removeParent.parent();
                removeParent.remove();
                col.filter(function () {
                    return ($(this).is('[class*="col-"]') && $(this).children('*').length === 0);
                }).remove();
            }
            removeElement();
        });
    }(jQuery, window, document));
});
function pageLoad(){
    "use strict";
    //Start Londerland
    $(function () {
        if($('#preloader:visible').length == 1){
            $("#preloader").fadeOut();
        }
        myLazyLoad.update();
    });
	customScrollbars('#page-wrapper', 'move');
	customScrollbars('.default-scroller', 'scroll');
	customScrollbars('.nav-bar-rtl', 'leave');
	customScrollbars('.inbox-center', 'leave');
	customScrollbars('.mailbox', 'leave');
    /* ===== Tooltip Initialization ===== */

    $(function () {
        if(browserInfo.mobile !== true) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
        /*$('body').tooltip({
            selector: '[data-bs-toggle="tooltip"]'
        });*/
    });

    /* ===== Popover Initialization ===== */

    $(function () {
        $('[data-bs-toggle="popover"]').popover({trigger: "hover",});
    });

    $(function () {
        initSwitches();
    });

    /* ===== Collepsible Toggle ===== */

    $(".collapseble").on("click", function () {
        $(".collapseblebox").fadeToggle(350);
    });


    /* ===== Resize all elements ===== */



    /* ===== Visited ul li ===== */

    /*$('.visited li a').on("click", function (e) {
        $('.visited li').removeClass('active');
        var $parent = $(this).parent();
        if (!$parent.hasClass('active')) {
            $parent.addClass('active');
        }
        e.preventDefault();
    });*/

    /* =================================================================
        Update 1.5
        this is for close icon when navigation open in mobile view
    ================================================================= */


    /* magnific stuff */
    $('.popup-with-form').magnificPopup({
        type: 'inline',
        preloader: true,
        removalDelay: 500,
        showCloseBtn: false,
        // When elemened is focused, some mobile browsers in some cases zoom in
        // It looks not nice, so we disable it:
        callbacks: {
            beforeOpen: function() {
                if($(window).width() < 700) {
                    this.st.focus = false;
                } else {
                    this.st.focus = '#name';
                }
                this.st.mainClass = this.st.el.attr('data-effect');
            },
            beforeClose: function () {
                // Callback available since v0.9.0
                if($.magnificPopup.instance.currItem.inlineElement.find('.rubberBand').length !== 0){
                    if(!$.magnificPopup.instance.currItem.inlineElement.find('.rubberBand').hasClass('hidden')){
                        var magIndex = $.magnificPopup.instance.currItem.index;
                        message('You forgot to save','<a class="mouse" onclick="$(\'.popup-with-form\').magnificPopup(\'open\','+magIndex+')">Would you like to go back?</a>',activeInfo.settings.notifications.position,'#FFF','warning','5000');
                    }
                }
            },
        }
    });
    // Inline popups
    $('.inline-popups').magnificPopup({
      removalDelay: 500, //delay removal by X to allow out-animation
      closeOnBgClick: true,
      //closeOnContentClick: true,
      callbacks: {
        beforeOpen: function() {
           this.st.mainClass = this.st.el.attr('data-effect');
           this.st.focus = '.inline-focus';
       },
       close: function() {
          // Removing the embed stops the trailer
          $('.youtube-div').html('');
        }
      },
      midClick: true // allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source.
    });

}
/* ===== Sidebar ===== */

$('.slimscrollright').css({ height: '100%', 'overflow-y': 'auto' });
$('.slimscrollsidebar').css({ height: '100%', 'overflow-y': 'auto' });
$(".navbar-toggle").on("click", function () {
    $(".navbar-toggle i").toggleClass("ti-menu").addClass("ti-close");
});
/* ===== Login and Recover Password ===== */
$(document).on("click", "#to-recover", function(e) {
    $("#loginform").slideUp();
    $("#recoverform").fadeIn();
});
$(document).on("click", ".to-register", function(e) {
    $("#loginform").slideUp();
    $("#registerForm").removeClass('hidden');
    $("#registerform").fadeIn();
});
$(document).on("click", "#leave-recover", function(e) {
    $("#loginform").slideDown();
    $("#recoverform").fadeOut();
});
$(document).on("click", "#leave-registration", function(e) {
    $("#registerform").fadeOut();
    $("#registerForm").addClass('hidden');
    $("#loginform").slideDown();

});
$(document).on("click", ".show-login", function(e) {
    buildLogin();
});
$(document).on("click", ".depenency-item", function(e) {
    alert($(this).attr('data-name'));
});
// 2FA step: ask for a one-time bypass code; the server writes it to the container log for the admin
$(document).on("click", ".tfa-bypass-request", function(e) {
    e.preventDefault();
    $('#loginform [name=tfaBypassRequest]').val('1');
    $('#tfa-div .login-button').trigger('click');
    $('#loginform [name=tfaBypassRequest]').val('');
});
$(document).on("click", ".login-button", function(e) {
    e.preventDefault;
    var oAuthEntered = $('#oAuth-Input').val();
    var usernameEntered = $('#login-username-Input').val();
    if(oAuthEntered == '' && usernameEntered == ''){
        message('Login Error', ' You need to enter a Username', activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
        $('#login-username-Input').focus();
        return false;
    }
    loginAttempts = loginAttempts + 1;
    $('#login-attempts').val(loginAttempts);
    var check = (local('g','loggingIn'));
    if(check == null) {
        local('s','loggingIn', true);
        $('div.login-box').block({
            message: '<h5><img width="20" src="plugins/images/busy.gif" /> Just a moment...</h4>',
            css: {
                color: '#fff',
                border: '1px solid #2cabe3',
                backgroundColor: '#2cabe3'
            }
        });
        var post = $('#loginform').serializeToJSON();
        londerlandAPI2('POST', 'api/v2/login', post).done(function (data) {
            local('set','message','Welcome|Login Successful|success');
	        local('r','loggingIn');
	        location.reload();
        }).fail(function (xhr) {
            $('div.login-box').unblock({});
            switch (xhr.status){
	            case 401:
					if(xhr.responseJSON.response.message == '2FA Code incorrect'){
						$('div.login-box').unblock({});
						$('#tfa-div').removeClass('hidden');
						$('#loginform [name=tfaCode]').focus();
					}
	            	break;
	            case 403:
		            $('div.login-box').block({
			            message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
			            css: {
				            color: '#fff',
				            border: '1px solid #e91e63',
				            backgroundColor: '#f44336'
			            }
		            });
		            setTimeout(function(){ local('r','loggingIn'); location.reload() }, 10000);
	            	break;
	            case 422:
		            $('div.login-box').unblock({});
		            $('#tfa-div').removeClass('hidden');
		            $('#loginform [name=tfaCode]').focus();
	            	break;
	            default:
		            message('Login Error', 'API Connection Failed', activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
		            console.error("Londerland Function: API Connection Failed");
            }
	        message('Login Error', xhr.responseJSON.response.message, activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
	        console.error("Londerland Function: " + xhr.responseJSON.response.message);
            local('r','loggingIn');
        });
    }
});
$(document).on("click", ".unlockButton", function(e) {
    e.preventDefault;
    var post = {
        password:$('#unlockPassword').val()
    };
    if(post == ''){
	    message('Password cannot be blank', '', activeInfo.settings.notifications.position, '#FFF', 'error', '5000');
    	return false;
    }
    londerlandAPI2('POST','api/v2/users/unlock',post).done(function(data) {
        let html = data.response;
        location.reload();
    }).fail(function(xhr) {
	    LonderlandApiError(xhr, 'API Error');
    });
});
$(document).on("click", ".register-button", function(e) {
    e.preventDefault;
    var post = $( '#registerForm' ).serializeToJSON();
    console.log(post)
    londerlandAPI2('POST','api/v2/users/register',post).done(function(data) {
        let html = data.response;
		location.reload();
    }).fail(function(xhr) {
	    LonderlandApiError(xhr, 'API Error');
    });
});
$(document).on("click", ".reset-button", function(e) {
    e.preventDefault;
    var email = $('#recover-input').val();
    if(email !== ''){
		var post = {
	        email:email
        };
	    message('Submitting request...','',activeInfo.settings.notifications.position,'#FFF','info','10000');
        londerlandAPI2('POST','api/v2/users/recover',post).done(function(data) {
            var html = data.response;
            message('Recover Password',html.message,activeInfo.settings.notifications.position,'#FFF','success','10000');
            $('#leave-recover').trigger('click');
        }).fail(function(xhr) {
	        LonderlandApiError(xhr, 'API Error');
        });
    }else{
        message('Recover Error','Enter Email',activeInfo.settings.notifications.position,'#FFF','warning','10000');
    }
});
$(document).on("click", ".open-close", function () {
    $("body").toggleClass("show-sidebar");
});
//EDIT GROUP GET ID
$(document).on("click", ".editGroupButton", function () {
    $('#edit-group-form [name=group]').val($(this).closest('[data-id]').attr("data-group"));
    $('#edit-group-form [name=id]').val($(this).closest('[data-id]').attr("data-id"));
    $('#edit-group-form [name=image]').val($(this).closest('[data-id]').attr("data-image"));
});
//EDIT GROUP
$(document).on("click", ".editGroup", function () {
	var info = $('#edit-group-form').serializeToJSON();
	var callbacks = $.Callbacks();
	if (typeof info.id == 'undefined' || info.id == '') {
		message('Edit Tab Error',' Could not get ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (typeof info.group == 'undefined' || info.group == '') {
		message('Edit Tab Error',' Please set a Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof info.image == 'undefined' || info.image == '') {
		message('Edit Tab Error',' Please set an Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	callbacks.add( buildGroupManagement );
	londerlandAPI2('PUT','api/v2/groups/' + info.id,info,true).done(function(data) {
		try {
			var response = data.response;
			clearSelect('.groupIconImageList, .groupIconIconList');
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		clearForm('#edit-group-form');
		$.magnificPopup.close();
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
//CHANGE DEFAULT GROUP
$(document).on("click", ".changeDefaultGroup", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var callbacks = $.Callbacks();
	callbacks.add( buildGroupManagement );
	londerlandAPI2('PUT','api/v2/groups/' + id, {"default":1},true).done(function(data) {
		try {
			var response = data.response;
			message(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
		}catch(e) {
			londerlandCatchError(e,data);
		}
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
//DELETE GROUP
$(document).on("click", ".deleteUserGroup", function () {
	var el = $(this);
    Swal.fire({
        title: window.lang.translate('Delete ')+el.closest('[data-id]').attr("data-group")+'?',
        icon: "warning",
        showCancelButton: true,
        cancelButtonText: window.lang.translate('No'),
        confirmButtonText: window.lang.translate('Yes'),
        confirmButtonColor: "#DD6B55"
    }).then(function(result) {
        let willDelete = result.isConfirmed;
        if (willDelete) {
	        var id = el.closest('[data-id]').attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildGroupManagement );
	        londerlandAPI2('DELETE','api/v2/groups/' + id, null,true).done(function(data) {
		        try {
			        message('Group Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
			        if(callbacks){ callbacks.fire(); }
		        }catch(e) {
			        londerlandCatchError(e,data);
		        }
	        }).fail(function(xhr) {
		        LonderlandApiError(xhr, 'API Error');
	        });
        }
    });
});
//ADD GROUP
$(document).on("click", ".addNewGroup", function () {

	var info = $('#new-group-form').serializeToJSON();
	console.log(info);
	if (typeof info.group == 'undefined' || info.group == '') {
		message('New Group Error',' Please set a Group Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof info.image == 'undefined' || info.image == '') {
		message('New Group Error',' Please set a Group Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	var callbacks = $.Callbacks();
	callbacks.add( buildGroupManagement );
	londerlandAPI2('POST','api/v2/groups',info,true).done(function(data) {
		try {
			var response = data.response;
			clearSelect('.groupIconImageList, .groupIconIconList');
			message(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#new-group-form');
			$.magnificPopup.close();
		}catch(e) {
			londerlandCatchError(e,data);
		}
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
// ADD USER
$(document).on("click", ".addNewUser", function () {
	var userInfo = $('#new-user-form').serializeToJSON();
	$.each(userInfo, function(i,v) {
		if(v == ''){
			delete userInfo[i];
		}
	})
	console.log(userInfo)
	var callbacks = $.Callbacks();
	callbacks.add( buildUserManagement );
	londerlandAPI2('POST','api/v2/users', userInfo,true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('User Created',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		clearForm('#new-user-form');
		window.refreshManageUsers();
		$.magnificPopup.close();
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
//EDIT GROUP GET ID
$(document).on("click", ".editUserButton", function () {
    $('#edit-user-form [name=username]').val($(this).closest('[data-id]').attr("data-username"));
    $('#edit-user-form [name=id]').val($(this).closest('[data-id]').attr("data-id"));
    $('#edit-user-form [name=email]').val($(this).closest('[data-id]').attr("data-email"));
});
//EDIT GROUP
$(document).on("click", ".editUserAdmin", function () {
	var userInfo = $('#edit-user-form').serializeToJSON();
	$.each(userInfo, function(i,v) {
		if(v == ''){
			delete userInfo[i];
		}
	})
	if (typeof userInfo.id == 'undefined' || userInfo.id == '') {
		message('Edit User Error',' Could not get User ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (userInfo.password !== '' && userInfo.password !== userInfo.password2){
		message('Edit User Error',' Passwords do not match!',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	var callbacks = $.Callbacks();
	callbacks.add( buildUserManagement );
	londerlandAPI2('PUT','api/v2/users/' + userInfo.id, userInfo,true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('User Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		clearForm('#edit-user-form');
		$.magnificPopup.close();
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
// CHANGE USER GROUP
$(document).on("change", ".userGroupSelect", function () {

	var id = $(this).closest('[data-id]').attr("data-id");
	var groupId = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	callbacks.add( buildUserManagement );
	londerlandAPI2('PUT','api/v2/users/' + id, {"group_id":groupId},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('User Group Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
// DELETE USER
//DELETE GROUP
$(document).on("click", ".deleteUser", function () {
    var user = $(this);
    Swal.fire({
        title: window.lang.translate('Delete ')+user.closest('[data-id]').attr("data-username")+'?',
        icon: "warning",
        showCancelButton: true,
        cancelButtonText: window.lang.translate('No'),
        confirmButtonText: window.lang.translate('Yes'),
        confirmButtonColor: "#DD6B55"
    }).then(function(result) {
        let willDelete = result.isConfirmed;
        if (willDelete) {
	        var id = user.closest('[data-id]').attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildUserManagement );
	        londerlandAPI2('DELETE','api/v2/users/' + id, null,true).done(function(data) {
		        message('User Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        LonderlandApiError(xhr, 'User Delete Error');
	        });
        }
    });
});
// CHANGE TAB GROUP MIN
$(document).on("change", ".tabGroupSelectMax", function (event) {
	var id = $(this).closest('[data-id]').attr("data-id");
	var groupID = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"group_id_max":groupID},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Group Max Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Group Error');
	});
});
// CHANGE TAB GROUP MAX
$(document).on("change", ".tabGroupSelectMin", function (event) {
	var id = $(this).closest('[data-id]').attr("data-id");
	var groupID = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"group_id":groupID},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Group Min Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Group Error');
	});
});
// CHANGE TAB CATEGORY
$(document).on("change", ".tabCategorySelect", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var categoryID = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"category_id":categoryID},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Category Error');
	});
});
// CHANGE TAB TYPE
$(document).on("change", ".tabTypeSelect", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var type = $(this).find("option:selected").val();
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"type":type},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Type Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Type Error');
	});
});
// CHANGE ENABLED TAB
$(document).on("change", ".enabledSwitch", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var enabled = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"enabled":enabled},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Enable Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Enable Error');
	});
});
// CHANGE SPLASH TAB
$(document).on("change", ".splashSwitch", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var splash = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"splash":splash},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Splash Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Splash Error');
	});
});
// CHANGE SPLASH TAB
$(document).on("change", ".pingSwitch", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var ping = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"ping":ping},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Ping Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Ping Error');
	});
});
// CHANGE PRELOAD TAB
$(document).on("change", ".preloadSwitch", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var preload = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"preload":preload},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Preload Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Preload Error');
	});
});
// CHANGE ADD TO ADMIN TAB
$(document).on("change", ".addToAdminSwitch", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var data = $(this).prop("checked") ? 1 : 0;
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"add_to_admin":data},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Tab Add To Admin Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Tab Add To Admin Error');
	});
});
// CHANGE DEFAULT TAB
$(document).on("change", ".defaultSwitch", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var callbacks = $.Callbacks();
	londerlandAPI2('PUT','api/v2/tabs/' + id, {"default":1},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Default Tab Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Default Tab Error');
	});
});
//DELETE TAB
$(document).on("click", ".deleteTab", function () {
    var tab = $(this);
    Swal.fire({
        title: window.lang.translate('Delete ') + tab.closest('[data-id]').attr("data-name") + '?',
        icon: "warning",
        showCancelButton: true,
        cancelButtonText: window.lang.translate('No'),
        confirmButtonText: window.lang.translate('Yes'),
        confirmButtonColor: "#DD6B55"
    }).then(function(result) {
        let willDelete = result.isConfirmed;
        if (willDelete) {
	        var id = tab.closest('[data-id]').attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildTabEditor );
	        londerlandAPI2('DELETE','api/v2/tabs/' + id, null,true).done(function(data) {
		        message('Tab Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        LonderlandApiError(xhr, 'Tab Deleted Error');
	        });
        }
    });
});
function convertMsToMinutes(ms){
    if(ms === false || ms === 0 || ms === "0"){
        return 0;
    }else{
        return (ms / 1000) / 60;
    }
}
function convertMinutesToMs(minutes){
    if(minutes === false || minutes === 0 || minutes === "0"){
        return 0;
    }else{
        return (minutes * 1000) * 60;
    }
}
//EDIT TAB
$(document).on("click", ".editTab", function () {
    var originalTabName = $('#originalTabName').html();
    var tabInfo = $('#edit-tab-form').serializeToJSON();
    let tabNameLower = tabInfo.name.toLowerCase();
    let originalTabNameLower = originalTabName.toLowerCase();
    if (typeof tabInfo.id == 'undefined' || tabInfo.id == '') {
        message('Edit Tab Error',' Could not get Tab ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
	    return false;
    }
    if (typeof tabInfo.name == 'undefined' || tabInfo.name == '') {
        message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	    return false;
    }
    if (typeof tabInfo.image == 'undefined' || tabInfo.image == '') {
        message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	    return false;
    }
    if ((typeof tabInfo.url == 'undefined' || tabInfo.url == '') && (typeof tabInfo.url_local == 'undefined' || tabInfo.url_local == '')) {
        message('Edit Tab Error',' Please set a Tab URL or Local URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
	    return false;
    }
    if(checkIfTabNameExists(tabInfo.name) && originalTabNameLower !== tabNameLower){
        message('Edit Tab Error',' Tab name already used',activeInfo.settings.notifications.position,'#FFF','warning','5000');
        return false;
    }
    if(tabInfo.timeout_ms !== '' || typeof tabInfo.timeout_ms !== 'undefined'){
    	tabInfo.timeout_ms = convertMinutesToMs(tabInfo.timeout_ms);
    }
    if(tabInfo.id !== '' && tabInfo.tabName !== '' && tabInfo.tabImage !== ''){
	    var callbacks = $.Callbacks();
	    callbacks.add( buildTabEditor );
	    londerlandAPI2('PUT','api/v2/tabs/' + tabInfo.id,tabInfo,true).done(function(data) {
		    try {
			    var response = data.response;
		    }catch(e) {
			    londerlandCatchError(e,data);
		    }
		    message('Tab Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		    if(callbacks){ callbacks.fire(); }
		    clearForm('#edit-tab-form');
		    $.magnificPopup.close();
	    }).fail(function(xhr) {
		    LonderlandApiError(xhr, 'Tab Error');
	    });
    }
});
//ADD NEW TAB
$(document).on("click", ".addNewTab", function () {
	var tabInfo = $('#new-tab-form').serializeToJSON();
	tabInfo['order'] = parseInt($('#tabEditorTable').find('tr[data-order]').last().attr('data-order')) + 1;

	if (typeof tabInfo.name == 'undefined' || tabInfo.name == '') {
		message('Edit Tab Error',' Please set a Tab Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof tabInfo.image == 'undefined' || tabInfo.image == '') {
		message('Edit Tab Error',' Please set a Tab Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if ((typeof tabInfo.url == 'undefined' || tabInfo.url == '') && (typeof tabInfo.url_local == 'undefined' || tabInfo.url_local == '')) {
		message('Edit Tab Error',' Please set a Tab URL or Local URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(checkIfTabNameExists(tabInfo.name)){
		message('Edit Tab Error',' Tab name already used',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(tabInfo.timeout_ms !== '' || typeof tabInfo.timeout_ms !== 'undefined'){
		tabInfo.timeout_ms = convertMinutesToMs(tabInfo.timeout_ms);
	}
    if(tabInfo.order !== '' && tabInfo.name !== '' && (tabInfo.url !== '' || tabInfo.url_local !== '') && tabInfo.image !== '' ){
	    var callbacks = $.Callbacks();
	    callbacks.add( buildTabEditor );
	    londerlandAPI2('POST','api/v2/tabs',tabInfo,true).done(function(data) {
		    try {
			    var response = data.response;
			    clearSelect('.tabIconImageList, .tabIconIconList');
		    }catch(e) {
			    londerlandCatchError(e,data);
		    }
		    message('Tab Created',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		    if(callbacks){ callbacks.fire(); }
		    clearForm('#new-tab-form');
		    $.magnificPopup.close();
	    }).fail(function(xhr) {
		    LonderlandApiError(xhr, 'Tab Error');
	    });
    }
});
//ADD NEW CATEGORY
$(document).on("click", ".addNewCategory", function () {
    var categoryInfo = $('#new-category-form').serializeToJSON();
	categoryInfo['order'] = parseInt($('#categoryEditorTable').find('tr[data-order]').last().attr('data-order')) + 1;

	if (typeof categoryInfo.category == 'undefined' || categoryInfo.category == '') {
		message('Edit Tab Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof categoryInfo.image == 'undefined' || categoryInfo.image == '') {
		message('Edit Tab Error',' Please set a Category Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(categoryInfo.category !== '' && categoryInfo.image !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildCategoryEditor );
		londerlandAPI2('POST','api/v2/categories',categoryInfo,true).done(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				londerlandCatchError(e,data);
			}
			message('Category Added',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#new-category-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			LonderlandApiError(xhr, 'Category Error');
		});
	}
});
//DELETE CATEGORY
$(document).on("click", ".deleteCategory", function () {
    var category = $(this);
    Swal.fire({
        title: window.lang.translate('Delete ')+category.closest('[data-id]').attr("data-name")+'?',
        icon: "warning",
        showCancelButton: true,
        cancelButtonText: window.lang.translate('No'),
        confirmButtonText: window.lang.translate('Yes'),
        confirmButtonColor: "#DD6B55"
    }).then(function(result) {
        let willDelete = result.isConfirmed;
        if (willDelete) {
	        var id = category.closest('[data-id]').attr("data-id");
	        var callbacks = $.Callbacks();
	        callbacks.add( buildCategoryEditor );
	        londerlandAPI2('DELETE','api/v2/categories/' + id, null,true).done(function(data) {
		        message('Category Deleted','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        LonderlandApiError(xhr, 'Category Deleted Error');
	        });
        }
    });
});
//EDIT CATEGORY GET ID
$(document).on("click", ".editCategoryButton", function () {
    $('#edit-category-form [name=category]').val($(this).closest('[data-id]').attr("data-name"));
    $('#edit-category-form [name=image]').val($(this).closest('[data-id]').attr("data-image"));
    $('#edit-category-form [name=id]').val($(this).closest('[data-id]').attr("data-id"));
});
//EDIT CATEGORY
$(document).on("click", ".editCategory", function () {
	var categoryInfo = $('#edit-category-form').serializeToJSON();
	if (typeof categoryInfo.id == 'undefined' || categoryInfo.id == '') {
		message('Edit Tab Error',' Could not get Category ID',activeInfo.settings.notifications.position,'#FFF','error','5000');
		return false;
	}
	if (typeof categoryInfo.category == 'undefined' || categoryInfo.category == '') {
		message('Edit Tab Error',' Please set a Category Name',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if (typeof categoryInfo.image == 'undefined' || categoryInfo.image == '') {
		message('Edit Tab Error',' Please set a Category Image',activeInfo.settings.notifications.position,'#FFF','warning','5000');
		return false;
	}
	if(categoryInfo.id !== '' && categoryInfo.category !== '' && categoryInfo.image !== ''){
		var callbacks = $.Callbacks();
		callbacks.add( buildCategoryEditor );
		londerlandAPI2('PUT','api/v2/categories/' + categoryInfo.id,categoryInfo,true).done(function(data) {
			try {
				var response = data.response;
				console.log(response);
			}catch(e) {
				londerlandCatchError(e,data);
			}
			message('Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			clearForm('#edit-category-form');
			$.magnificPopup.close();
		}).fail(function(xhr) {
			LonderlandApiError(xhr, 'Category Error');
		});
	}
});
//CHANGE DEFAULT CATEGORY
$(document).on("click", ".changeDefaultCategory", function () {
	var id = $(this).closest('[data-id]').attr("data-id");
	var callbacks = $.Callbacks();
	callbacks.add( buildCategoryEditor );
	londerlandAPI2('PUT','api/v2/categories/' + id, {"default":1},true).done(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			londerlandCatchError(e,data);
		}
		message('Default Category Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Default Cateogry Error');
	});
});
// CHANGE CUSTOMIZE Options and CSS Save
$(document).on("click", ".saveCss", function () {
    $('.cssTextarea').val(cssEditor.getValue()).trigger('change');
});
$(document).on("click", ".saveThemeCss", function () {
    $('.cssThemeTextarea').val(cssThemeEditor.getValue()).trigger('change');
});
$(document).on("click", ".saveJava", function () {
    $('.javaTextarea').val(javaEditor.getValue()).trigger('change');
});
$(document).on("click", ".saveThemeJava", function () {
    $('.javaThemeTextarea').val(javaThemeEditor.getValue()).trigger('change');
});

$(document).on('change keydown', '.addFormTick :input', function(e) {
    $(this).attr('data-changed', true);
    $(this).closest('.form-group').addClass('has-success');
    var formID = $(this).closest('form').attr('id');
	$('#'+formID+'-save').removeClass('hidden');
	$('#'+formID+'-reset').removeClass('hidden');
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val();
    }
    if($(this).hasClass('themeChanger')){
        londerlandAPI2('GET','api/v2/themes').done(function(data) {
            try {
                let response = data.response;
                let path = response.data[value]['path'];
                changeTheme(path + '/' + value);
                // Built-in themes are made for either the Light or the Dark style: switch it along
                let style = response.data[value]['style'];
                let $style = $('.styleChanger');
                if (style && $style.length && $style.val() !== style) {
                    $style.val(style).trigger('change');
                }
            }catch(e) {
                londerlandCatchError(e,data);
            }
        }).fail(function(xhr) {
            LonderlandApiError(xhr, 'Theme Preview Error');
        });

    }
    if($(this).hasClass('styleChanger')){
        changeStyle(value);
    }
    if($(this).hasClass('notifyChanger')){
        activeInfo.settings.notifications.backbone = value;
        defineNotification();
    }
    if($(this).hasClass('notifyPositionChanger')){
        activeInfo.settings.notifications.position = value;
    }
    if($(this).hasClass('authDebug')){
        activeInfo.settings.misc.authDebug = value;
    }
});

// Mark the form as changed when a switch is toggled
$(document).on('click', '.addFormTick .js-switch', function(e) {
    var checkbox = this;
    setTimeout(function() {
        $(checkbox).attr('data-changed', true);
        $(checkbox).closest('.form-group').addClass('has-success');
        var formID = $(checkbox).closest('form').attr('id');
        $('#'+formID+'-save').removeClass('hidden');
        $('#'+formID+'-reset').removeClass('hidden');
    }, 100);
});
//DELETE IMAGE
$(document).on("click", ".deleteImage", function () {
    var image = $(this);
    Swal.fire({
        title: window.lang.translate('Delete ')+image.attr("data-image-name")+'?',
        icon: "warning",
        showCancelButton: true,
        cancelButtonText: window.lang.translate('No'),
        confirmButtonText: window.lang.translate('Yes'),
        confirmButtonColor: "#DD6B55"
    }).then(function(result) {
        let willDelete = result.isConfirmed;
        if (willDelete) {
            var post = {
                api:'api/v2/image/' + image.attr("data-image-name-ext"),
                messageTitle:'',
                messageBody:window.lang.translate('Deleted Image')+': '+image.attr("data-image-name"),
                error:'Londerland Function: User API Connection Failed'
            };
            var callbacks = $.Callbacks();
            callbacks.add( buildImageManagerView );
	        londerlandAPI2('DELETE',post.api,'',true).done(function(data) {
		        try {
			        var response = data.response;
		        }catch(e) {
			        londerlandCatchError(e,data);
		        }
		        message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		        if(callbacks){ callbacks.fire(); }
	        }).fail(function(xhr) {
		        LonderlandApiError(xhr, 'Image Error');
	        });
        }
    });
});
// RELOAD Page
$(document).on("click", ".reload", function () {
    location.reload();
});
// ENABLE PLUGIN
$(document).on('click', '.enablePlugin', function() {
	ajaxloader(".content-wrap","in");
	let pluginConfigValue = $(this).attr('data-config-name');
	let callbacks = $.Callbacks();
	callbacks.add( ajaxloader );
	let data = {};
	data[pluginConfigValue] = 'true';
	londerlandAPI2('PUT','api/v2/config', data,true).done(function(data) {
		try {
			message('Plugin Enabled','',activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
			buildPlugins('disabled');
			//buildPlugins('enabled');
		}catch(e) {
			londerlandCatchError(e,data);
		}
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'Plugin Error');
		ajaxloader();
	});
});
// DISABLE PLUGIN
$(document).on('click', '.disablePlugin', function() {
    var plugin = $(this);
    Swal.fire({
        title: window.lang.translate('Disable')+' '+plugin.attr("data-plugin-name")+'?',
        icon: "warning",
        showCancelButton: true,
        cancelButtonText: window.lang.translate('No'),
        confirmButtonText: window.lang.translate('Yes'),
        confirmButtonColor: "#DD6B55"
    }).then(function(result) {
        let willDelete = result.isConfirmed;
        if (willDelete) {
	        ajaxloader(".content-wrap","in");
			let pluginConfigValue = plugin.attr('data-config-name');
	        var callbacks = $.Callbacks();
	        callbacks.add( ajaxloader );
	        var data = {};
	        data[pluginConfigValue] = 'false';
	        londerlandAPI2('PUT','api/v2/config', data,true).done(function(data) {
		        try {
			        message('Plugin Disabled','',activeInfo.settings.notifications.position,"#FFF","success","5000");
			        if(callbacks){ callbacks.fire(); }
			        buildPlugins('enabled');
		        }catch(e) {
			        londerlandCatchError(e,data);
		        }
	        }).fail(function(xhr) {
		        LonderlandApiError(xhr, 'Plugin Error');
		        ajaxloader();
	        });
        }
    });
});
// AUTH BACKEND HIDE SHOW
$(document).on('change', '#authSelect, #authBackendSelect', function(e) {
    changeAuth();
});
$(document).on('change', '#plexMachineSelector', function(e) {
	let selector = $(this).attr('data-selector');
	$(selector).val($(this).val());
	$(selector).change();
	messageSingle('Machine ID selected','Please save...',activeInfo.settings.notifications.position,'#FFF','success','5000');
});
$(document).on("click", ".closeErrorPage", function () {
    $('.error-page').html('');
    $('.error-page').fadeOut();
});
// test Location
$(document).on("click", ".testPath", function () {
    var path = $("#form-dbPath").val();
    if (typeof path == 'undefined' || path == '') {
        message('Path Error',' Please enter a path for DB',activeInfo.settings.notifications.position,'#FFF','warning','10000');
    }else{
        londerlandAPI2('POST','api/v2/test/path',{path:path}).done(function(data) {
            var html = data.response;
            message('Path',' Path is good to go',activeInfo.settings.notifications.position,'#FFF','success','10000');
        }).fail(function(xhr) {
	        LonderlandApiError(xhr, 'API Error');
        });
    }
});
// recent filter
// request search filter
//playlist filter
// refresh cache image
// open tab code
$(document).on("click", ".openTab", function(e) {
    if($(this).attr("data-open-tab") === "true") {
        var tabName = $(this).attr("data-tab-name");
        var container = $("#container-"+tabName);
        var activeFrame = container.children('iframe');
        if(activeFrame.length === 1){
            $('#menu-'+tabName+' a').trigger("click");
            activeFrame.attr("src", $(this).attr("data-url"));
        }else{
            container.attr("data-url", $(this).attr("data-url"));
            $('#menu-'+tabName+' a').trigger("click");
        }
    }else{
        var source = $(this).attr("data-url");
        window.open(source, '_blank');
    }
    $.magnificPopup.close();
});
//request click
// metadata start
// sab play/resume
// test tab
$(document).on("click", ".testTab", function () {
    var input = $('#new-tab-form-inputURLNew');
    if(input.val() == ''){
        message('','Please enter a URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(input.val() !== ''){
        var post = {
            url:input.val()
        };
        londerlandAPI2('POST','api/v2/test/iframe',post).done(function(data) {
            let html = data.response;
            $('.tabTestMessage.alert-success').removeClass('hidden');
            $('.tabTestMessage.alert-danger').addClass('hidden');
	        setTimeout(function(){
		        $('.tabTestMessage.alert-success').addClass('hidden');
	        	}, 5000);
        }).fail(function(xhr) {
	        LonderlandApiError(xhr, 'API Error');
	        $('.tabTestMessage.alert-danger').removeClass('hidden');
	        $('.tabTestMessage.alert-success').addClass('hidden');
	        setTimeout(function(){

		        $('.tabTestMessage.alert-danger').addClass('hidden');
	        }, 5000);
        });
    }
});
$(document).on("click", ".testEditTab", function () {
    var input = $('#edit-tab-form-inputURL');
    if(input.val() == ''){
        message('','Please enter a URL',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
    if(input.val() !== ''){
        var post = {
            url:input.val()
        };
	    message('Checking URL now...','',activeInfo.settings.notifications.position,'#FFF','info','5000');
        londerlandAPI2('POST','api/v2/test/iframe',post).done(function(data) {
            let html = data.response;
            $('.tabEditTestMessage.alert-success').removeClass('hidden');
            $('.tabEditTestMessage.alert-danger').addClass('hidden');
	        setTimeout(function(){
		        $('.tabEditTestMessage.alert-success').addClass('hidden');
	        }, 5000);
        }).fail(function(xhr) {
	        LonderlandApiError(xhr, 'API Error');
	        $('.tabEditTestMessage.alert-danger').removeClass('hidden');
	        $('.tabEditTestMessage.alert-success').addClass('hidden');
	        setTimeout(function(){
		        $('.tabEditTestMessage.alert-danger').addClass('hidden');
	        }, 5000);
        });
    }
});
// new api key
$(document).on("click", ".newAPIKey", function () {
	let newCode = generateCode();
    $('#settings-main-form [name=londerlandAPI]').val(newCode).change().parent().find('.clipboard').attr('data-clipboard-text',newCode);
});
// purge log
$(document).on("click", ".purgeLog", function () {
    let logId = $('.choose-londerland-log option:selected').attr('data-id');
    if(logId){
	    let post = {
		    api:'api/v2/log/' + logId,
		    messageTitle:'',
		    messageBody:window.lang.translate('Deleted Log'),
		    error:'Londerland Function: User API Connection Failed'
	    };
	    londerlandAPI2('DELETE',post.api,'',true).done(function(data) {
		    loadSettingsPage2('api/v2/page/settings_settings_logs','#settings-settings-logs','Log Viewer');
		    try {
			    let response = data.response;
			    message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		    }catch(e) {
			    londerlandCatchError(e,data);
		    }
	    }).fail(function(xhr) {
		    LonderlandApiError(xhr, 'API Error');
	    });
    }else{
	    message('','Could not get Log Id',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
});
$(document).on("click", ".delete-backup", function () {
	$('#settings-settings-backup').block({
		message: '<p style="margin:0;padding:8px;font-size:24px;" lang="en">Deleting Backup...</p>',
		css: {
			color: '#fff',
			border: '1px solid #5761a9',
			backgroundColor: '#707cd2'
		}
	});
	let filename = $(this).attr('data-file');
	if(filename !== ''){
		let post = {
			api:'api/v2/backup/' + filename,
			messageTitle:'',
			messageBody:window.lang.translate('Deleted Backup')+': '+filename,
			error:'Londerland Function: Backup API Connection Failed'
		};
		londerlandAPI2('DELETE',post.api,'',true).done(function(data) {
			message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
			getLonderlandBackups();
			$('#settings-settings-backup').unblock();
		}).fail(function(xhr) {
			LonderlandApiError(xhr, 'API Error');
			$('#settings-settings-backup').unblock();
		});
	}
});
//Show Password
$(document).on("click", ".showPassword", function () {
    var toggle = $(this).parent().parent().find('.password-alt');
    if (toggle.attr('type') === "password") {
        toggle.attr('type', 'text');
    } else {
        toggle.attr('type', 'password');
    }
    $(this).find('.passwordToggle').toggleClass('fa-eye').toggleClass('fa-eye-slash');
});
$(document).on("click", ".emailUser", function () {
    var email = $(this).closest('[data-id]').attr('data-email');
    if(activeInfo.plugins["PHPMAILER-enabled"] == true){
        $('.emailModal').click();
        $('#sendEmailToInput').val(email);
    }else{
        message('Email','Plugin not setup',activeInfo.settings.notifications.position,'#FFF','warning','5000');
    }
});
// calendar popups
// request filter
$(document).on('keydown', 'body', function () {
    blockDev();
});
/* ===== Open-Close Right Sidebar ===== */

$(document).on("click", ".right-side-toggle", function () {
    $(".right-sidebar").slideDown(50).toggleClass("shw-rside");
    $(".fxhdr").on("click", function () {
        $("body").toggleClass("fix-header"); /* Fix Header JS */
    });
    $(".fxsdr").on("click", function () {
        $("body").toggleClass("fix-sidebar"); /* Fix Sidebar JS */
    });

    /* ===== Service Panel JS ===== */

    var fxhdr = $('.fxhdr');
    if ($("body").hasClass("fix-header")) {
        fxhdr.attr('checked', true);
    } else {
        fxhdr.attr('checked', false);
    }
});
// Keyboard shortcuts (ignored while typing in a form field)
function shortcut(handler) {
    return function(event) {
        if ($(event.target).is('input, textarea, select, [contenteditable="true"]')) {
            return;
        }
        handler(event);
    };
}
tinykeys.tinykeys(window, {
    'r r': shortcut(function() { reloadCurrentTab() }),
    'c c': shortcut(function(event) { closeCurrentTab(event) }),
    's s': shortcut(function() { openSettings() }),
    'f f': shortcut(function() { toggleFullScreen() }),
    'd d': shortcut(function() { toggleDebug() }),
    'Escape': shortcut(function() {
        $('.splash-screen').removeClass('show').addClass('hidden')
    }),
    'Control+Shift+ArrowUp': shortcut(function(event) {
        event.preventDefault();
        var getCurrentTab = $('.allTabsList a.active').parent();
        var previousTab = getCurrentTab.prev().children();
        previousTab.trigger("click");
        parent.focus();
    }),
    'Control+Shift+ArrowDown': shortcut(function(event) {
        event.preventDefault();
        var getCurrentTab = $('.allTabsList a.active').parent();
        var nextTab = getCurrentTab.next().children();
        nextTab.trigger("click");
    }),
});
$(document).on('keyup', "#debug-input", function(e  ){
	console.log(this);
    if(e.keyCode == 13) {
        orgDebug();
    }
});
// Settings: opening a section with nothing selected yet selects its first sub-tab, so the page is never blank
$(document).on('click', ".sticon", function(){
    var target = $(this).attr('href');
    var menu = $(target).find('.customtab2 > li');
    if(menu.length !== 0){
        // Bootstrap 5 marks the link as active, the server-rendered default (About) marks the list item
        var isActive = $(menu).filter('.active').length > 0 || $(menu).find('a.active').length > 0;
        if(isActive == false){
            // A real click, so Bootstrap shows the pane and the item's onclick loads its content;
            // jQuery's trigger('click') does neither for links
            var el = $(menu).find('a').get(0);
            if(el){
                el.click();
            }
        }
    }
});
// open help modal
$(document).on('click', ".help-modal", function(){
    var type = $(this).attr('data-modal');
    var title = '';
    var body = '';
    //clear modal first
    $('#help-modal-title').html('');
    $('#help-modal-body').html('');
    //alter info
    switch (type) {
        case 'tabs':
            title = 'Tab Help';
            var items = [
                {title:"Name", body:"The text that will be displayed for that certain tab"},
                {title:"Category", body:"Each Tab is assigned a Category, the default is unsorted.  You may create new categories on the Category settings tab"},
                {title:"Group", body:"The lowest Group that will have access to this tab"},
                {title:"Type", body:"Internal is for Londerland pages<br/>iFrame is for all others<br/>New Window is for items to open in a new window"},
                {title:"Default", body:"You can choose one tab to be the first opened tab on page load"},
                {title:"Active", body:"Either mark a tab as active or inactive"},
                {title:"Splash", body:"Toggle this to add the tab to the Splash Page on page load"},
                {title:"Ping", body:"Enable Londerland to ping the status of the local URL of this tab"},
                {title:"Preload", body:"Toggle this tab to loaded in the background on page load"},
            ];
            body = buildAccordion(items);
            break;
        default:
            return null;

    }
    $('#help-modal-title').html(title);
    $('#help-modal-body').html(body);
    $('.help-modal-lg').modal('show');
});
$(document).on('click', ".close-popup", function(){
    $.magnificPopup.close();
});
// open help modal
$(document).on('click', ".copyDebug", function(){
    copyDebug();
    $('#internal-clipboard').trigger('click');
});
// AccountDN change
$(document).on("keyup", "#authBackendHostPrefix-input, #authBackendHostSuffix-input", function () {
    var newDN = $('#authBackendHostPrefix-input').val() + 'TestAcct' + $('#authBackendHostSuffix-input').val();
    $('#accountDN').html(newDN);
});

//IP INFO
$(document).on('click', ".ipInfo", function(){
	londerlandAPI2('GET','api/v2/ip/'+$(this).text()).done(function(data) {
		try {
			let response = data.response.data;
			var region = (typeof response.region == 'undefined') ? ' N/A' : response.region;
			var ip = (typeof response.ip == 'undefined') ? ' N/A' : response.ip;
			var hostname = (typeof response.hostname == 'undefined') ? ' N/A' : response.hostname;
			var loc = (typeof response.loc == 'undefined') ? ' N/A' : response.loc;
			var org = (typeof response.org == 'undefined') ? ' N/A' : response.org;
			var city = (typeof response.city == 'undefined') ? ' N/A' : response.city;
			var country = (typeof response.country == 'undefined') ? ' N/A' : response.country;
			var phone = (typeof response.phone == 'undefined') ? ' N/A' : response.phone;
			var div = '<div class="row">' +
				'<div class="col-xl-12">' +
				'<div class="white-box">' +
				'<h3 class="box-title">'+ip+'</h3>' +
				'<div class="table-responsive inbox-center">' +
				'<table class="table">' +
				'<tbody>' +
				'<tr><td class="text-start">Hostname</td><td class="txt-oflo text-end">'+hostname+'</td></tr>' +
				'<tr><td class="text-start">Location</td><td class="txt-oflo text-end">'+loc+'</td></tr>' +
				'<tr><td class="text-start">Org</td><td class="txt-oflo text-end">'+org+'</td></tr>' +
				'<tr><td class="text-start">City</td><td class="txt-oflo text-end">'+city+'</td></tr>' +
				'<tr><td class="text-start">Country</td><td class="txt-oflo text-end">'+country+'</td></tr>' +
				'<tr><td class="text-start">Phone</td><td class="txt-oflo text-end">'+phone+'</td></tr>' +
				'<tr><td class="text-start">Region</td><td class="txt-oflo text-end">'+region+'</td></tr>' +
				'</tbody>' +
				'</table>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';
			Swal.fire({
				html: createElementFromHTML(div),
				showConfirmButton: false,
				customClass: { popup: 'bg-org' }
			});
		}catch(e) {
			londerlandCatchError(e,data);
		}
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
	});
});
// set active for group list
$(document).on('click', '.allGroupsList', function() {
    //$(this).toggleClass('active');
});
$(document).on('click', '.imageManagerItem', function() {
	createImageSwal($(this));
});

// Trakt image fix
// Plugins settings bind
$(document).on('click', '[id$=-settings-button]', function() {
	let el = $(this)[0];
	let bind = $(el).attr('data-bind');
	let api = $(el).attr('data-api');
	let prefix = $(el).attr('data-config-prefix');
	if(bind == 'true' && api !== 'false' && prefix !== 'false'){
		ajaxloader(".content-wrap","in");
		londerlandAPI2('GET',api).done(function(data) {
			var response = data.response;
			$('#'+prefix+'-settings-items').html(buildFormGroup(response.data));
		}).fail(function(xhr) {
			LonderlandApiError(xhr);
		});
		ajaxloader();
	}
});
$(document).on('change', '[id*=-form-chooseI]', function (e) {
	let el = $(this)[0];
	let id = $(el).attr('id');
	let newForm = (id.includes('new')) ? 'New' : '';
	let pasteId = id.match(/(?:[a-z]*-){1,5}/) + 'inputImage' + newForm;
	let newValue = $('#'+id).val();
	if(newValue !== 'Select or type Icon'){
		$('#'+pasteId).val(newValue);
	}
});
// SETTINGS DROPDOWN CHANGE
$(document).on("change", ".settings-dropdown-box", function () {
	let id = $(this).val();
	$(id).click();
});
$(document).on('click', '.nav-non-mobile li a', function() {
	let id = $(this).attr('id');
	let menu = $(this).parent().parent().attr('data-dropdown');
	$('.' + menu).val('#' + id);

});

// Toggle Side Menu
$(document).on('click', '.toggle-side-menu', function() {
	toggleTopBarHamburger();
});
// Toggle Side Menu Other
$(document).on('click', '.ti-shift-left.mouse', function() {
	toggleSideMenu();
});

// Log Details
$(document).on('click', '.log-details', function() {
	let trace = $(this).attr('data-trace');
	let activateClipboard = $(this).attr('data-clipboard');
	let el = $(this);
	el.find('i').toggleClass('fa fa-lg fa-spin mdi-reload');
	londerlandAPI2('GET','api/v2/log/all/'+trace).done(function(data) {
		try {
			let response = data.response;
			if(activateClipboard){
				clipboard(true,JSON.stringify(response.data));
			}else{
				formatLogDetails(response.data);
			}
		}catch(e) {
			londerlandCatchError(e,data);
		}
		el.find('i').toggleClass('fa fa-lg fa-spin mdi-reload');
	}).fail(function(xhr) {
		LonderlandApiError(xhr, 'API Error');
		el.find('i').toggleClass('fa fa-lg fa-spin mdi-reload');
	})
});

// Choose Log choose-londerland-log
$(document).on("change", ".choose-londerland-log", function () {
	londerlandLogTable.ajax.url($(this).val()).load();
});

// Test cron
$(document).on('click', '.test-cron', function() {
	let cron = $(this).parent().parent().find('input').val();
	testAPIConnection('cron',cron);
});

// Test Folder
$(document).on('click', '.test-folder', function() {
    let folder = $(this).parent().parent().find('input').val();
    testAPIConnection('folder',{'folder':folder});
});

