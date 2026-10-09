var OAuthLoginNeeded = false;
// The page itself never scrolls (html has overflow: hidden), so popups must scroll in their own fixed layer.
// Magnific Popup's default ('auto') switches that off on phones, which left long popups stuck.
if ($.magnificPopup) {
  $.extend($.magnificPopup.defaults, { fixedContentPos: true, fixedBgPos: true });
}
var directToHash = false;
var pingOrg = false;
var checkCommitLoadStatus = false;
var timeouts = {};
var increment = 0;
var tabInformation = {};
var tabActionsList = [];
tabActionsList["refresh"] = [];
tabActionsList["close"] = [];
var customHTMLEditorObject = [];
$.xhrPool = [];
// Add new jquery serializeObject function
$.fn.serializeObject = function () {
  var o = {};
  var a = this.serializeArray();
  $.each(a, function () {
    if (o[this.name] !== undefined) {
      if (!o[this.name].push) {
        o[this.name] = [o[this.name]];
      }
      o[this.name].push(this.value || "");
    } else {
      o[this.name] = this.value || "";
    }
  });
  return o;
};
// Start Londerland
$(document).ready(function () {
  if (getCookie("londerlandOAuth")) {
    OAuthLoginNeeded = true;
  }
  launch();
  local("r", "loggingIn");
});
/* NORMAL FUNCTIONS */
function orgDebug() {
  let cmd = $("#debug-input").val();
  let result = "";
  if (cmd !== "") {
    result = eval(cmd);
  }
  if (result !== "") {
    $("#debugResultsBox").removeClass("hidden");
    $("#debugResults").html(formatDebug(result));
    $(".cmdName").text(cmd);
    if (browserInfo.mobile !== true) {
      $("#debugResults > .whitebox").css({ height: "250px", "overflow-y": "auto" });
    }
  } else {
  }
}
function jsonToHTML(json) {
  var html = "";
  $.each(json, function (i, v) {
    if (typeof v === "object") {
      html += '<p class="tab0">' + i + ":</p>";
      $.each(v, function (index, value) {
        if (typeof value === "object") {
          html += '<p class="tab1">' + index + ":</p>";
          html += jsonToHTML2(value);
        } else {
          html += '<p class="tab1">' + index + ": " + value + "</p>";
        }
      });
    } else {
      html += '<p class="tab0">' + i + ": " + v + "</p>";
    }
  });
  return html;
}
function jsonToHTML2(json) {
  var html = "";
  $.each(json, function (i, v) {
    if (typeof v === "object") {
      html += '<p class="tab2">' + i + ":</p>";
      $.each(v, function (index, value) {
        if (typeof value === "object") {
          html += '<p class="tab3">' + index + ":</p>";
          html += jsonToHTML3(value);
        } else {
          html += '<p class="tab3">' + index + ": " + value + "</p>";
        }
      });
    } else {
      html += '<p class="tab2">' + i + ": " + v + "</p>";
    }
  });
  return html;
}
function jsonToHTML3(json) {
  var html = "";
  $.each(json, function (i, v) {
    if (typeof v === "object") {
      html += '<p class="tab4">' + i + ":</p>";
      $.each(v, function (index, value) {
        if (typeof value === "object") {
          html += '<p class="tab5">' + index + ":</p>";
          html += jsonToHTML2(value);
        } else {
          html += '<p class="tab5">' + index + ": " + value + "</p>";
        }
      });
    } else {
      html += '<p class="tab4">' + i + ": " + v + "</p>";
    }
  });
  return html;
}

function copyDebug() {
  var pre = $("#debugPreInfo").find(".whitebox").text();
  var debug = $("#debugResults").find(".whitebox").text();
  clipboard(true, pre + debug);
  console.log(pre + debug);
}
function formatDebug(result) {
  var formatted = "";
  switch (typeof result) {
    case "object":
      formatted = jsonToHTML(result);
      break;
    default:
      formatted = result;
  }
  return (
    '<pre class="whitebox bg-org text-success default-scroller">' +
    formatted +
    "</pre>"
  );
}
function getDebugPreInfo() {
  var formatted =
    "Version: " +
    activeInfo.version +
    "<br/>Server OS: " +
    activeInfo.serverOS +
    "<br/>PHP: " +
    activeInfo.phpVersion +
    "<br/>Install Type: " +
    (activeInfo.settings.misc.docker ? "Docker" : "Native") +
    "<br/>Auth Type: " +
    activeInfo.settings.misc.authType +
    "<br/>Auth Backend: " +
    activeInfo.settings.misc.authBackend +
    "<br/>Installed Plugins: " +
    formatDebug(activeInfo.settings.misc.installedPlugins) +
    "<br/>Installed Themes: " +
    formatDebug(activeInfo.settings.misc.installedThemes) +
    "<br/>Theme: " +
    activeInfo.theme +
    "<br/>Local: " +
    activeInfo.settings.user.local +
    "<br/>oAuth: " +
    activeInfo.settings.user.oAuthLogin +
    "<br/>Agent: " +
    activeInfo.settings.user.agent;
  formatted =
    '<pre class="whitebox bg-org text-success">' + formatted + "</pre>";
  $("#debugPreInfo").html(formatted);
  if (browserInfo.mobile !== true) {
    $("#debugPreInfo > .whitebox").css({ height: "250px", "overflow-y": "auto" });
  }
}
function orgDebugList(cmd) {
  if (cmd !== "") {
    $("#debug-input").val(cmd);
    orgDebug();
  }
}
function clipboard(trigger = true, string = null) {
  let clipboard = $("#internal-clipboard");
  if (string) {
    clipboard.attr("data-clipboard-text", string);
  }
  if (trigger) {
    clipboard.click();
  }
}
function contains(target, pattern) {
  var value = 0;
  pattern.forEach(function (word) {
    value = value + target.includes(word);
  });
  return value === 1;
}
function setTabInfo(id, action, value) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole("Set Tab Info", "No Tab Info Found... Id: " + id, "error");
    return false;
  }
  let tab = cleanClass(tabInfo.name);
  if (
    tab == "Londerland-Support" ||
    tab == "Londerland-Docs" ||
    tab == "Feature-Request"
  ) {
    return false;
  }
  if (tab !== null && action !== null && value !== null) {
    switch (action) {
      case "active":
        $.each(tabInformation, function (i, v) {
          tabInformation[i]["active"] = false;
        });
        break;
      default:
      //nada
    }
    tabInformation[id][action] = value;
  } else {
    return false;
  }
}
function tabTimerAction() {
  if (tabActionsList.close.length > 0) {
    $.each(tabActionsList.close, function (i, v) {
      var tab = v.tab;
      var minutes = tabInformation[v.id]["tabInfo"]["timeout_ms"] / 1000 / 60;
      var process = false;
      if (tabInformation[v.id]["loaded"]) {
        if (tabInformation[v.id]["active"] && idleTime >= 1) {
          process = true;
        }
        if (tabInformation[v.id]["active"] === false) {
          process = true;
        }
        if (process) {
          tabInformation[v.id]["increments"] =
            tabInformation[v.id]["increments"] + 1;
          if (tabInformation[v.id]["increments"] >= minutes) {
            tabInformation[v.id]["increments"] = 0;
            londerlandConsole("Tab Function", "Auto Closing tab: " + tab);
            closeTab(v.id);
          }
        }
      }
    });
  }
  if (tabActionsList.refresh.length > 0) {
    $.each(tabActionsList.refresh, function (i, v) {
      var tab = v.tab;
      var minutes = tabInformation[v.id]["tabInfo"]["timeout_ms"] / 1000 / 60;
      var process = false;
      if (tabInformation[v.id]["loaded"]) {
        tabInformation[v.id]["increments"] =
          tabInformation[v.id]["increments"] + 1;
        if (tabInformation[v.id]["increments"] >= minutes) {
          tabInformation[v.id]["increments"] = 0;
          londerlandConsole("Tab Function", "Auto Reloading tab: " + tab);
          reloadTab(v.id);
        }
      }
    });
  }
}
function timerIncrement() {
  increment = increment + 1;
  tabTimerAction();
  //check for cookieExpiry
  if (hasCookie) {
    if (getCookie("londerlandToken")) {
      //do nothing
    } else {
      location.reload();
    }
  }
  idleTime = idleTime + 1;
  if (typeof activeInfo !== "undefined") {
    if (
      activeInfo.settings.lockout.enabled &&
      activeInfo.settings.user.oAuthLogin !== true
    ) {
      if (
        idleTime > activeInfo.settings.lockout.timer &&
        $("#lockScreen").length !== 1
      ) {
        if (
          activeInfo.user.groupID <= activeInfo.settings.lockout.minGroup &&
          activeInfo.user.groupID >= activeInfo.settings.lockout.maxGroup
        ) {
          lock();
        }
      }
    }
  }
}
function ajaxloader(element = null, action = "out") {
  var loader = `
	<div class="ajaxloader">
		<svg class="circular" viewBox="25 25 50 50">
			<circle class="path" cx="50" cy="50" fill="none" r="20" stroke-miterlimit="10" stroke-width="5"></circle>
		</svg>
	</div>`;
  switch (action) {
    case "in":
    case "fadein":
      $(loader).appendTo(element);
      break;
    case "out":
    case "fadeout":
      $(".ajaxloader").remove();
      break;
    default:
      $(".ajaxloader").remove();
  }
}
// Tabs this user can open: the ones in their menu (enabled and allowed for their group)
function isOpenableTab(tabInfo) {
  return !!tabInfo && typeof tabInformation[tabInfo.id] !== "undefined";
}
// Tab to start with when the address names none (or one this user cannot open): the group's default tab, then the
// default tab of the Tab Editor, then the first tab of the user's menu. Tabs that open a new window are skipped.
function startTabId(defaultId) {
  const startable = (tabInfo) => isOpenableTab(tabInfo) && tabInfo.type != 2;
  for (const candidate of [activeInfo.settings.misc.groupDefaultTab, defaultId]) {
    if (candidate !== null && typeof candidate !== "undefined" && candidate !== "") {
      const tabInfo = findTab(candidate);
      if (startable(tabInfo)) {
        return tabInfo.id;
      }
    }
  }
  const first = $("#side-menu .allTabsList")
    .toArray()
    .map((li) => findTab($(li).attr("data-tab-id")))
    .find(startable);
  return first ? first.id : null;
}
function getDefault(id) {
  const hash = getHash();
  if (hash === "LonderlandLogin" && !activeInfo.user.loggedin) {
    loadNextTab(true);
    return;
  }
  if (hash !== false && hash !== "LonderlandLogin") {
    const hashTab = findTab(hash, isNaN(hash) ? "name" : "id");
    if (isOpenableTab(hashTab)) {
      directToHash = true;
      switchTab(hashTab.id);
      return;
    }
    londerlandConsole("Get Hash", "No Tab Info Found... Hash: " + hash, "error");
  }
  const start = startTabId(id);
  if (start !== null) {
    switchTab(start);
  } else {
    londerlandConsole("Get Default", "No tab to open for this user", "error");
    loadNextTab(true);
  }
}
function getHash() {
  if ($(location).attr("hash")) {
    return dirtyHash($(location).attr("hash").substr(1));
  }
  return false;
}
function setHash(hash) {
  window.location.hash = "#" + cleanHash(hash);
}
function iconPrefix(source) {
  if (!source) {
    // e.g. a category saved without an image
    return '<i class="fa fa-question fa-fw"></i>';
  }
  var tabIcon = source.split("::");
  var icons = {
    materialize: "mdi mdi-",
    fontawesome: "fa fa-",
    "fontawesome-brands": "fa-brands fa-",
    themify: "ti-",
    simpleline: "icon-",
    weathericon: "wi wi-",
    alphanumeric: "fa-fw",
  };
  if (Array.isArray(tabIcon) && tabIcon.length === 2) {
    if (tabIcon[0] !== "url" && tabIcon[0] !== "alphanumeric") {
      return '<i class="' + icons[tabIcon[0]] + tabIcon[1] + ' fa-fw"></i>';
    } else if (tabIcon[0] == "alphanumeric") {
      return '<i class="fa-fw">' + tabIcon[1] + "</i>";
    } else {
      return '<img class="fa-fw" src="' + tabIcon[1] + '" alt="tabIcon" />';
    }
  } else {
    return '<img class="fa-fw" src="' + source + '" alt="tabIcon" />';
  }
}
function iconPrefixSplash(source) {
  if (!source) {
    // e.g. a category saved without an image
    return '<i class="fa fa-question fa-fw"></i>';
  }
  var tabIcon = source.split("::");
  var icons = {
    materialize: "mdi mdi-",
    fontawesome: "fa fa-",
    "fontawesome-brands": "fa-brands fa-",
    themify: "ti-",
    simpleline: "icon-",
    weathericon: "wi wi-",
    alphanumeric: "fa-fw",
  };
  if (Array.isArray(tabIcon) && tabIcon.length === 2) {
    if (tabIcon[0] !== "url" && tabIcon[0] !== "alphanumeric") {
      return '<i class="' + icons[tabIcon[0]] + tabIcon[1] + ' fa-fw"></i>';
    } else if (tabIcon[0] == "alphanumeric") {
      return '<i class="fa-fw">' + tabIcon[1] + "</i>";
    } else {
      return tabIcon[1];
    }
  } else {
    return source;
  }
}
function cleanClass(string) {
  return string.replace(/ +/g, "-").replace(/\W+/g, "-");
}
function cleanHash(hash) {
  hash = encodeURI(hash);
  return hash.replaceAll("%20", "-");
}
function dirtyHash(hash) {
  hash = hash.replaceAll("-", "%20");
  return decodeURI(hash);
}
// What the hell is this?  I don't remember this lol
function noTabs(arrayItems) {
  if (arrayItems.data.user.loggedin === true) {
    londerlandAPI2("GET", "api/v2/page/tabs")
      .done(function (data) {
        try {
          var json = data.response;
          londerlandConsole("Londerland Function", "No tabs available");
          $(json.data).appendTo($(".londerland-area"));
          $(".londerland-area").removeClass("hidden");
          $("#preloader").fadeOut();
        } catch (e) {
          londerlandCatchError(e, data);
        }
      })
      .fail(function (xhr) {
        LonderlandApiError(xhr, "Error");
      });
  } else {
    $(".show-login").trigger("click");
  }
}
function formatImage(icon) {
  if (!icon.id || icon.text == "Select or type Image") {
    return icon.text;
  }
  var baseUrl = "/user/pages/images/flags";
  var $icon = $(
    '<span><img src="' +
      icon.id +
      '" class="img-chooser" /> ' +
      icon.text +
      "</span>"
  );
  return $icon;
}
function formatIcon(icon) {
  if (!icon.id || icon.text == "Select or type Icon") {
    return icon.text;
  }
  var $icon = $("<span>" + iconPrefix(icon.id) + icon.text + "</span>");
  return $icon;
}
function logout() {
  message(
    "",
    " Goodbye!",
    activeInfo.settings.notifications.position,
    "#FFF",
    "success",
    "10000"
  );
  londerlandAPI2("GET", "api/v2/logout")
    .done(function (data) {
      local("set", "message", "Goodbye|Logout Successful|success");
      history.replaceState(null, null, " ");
      if (
        activeInfo.settings.misc.authProxyOverrideLogout &&
        activeInfo.settings.misc.authProxyLogoutURL !== ""
      ) {
        location.href = activeInfo.settings.misc.authProxyLogoutURL;
      } else {
        location.reload();
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Logout Failed");
    });
}
function hideFrames(split = null) {
  let extra = split ? "-right" : "";
  $(".iFrame-listing" + extra + " div[class^='frame-container']")
    .addClass("hidden")
    .removeClass("show");
  $(".internal-listing" + extra + " div[class^='internal-container']")
    .addClass("hidden")
    .removeClass("show");
  $(".plugin-listing" + extra + " div[class^='plugin-container']")
    .addClass("hidden")
    .removeClass("show");
}
function closeSideMenu() {
  $(".content-wrapper").removeClass("show-sidebar");
}
function removeMenuActive() {
  $("#side-menu a").removeClass("active");
}
function swapDisplay(type, split) {
  let extra = split ? "-right" : "";
  switch (type) {
    case "internal":
      $("body").removeClass("fix-header");
      $(".iFrame-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      $(".internal-listing" + extra)
        .addClass("show")
        .removeClass("hidden");
      $(".login-area").addClass("hidden").removeClass("show");
      $(".plugin-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      //$('body').removeClass('fix-header');
      if (split) {
        $("#page-wrapper").addClass("split");
        $("#page-wrapper-right").removeClass("hidden");
      }
      break;
    case "iframe":
      $("body").addClass("fix-header");
      $(".iFrame-listing" + extra)
        .addClass("show")
        .removeClass("hidden");
      $(".internal-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      $(".login-area").addClass("hidden").removeClass("show");
      $(".plugin-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      //$('body').addClass('fix-header');
      if (split) {
        $("#page-wrapper").addClass("split");
        $("#page-wrapper-right").removeClass("hidden");
      }
      break;
    case "login":
      $("body").removeClass("fix-header");
      $(".iFrame-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      $(".internal-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      $(".login-area").addClass("show").removeClass("hidden");
      $(".plugin-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      if (activeInfo.settings.misc.minimalLoginScreen == true) {
        $(".sidebar").addClass("hidden");
        $(".navbar").addClass("hidden");
        $("#page-wrapper").addClass("hidden");
      }
      if (split) {
        $("#page-wrapper").addClass("split");
        $("#page-wrapper-right").removeClass("hidden");
      }
      break;
    case "plugin":
      $(".iFrame-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      $(".internal-listing" + extra)
        .addClass("hidden")
        .removeClass("show");
      $(".login-area").addClass("hidden").removeClass("show");
      $(".plugin-listing" + extra)
        .addClass("show")
        .removeClass("hidden");
      if (split) {
        $("#page-wrapper").addClass("split");
        $("#page-wrapper-right").removeClass("hidden");
      }
      break;
    default:
  }
}
function toggleParentActive(id) {
  var childTab = $("#menu-" + id);
  if (childTab.parent().hasClass("nav-second-level")) {
    if (!childTab.parent().hasClass("show")) {
      childTab.parent().addClass("collapse show");
      childTab.parent().parent().addClass("active");
    }
  }
}
function swapBodyClass(id) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole("Swap Body", "No Tab Info Found... Id: " + id, "error");
    return false;
  }
  let prior = $("body").attr("data-active-tab");
  let priorId = $("body").attr("data-active-tab-id");
  if (prior !== "") {
    $("body").removeClass("active-tab-" + prior);
    $("body").removeClass("active-tab-" + priorId);
  }
  $("body").attr("data-active-tab", tabInfo.name);
  $("body").attr("data-active-tab-id", tabInfo.id);
  $("body").addClass("active-tab-" + tabInfo.name);
  $("body").addClass("active-tab-" + tabInfo.id);
  // Only website (iframe) tabs can be opened in a new browser tab; Londerland's own pages are API addresses
  $(".open-in-new-tab").toggleClass("disabled-action", !isIframeTab(tabInfo));
}
function isIframeTab(tabInfo) {
  return tabInfo.type === 1 || tabInfo.type === "1" || tabInfo.type === "iframe";
}
function editPageTitle(title) {
  document.title = title + " - " + activeInfo.appearance.title;
}
function switchToPlugin(plugin) {
  closeSideMenu();
  removeMenuActive();
  swapDisplay("plugin");
  $(".plugin-container").each(function () {
    $(this).addClass("hidden").removeClass("show");
  });
  $("#container-plugin-" + plugin)
    .addClass("show")
    .removeClass("hidden");
}
function switchTab(id, split = null) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole("Switch Tab", "No Tab Info Found... Id: " + id, "error");
    return false;
  }
  if (activeInfo.settings.misc.collapseSideMenuOnClick) {
    if (!$(".navbar ").hasClass("sidebar-hidden")) {
      toggleSideMenu();
    }
  }
  let extra = split ? "right-" : "";
  let type = tabInfo.type;
  // need to rework for split
  if (type !== 2) {
    hideFrames(split);
    closeSideMenu();
    removeMenuActive();
    toggleParentActive(id);
    swapBodyClass(id);
  }
  if (type !== 2 && type !== "plugin") {
    setHash(tabInfo.name);
  }
  switch (type) {
    case 0:
    case "0":
    case "internal":
      swapDisplay("internal", split);
      var newTab = $("#internal-" + extra + id);
      $("#menu-" + id)
        .find("a")
        .addClass("active");
      editPageTitle(tabInfo.name);
      if (newTab.hasClass("loaded")) {
        londerlandConsole("Tab Function", "Switching to tab: " + tabInfo.name);
        newTab.addClass("show").removeClass("hidden");
        setTabInfo(id, "active", true);
      } else {
        //$("#preloader").fadeIn();
        londerlandConsole("Tab Function", "Loading new tab for: " + tabInfo.name);
        $("#menu-" + id + " a")
          .children()
          .addClass("tabLoaded");
        newTab.addClass("show loaded").removeClass("hidden");
        loadInternal(id, split);
        setTabInfo(id, "active", true);
        setTabInfo(id, "loaded", true);
        //$("#preloader").fadeOut();
      }
      break;
    case 1:
    case "1":
    case "iframe":
      swapDisplay("iframe", split);
      var newTab = $("#container-" + extra + id);
      var tabURL = newTab.attr("data-url");
      $("#menu-" + id)
        .find("a")
        .addClass("active");
      editPageTitle(tabInfo.name);
      if (newTab.hasClass("loaded")) {
        londerlandConsole("Tab Function", "Switching to tab: " + tabInfo.name);
        newTab.addClass("show").removeClass("hidden");
        setTabInfo(id, "active", true);
      } else {
        $("#preloader").fadeIn();
        londerlandConsole("Tab Function", "Loading new tab for: " + tabInfo.name);
        $("#menu-" + id + " a")
          .children()
          .addClass("tabLoaded");
        newTab.addClass("show loaded").removeClass("hidden");
        $(buildFrame(id, extra)).appendTo(newTab);
        setTabInfo(id, "active", true);
        setTabInfo(id, "loaded", true);
        $("#preloader").fadeOut();
      }
      $("#frame-" + id).focus();
      break;
    case 2:
    case 3:
    case "2":
    case "3":
    case "_blank":
    case "popout":
      popTab(id);
      break;
    case "plugin":
      swapDisplay("plugin");
      $("#container-plugin-" + id)
        .addClass("show")
        .removeClass("hidden");
      break;
    default:
      londerlandConsole("Tab Function", "Action not set", "error");
  }
}
function popTab(id) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Pop Tab Function",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  let name = tabInfo.name;
  switch (tabInfo.type) {
    case 0:
    case "0":
    case "internal":
      console.warn(
        "Tab Function: New window not supported for tab id: " +
          id +
          " | " +
          name
      );
      break;
    case 1:
    case "1":
    case "iframe":
    case 2:
    case 3:
    case "2":
    case "3":
    case "_blank":
    case "popout":
      londerlandConsole(
        "Tab Function",
        "Creating New Window for tab id: " + id + " | " + name
      );
      window.open(tabInfo.access_url, "_blank");
      break;
    default:
      londerlandConsole("Tab Function", "Action not set", "error");
  }
}
function closeTab(id) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Close Tab Function",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  // check if current tab?
  if ($(".active-tab-" + id).length > 0) {
    closeCurrentTab(event);
  } else {
    if ($(".frame-" + id).hasClass("loaded")) {
      switch (tabInfo.type) {
        case 0:
        case "0":
        case "internal":
          londerlandConsole("Tab Function", "Closing tab: " + tabInfo.name);
          $("#internal-" + id).html("");
          $("#menu-" + id + " a").removeClass("active");
          $("#menu-" + id + " a")
            .children()
            .removeClass("tabLoaded");
          $("#internal-" + id).removeClass("loaded show");
          $("#menu-" + id).removeClass("active");
          setTabInfo(id, "loaded", false);
          break;
        case 1:
        case "1":
        case "iframe":
          londerlandConsole("Tab Function", "Closing tab: " + tab);
          $("#menu-" + id + " a").removeClass("active");
          $("#menu-" + id + " a")
            .children()
            .removeClass("tabLoaded");
          $("#container-" + id).removeClass("loaded show");
          $("#frame-" + id).remove();
          setTabInfo(id, "loaded", false);
          break;
        case 2:
        case 3:
        case "2":
        case "3":
        case "_blank":
        case "popout":
          break;
        default:
          londerlandConsole("Tab Function", "Action not set", "error");
      }
    }
  }
}
function reloadTab(id) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Reload Tab Function",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  $("#preloader").fadeIn();
  londerlandConsole("Tab Function", "Reloading tab: " + tabInfo.name);
  switch (tabInfo.type) {
    case 0:
    case "0":
    case "internal":
      $("#frame-" + id).html("");
      loadInternal(id);
      break;
    case 1:
    case "1":
    case "iframe":
      $("#frame-" + id).attr("src", $("#frame-" + id).attr("src"));
      break;
    case 2:
    case 3:
    case "2":
    case "3":
    case "_blank":
    case "popout":
      break;
    default:
      londerlandConsole("Tab Function", "Action not set", "error");
  }
  $("#preloader").fadeOut();
}
function reloadCurrentTab() {
  //$("#preloader").fadeIn();
  londerlandConsole("Tab Function", "Reloading Current tab");
  let id = null;
  let iframe = $(".iFrame-listing").find(".show");
  let internal = $(".internal-listing").find(".show");
  if (iframe.length > 0) {
    var type = "iframe";
  } else if (internal.length > 0) {
    var type = "internal";
  } else {
    var type = "not set";
  }
  switch (type) {
    case 0:
    case "0":
    case "internal":
      let activeInternal = $(".internal-listing").find(".show");
      if (activeInternal) {
        id = activeInternal.attr("id");
        id = id.split("-")[1];
      }
      if (id) {
        var tabInfo = findTab(id);
        if (!tabInfo) {
          londerlandConsole(
            "Reload Current Tab Function",
            "No Tab Info Found... Id: " + id,
            "error"
          );
          return false;
        }
      } else {
        return false;
      }
      $(activeInternal).html("");
      loadInternal(id);
      break;
    case 1:
    case "1":
    case "iframe":
      let activeFrame = $(".iFrame-listing").find(".show").children("iframe");
      if (RegExp("^/.*").test(activeFrame.attr("src"))) {
        activeFrame.attr("src", activeFrame[0].contentWindow.location.pathname);
      } else {
        activeFrame.attr("src", activeFrame.attr("src"));
      }
      break;
    case 2:
    case 3:
    case "2":
    case "3":
    case "_blank":
    case "popout":
      break;
    default:
      console.error("Tab Function: Action not set");
  }
  //$("#preloader").fadeOut();
}
function loadNextTab(loadNextTabIfNotLoaded = false) {
  let next = $("#page-wrapper").find(".loaded").attr("id");
  if (next) {
    next = next.split("-")[1];
  }
  if (typeof next !== "undefined") {
    let parent = $("#menu-" + next).parent();
    if (
      parent.hasClass("show") === false &&
      parent.hasClass("nav-second-level")
    ) {
      parent.parent().find("a").first().trigger("click");
    }
    switchTab(next);
  } else {
    if (loadNextTabIfNotLoaded) {
      const tabInfo = findTab(0, "type") || findTab(1, "type");
      if (!tabInfo) {
        londerlandConsole("Tab Function", "No Available Tab to open", "error");
        return;
      }
      tabActions(1, tabInfo.id);
    } else {
      londerlandConsole("Tab Function", "No Available Tab to open", "error");
    }
  }
}
function closeCurrentTab(event) {
  let extra = "";
  let split = "";
  if (typeof event !== "undefined") {
    if (event.ctrlKey && event.altKey && !event.shiftKey) {
      extra = "-right";
      split = true;
    }
  }
  if ($(".plugin-listing").hasClass("show")) {
    hideFrames(split);
    loadNextTab();
    return false;
  }
  let id = $("body").attr("data-active-tab-id");
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Close Current Tab Function",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  var iframe = $(".iFrame-listing" + extra).find(".show");
  var internal = $(".internal-listing" + extra).find(".show");
  if (iframe.length > 0) {
    var type = "iframe";
  } else if (internal.length > 0) {
    var type = "internal";
  } else {
    var type = "not set";
  }
  switch (type) {
    case 0:
    case "0":
    case "internal":
      londerlandConsole("Londerland Function", "Closing tab: " + tabInfo.name);
      $("#internal" + extra + "-" + id).html("");
      $("#menu-" + id + " a").removeClass("active");
      $("#menu-" + id + " a")
        .children()
        .removeClass("tabLoaded");
      $("#internal" + extra + "-" + id).removeClass("loaded show");
      $("#menu-" + id).removeClass("active");
      setTabInfo(id, "loaded", false);
      setTabInfo(id, "active", false);
      loadNextTab();
      break;
    case 1:
    case "1":
    case "iframe":
      londerlandConsole("Londerland Function", "Closing tab: " + tabInfo.name);
      $("#menu-" + id + " a").removeClass("active");
      $("#menu-" + id + " a")
        .children()
        .removeClass("tabLoaded");
      $("#container" + extra + "-" + id)
        .removeClass("loaded show")
        .addClass("hidden");
      $("#frame" + extra + "-" + id).remove();
      setTabInfo(id, "loaded", false);
      setTabInfo(id, "active", false);
      loadNextTab();
      break;
    case 2:
    case 3:
    case "2":
    case "3":
    case "_blank":
    case "popout":
      break;
    default:
      londerlandConsole("Tab Function", "No Available Tab to open", "error");
  }
}
function openInNewBrowserTab() {
  let id = $("body").attr("data-active-tab-id");
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Open In New Browser Tab Function",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  if (!isIframeTab(tabInfo)) {
    return false;
  }
  window.open(tabInfo.access_url, "_blank", "noopener");
}
function findTab(query, term = "id") {
  let tabInfo = activeInfo.tabs.filter((tab) => tab[term] == query);
  return tabInfo.length >= 1 ? tabInfo[0] : false;
}
function tabActions(event, id, redirectURL = "") {
  if (event.which == 3) {
    return false;
  }
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Tab Action Function",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  let type = tabInfo.type;
  let name = tabInfo.name;
  if ((event.ctrlKey && !event.shiftKey && !event.altKey) || event.which == 2) {
    popTab(id);
  } else if (event.altKey && !event.shiftKey && !event.ctrlKey) {
    closeTab(id);
  } else if (event.shiftKey && !event.ctrlKey && !event.altKey) {
    reloadTab(id);
  } else if (event.ctrlKey && event.shiftKey && !event.altKey) {
    londerlandConsole("Tab Function", "Action not defined yet", "info");
  } else if (event.ctrlKey && event.altKey && !event.shiftKey) {
    londerlandConsole("Tab Function", "Action not defined yet", "info");
    switchTab(id, true);
  } else if (event.shiftKey && event.altKey && !event.ctrlKey) {
    londerlandConsole("Tab Function", "Action not defined yet", "info");
  } else {
    switchTab(id);
    if (type !== 2) {
      $(".splash-screen").removeClass("show").addClass("hidden");
    }
    if (redirectURL) {
      $(".close-popup").trigger("click");
      $("#frame-" + id).attr("src", redirectURL);
    }
  }
}
function arrayContains(needle, arrhaystack) {
  return arrhaystack.indexOf(needle) > -1;
}
/* END NORMAL FUNCTIONS */
/* BUILD FUNCTIONS */
/* END BUILD FUNCTIONS */
/* LONDERLAND API FUNCTIONS */
function selectOptions(options, active) {
  var selectOptions = "";
  $.each(options, function (i, v) {
    activeTest = active.split(",");
    if (activeTest.length > 1) {
      var selected = arrayContains(v.value, activeTest) ? "selected" : "";
    } else {
      var selected = active.toString() == v.value ? "selected" : "";
    }
    var disabled = v.disabled ? " disabled" : "";
    selectOptions +=
      "<option " +
      selected +
      disabled +
      ' value="' +
      v.value +
      '">' +
      v.name +
      "</option>";
  });
  return selectOptions;
}
function accordionOptions(options, parentID) {
  var accordionOptions = "";
  $.each(options, function (i, v) {
    var id = v.id;
    var extraClass = v.class ? " " + v.class : "";
    var header = v.header ? " " + v.header : "";
    if (typeof v.body == "object") {
      if (typeof v.body.length == "undefined") {
        var body = buildFormItem(v.body);
      } else {
        var body = "";
        $.each(v.body, function (int, val) {
          body += buildFormItem(val);
        });
      }
    } else {
      var body = v.body;
    }
    accordionOptions +=
      `
		<div class="card">
			<div class="card-header" id="` +
      id +
      `-heading" role="tab">
				<a class="card-title collapsed" data-bs-toggle="collapse" href="#` +
      id +
      `-collapse" data-bs-parent="#` +
      parentID +
      `" aria-expanded="false" aria-controls="` +
      id +
      `-collapse"><span lang="en">` +
      header +
      `</span></a>
			</div>
			<div class="card-collapse collapse" id="` +
      id +
      `-collapse" aria-labelledby="` +
      id +
      `-heading" role="tabpanel" aria-expanded="false" style="height: 0px;">
				<div class="card-body">` +
      body +
      `</div>
			</div>
		</div>
		`;
  });
  return accordionOptions;
}
function buildAccordion(array, open = false) {
  var items = "";
  var mainId = createRandomString(10);
  $.each(array, function (i, v) {
    var collapse = open && i == 0 ? "collapse show" : "collapse";
    var collapsed = open && i == 0 ? "" : "collapsed";
    var id = mainId + "-" + i;
    items +=
      `
        <div class="card">
            <div class="card-header bg-org" id="` +
      id +
      `-heading" role="tab"> <a class="card-title ` +
      collapsed +
      `" data-bs-toggle="collapse" href="#` +
      id +
      `-collapse" data-bs-parent="#` +
      mainId +
      `" aria-expanded="false" aria-controls="` +
      id +
      `-collapse"> <span lang="en">` +
      v.title +
      `</span> </a> </div>
            <div class="card-collapse ` +
      collapse +
      `" id="` +
      id +
      `-collapse" aria-labelledby="` +
      id +
      `-heading" role="tabpanel">
                <div class="card-body" lang="en"> ` +
      v.body +
      ` </div>
            </div>
        </div>
        `;
  });
  return (
    '<div class="card-stack" id="' +
    mainId +
    '" aria-multiselectable="true" role="tablist">' +
    items +
    "</div>"
  );
}
function buildFormItem(item) {
  var placeholder = item.placeholder
    ? ' placeholder="' + item.placeholder + '"'
    : "";
  var id = item.id ? ' id="' + item.id + '"' : "";
  var type = item.type ? ' data-type="' + item.type + '"' : "";
  var label = item.label ? ' data-label="' + item.label + '"' : "";
  var value = item.value ? ' value="' + item.value + '"' : "";
  var textarea = item.value ? item.value : "";
  var name = item.name ? ' name="' + item.name + '"' : "";
  var extraClass = item.class ? " " + item.class : "";
  var icon = item.icon ? " " + item.icon : "";
  var text = item.text ? " " + item.text : "";
  var attr = item.attr ? " " + item.attr : "";
  var disabled = item.disabled ? " disabled" : "";
  var href = item.href ? ' href="' + item.href + '"' : "";
  var pwd1 = createRandomString(6);
  var pwd2 = createRandomString(6);
  var pwd3 = createRandomString(6);
  var helpInfo = item.help
    ? '<div class="collapse" id="help-info-' +
      item.name +
      '"><blockquote lang="en">' +
      item.help +
      "</blockquote></div>"
    : "";
  var smallLabel = item.smallLabel
    ? '<label><span lang="en">' + item.smallLabel + "</span></label>" + helpInfo
    : "" + helpInfo;
  var pwgMgr =
    `
	<input name="disable-pwd-mgr-` +
    pwd1 +
    `" type="password" id="disable-pwd-mgr-` +
    pwd1 +
    `" style="display: none;" value="disable-pwd-mgr-` +
    pwd1 +
    `" />
	<input name="disable-pwd-mgr-` +
    pwd2 +
    `" type="password" id="disable-pwd-mgr-` +
    pwd2 +
    `" style="display: none;" value="disable-pwd-mgr-` +
    pwd2 +
    `" />
	<input name="disable-pwd-mgr-` +
    pwd3 +
    `" type="password" id="disable-pwd-mgr-` +
    pwd3 +
    `" style="display: none;" value="disable-pwd-mgr-` +
    pwd3 +
    `" />
	`;
  //+tof(item.value,'c')+`
  switch (item.type) {
    case "select-input":
      return (
        smallLabel +
        '<input list="' +
        item.name +
        'Options" data-changed="false" lang="en" type="text" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password" /><datalist id="' +
        item.name +
        'Options">' +
        selectOptions(item.options, item.value) +
        "</datalist>"
      );
    case "input":
    case "text":
      return (
        smallLabel +
        '<input data-changed="false" lang="en" type="text" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password" />'
      );
    case "number":
      return (
        smallLabel +
        '<input data-changed="false" lang="en" type="number" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password" />'
      );
    case "textbox":
      return (
        smallLabel +
        '<textarea data-changed="false" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password">' +
        textarea +
        "</textarea>"
      );
    case "password":
      return (
        smallLabel +
        pwgMgr +
        '<input data-changed="false" lang="en" type="password" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password" />'
      );
    case "password-alt":
      return (
        smallLabel +
        '<div class="input-group">' +
        pwgMgr +
        '<input data-changed="false" lang="en" type="password" class="password-alt form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password" /><button class="btn btn-secondary showPassword" type="button"><i class="fa fa-eye passwordToggle"></i></button></div>'
      );
    case "password-alt-copy":
      return (
        smallLabel +
        '<div class="input-group">' +
        pwgMgr +
        '<input data-changed="false" lang="en" type="password" class="password-alt form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' autocomplete="new-password" /><button class="btn btn-primary clipboard" type="button" data-clipboard-text="' +
        item.value +
        '"><i class="fa icon-docs"></i></button><button class="btn btn-inverse showPassword" type="button"><i class="fa fa-eye passwordToggle"></i></button></div>'
      );
    case "hidden":
      return (
        '<input data-changed="false" lang="en" type="hidden" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        " />"
      );
    case "select":
      return (
        smallLabel +
        '<select data-changed="false" class="form-control' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ">" +
        selectOptions(item.options, item.value) +
        "</select>"
      );
    case "select2":
      var select2ID = item.id ? "#" + item.id : "." + item.name;
      let settings = item.settings ? item.settings : "{}";
      return (
        smallLabel +
        '<select class="m-b-10 ' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        ' multiple="multiple" data-placeholder="">' +
        selectOptions(item.options, item.value) +
        '</select><script>initMultiSelect("' +
        select2ID +
        '", ' +
        settings +
        ');</script>'
      );
    case "switch":
    case "checkbox":
      return (
        smallLabel +
        '<input data-changed="false" type="checkbox" class="js-switch' +
        extraClass +
        '" data-size="medium" data-color="#99d683" data-secondary-color="#f96262"' +
        name +
        value +
        tof(item.value, "c") +
        id +
        disabled +
        type +
        label +
        attr +
        ' /><input data-changed="false" type="hidden"' +
        name +
        'value="false">'
      );
    case "button":
      return (
        smallLabel +
        '<button class="btn btn-sm btn-success btn-rounded waves-effect waves-light b-none' +
        extraClass +
        '" ' +
        href +
        attr +
        ' type="button"><span class="btn-label"><i class="' +
        icon +
        '"></i></span><span lang="en">' +
        text +
        "</span></button>"
      );
    case "blank":
      return "";
    case "accordion":
      return (
        '<div class="card-stack' +
        extraClass +
        '"' +
        placeholder +
        value +
        id +
        name +
        disabled +
        type +
        label +
        attr +
        '  aria-multiselectable="true" role="tablist">' +
        accordionOptions(item.options, item.id) +
        "</div>"
      );
    case "html":
      return item.html;
    case "arrayMultiple":
      return '<span class="text-danger">BuildFormItem Class not setup...';
    case "cron":
      return `${smallLabel}<div class="input-group"><input data-changed="false" class="form-control ${extraClass}" ${placeholder} ${value} ${id} ${name} ${disabled} ${type} ${label} ${attr} autocomplete="new-password"><button class="btn btn-info test-cron" type="button"><i class="fa fa-flask"></i></button></div>`;
    case "folder":
      return `${smallLabel}<div class="input-group"><input data-changed="false" class="form-control ${extraClass}" ${placeholder} ${value} ${id} ${name} ${disabled} ${type} ${label} ${attr} autocomplete="new-password"><button class="btn btn-info test-folder" type="button"><i class="fa fa-flask"></i></button></div>`;
    default:
      return '<span class="text-danger">BuildFormItem Class not setup...';
  }
}
function checkCronFile() {
  $(".cron-results-container").removeClass("hidden");
  londerlandAPI2("GET", "api/v2/test/cron")
    .done(function (data) {
      try {
        $(".cron-results").text("Cron file is setup correctly");
      } catch (e) {
        $(".cron-results").text("Unknown error");
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      $(".cron-results").text("Cron file is not setup or is setup incorrectly");
      LonderlandApiError(xhr);
    });
}
function buildPluginsItem(array, type = "enabled") {
  var activePlugins = "";
  var inactivePlugins = "";
  $.each(array, function (i, v) {
    var settingsPage =
      v.settings == true && type == "enabled"
        ? `
		<!-- Plugin Settings Page -->
		<form id="` +
          v.idPrefix +
          `-settings-page" class="mfp-hide white-popup mfp-with-anim addFormTick col-lg-10 offset-lg-1" autocomplete="off">
            <div class="card bg-org card-info">
                <div class="card-header">
                    <span lang="en">` +
          v.name +
          ` Settings</span>
                    <button type="button" class="btn bg-org btn-circle close-popup float-end"><i class="fa fa-times"></i> </button>
                    <button id="` +
          v.idPrefix +
          `-settings-page-save" onclick="submitSettingsForm('` +
          v.idPrefix +
          `-settings-page')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light float-end hidden animated loop-animation rubberBand m-r-20" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
                </div>
                <div class="card-wrapper collapse show" aria-expanded="true">
                    <div class="bg-org">
                        <fieldset id="` +
          v.idPrefix +
          `-settings-items" style="border:0;" class=""><h2>Loading...</h2></fieldset>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
		</form>
		`
        : "";
    var href =
      v.settings == true
        ? "#" + v.idPrefix + "-settings-page"
        : "javascript:void(0);";
    if (v.enabled == true) {
      var activeToggle =
        `<li><a class="btn default btn-outline disablePlugin" href="javascript:void(0);" data-plugin-name="` +
        v.name +
        `" data-config-prefix="` +
        v.configPrefix +
        `" data-config-name="` +
        v.configPrefix +
        `-enabled"><i class="ti-power-off fa-2x"></i></a></li>`;
      var settings =
        `<li><a class="btn default btn-outline popup-with-form" href="` +
        href +
        `" data-effect="mfp-3d-unfold"data-plugin-name="` +
        v.name +
        `" id="` +
        v.idPrefix +
        `-settings-button" data-config-prefix="` +
        v.configPrefix +
        `" data-api="${v.api}" data-settings="${v.settings}" data-bind="${v.bind}"><i class="ti-panel fa-2x"></i></a></li>`;
    } else {
      var activeToggle =
        `<li><a class="btn default btn-outline enablePlugin" href="javascript:void(0);" data-plugin-name="` +
        v.name +
        `" data-config-prefix="` +
        v.configPrefix +
        `" data-config-name="` +
        v.configPrefix +
        `-enabled"><i class="ti-plug fa-2x"></i></a></li>`;
      var settings = "";
    }
    var plugin =
      `
		<div class="col-xl-2 col-lg-2 col-md-6 col-6 m-b-10">
			<div class="white-box m-0">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0"> <img class="lazyload" data-src="` +
      v.image +
      `">
						<div class="el-overlay">
							<ul class="el-info">
								${settings} ${activeToggle}
							</ul>
						</div>
					</div>
					<div class="el-card-content">
						<h3 class="box-title elip">` +
      v.name +
      `</h3>
						<small class="elip text-uppercase p-b-10">` +
      v.category +
      `</small>
					</div>
				</div>
			</div>
		</div>
		`;
    if (v.enabled == true) {
      activePlugins += plugin + settingsPage;
    } else {
      inactivePlugins += plugin + settingsPage;
    }
  });
  activePlugins =
    activePlugins.length !== 0
      ? activePlugins
      : '<h2 class="text-center" lang="en">Nothing Active</h2>';
  inactivePlugins =
    inactivePlugins.length !== 0
      ? inactivePlugins
      : '<h2 class="text-center" lang="en">Everything Active</h2>';
  return type === "enabled"
    ? `
	<div class="card bg-org card-info">
		<div class="card-header">
			<span lang="en">Active Plugins</span>
		</div>
		<div class="card-wrapper collapse show" aria-expanded="true">
			<div class="card-body bg-org">
				<div class="row el-element-overlay m-b-40">` +
        activePlugins +
        `</div>
			</div>
		</div>
	</div>
	<div class="clearfix"></div>`
    : `	
	<div class="card bg-org card-info">
		<div class="card-header">
			<span lang="en">Inactive Plugins</span>
		</div>
		<div class="card-wrapper collapse show" aria-expanded="true">
			<div class="card-body bg-org">
				<div class="row el-element-overlay m-b-40">` +
        inactivePlugins +
        `</div>
			</div>
		</div>
	</div>`;
}


// Folder/file list rendered with native <details> elements
function buildPlugins(status = "enabled") {
  londerlandAPI2("GET", "api/v2/plugins/" + status)
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#" + status + "-plugin-area").html(
        buildPluginsItem(response.data, status)
      );
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function buildFormGroup(array) {
  var mainCount = 0;
  var group = '<div class="tab-content w-100">';
  var uList =
    '<div class="vtabs customvtab"><ul class="nav tabs-vertical" role="tablist">';
  $.each(array, function (i, v) {
    mainCount++;
    var count = 0;
    var total = v.length;
    var active = mainCount == 1 ? "active" : "";
    var customID = createRandomString(10);
    if (i == "custom") {
      group += v;
    } else {
      uList +=
        `<li role="presentation" class="` +
        active +
        `"><a href="#` +
        customID +
        cleanClass(i) +
        `" aria-controls="` +
        i +
        `" role="tab" data-bs-toggle="tab" aria-expanded="false"><span lang="en">` +
        i +
        `</span></a></li>`;
      group +=
        `
				<!-- FORM GROUP -->
				<div role="tabpanel" class="tab-pane fade show ` +
        active +
        `" id="` +
        customID +
        cleanClass(i) +
        `">
			`;
      $.each(v, function (i, v) {
        var override = "6";
        if (typeof v.override !== "undefined") {
          override = v.override;
        }
        var arrayMultiple = false;
        if (typeof v.type !== "undefined") {
          if (v.type == "arrayMultiple") {
            arrayMultiple = true;
          }
        }
        count++;
        if (count % 2 !== 0) {
          group += '<div class="row start">';
        }
        var helpID = "#help-info-" + v.name;
        var helpTip = v.help
          ? '<sup><a class="help-tip" data-bs-toggle="collapse" href="' +
            helpID +
            '" aria-expanded="true"><i class="m-l-5 fa fa-question-circle text-info" title="Help" data-bs-toggle="tooltip"></i></a></sup>'
          : "";
        var builtItems = "";
        if (arrayMultiple == true) {
          $.each(v.value, function (index, value) {
            if (typeof value === "object") {
              builtItems += '<div class="row m-b-40">';
              $.each(value, function (number, formItem) {
                let clearfix =
                  formItem.type == "blank"
                    ? '<div class="clearfix"></div>'
                    : "";
                builtItems += `
                                    <!-- INPUT BOX  Yes Multiple -->
                                    <div class="col-lg-6 p-b-10">
                                        <div class="form-group">
                                            <label class="form-label col-lg-12"><span lang="en">${
                                              formItem.label
                                            }</span>${helpTip}</label>
                                            <div class="col-lg-12">${buildFormItem(
                                              formItem
                                            )}</div> <!-- end div -->
                                        </div>
                                    </div>
                                    ${clearfix}
                                    <!--/ INPUT BOX -->
                                `;
              });
              builtItems += "</div>";
            } else {
              builtItems += buildFormItem(value);
            }
          });
        } else {
          builtItems =
            `
					<!-- INPUT BOX  no Multiple-->
					<div class="col-lg-` +
            override +
            ` p-b-10">
						<div class="form-group">
							<label class="form-label col-lg-12"><span lang="en">${
                v.label
              }</span>${helpTip}</label>
							<div class="col-lg-12">
								${buildFormItem(v)}
							</div>
						</div>
					</div>
					<!--/ INPUT BOX -->
				`;
        }
        group += builtItems;
        if (count % 2 == 0 || count == total) {
          group += "</div><!--end-->";
        }
      });
      group += "</div>";
    }
  });
  return uList + "</ul>" + group + "</div>";
}
function createImageSwal(attr) {
  let title = attr.attr("data-title");
  let fullPath = attr.attr("data-image-path");
  let clipboardText = attr.attr("data-clipboard-text");
  let name = attr.attr("data-image-name");
  let extension = attr.attr("data-image-name-ext");
  let div =
    `
		<div class="card card-default">
            <div class="card-header"><h1><img class="center" src="` +
    fullPath +
    `" style="height: 50px; width: 50px">` +
    title +
    `</h1></div>
            <div class="card-wrapper collapse show">
                <div class="card-body">
                	<h5 lang="en">Choose action:</h5>
					<div class="button-box">
                        <button class="btn btn-info waves-effect waves-light clipboard" type="button" data-clipboard-text="` +
    clipboardText +
    `"><span class="btn-label"><i class="ti-clipboard"></i></span><span lang="en">Copy to Clipboard</span></button>
                        <button class="btn btn-danger waves-effect waves-light deleteImage" type="button" data-image-path="` +
    fullPath +
    `" data-image-name="` +
    name +
    `" data-image-name-ext="` +
    extension +
    `"><span class="btn-label"><i class="fa fa-trash"></i></span><span lang="en">Delete</span></button>                        
                    </div>
                </div>
            </div>
        </div>
        `;
  Swal.fire({
    html: createElementFromHTML(div),
    showConfirmButton: false,
    customClass: { popup: "bg-org" },
  });
}
function buildImageManagerViewItem(array) {
  var imageListing = "";
  if (Array.isArray(array)) {
    $.each(array, function (i, v) {
      var filepath = v.split("/");
      var name = filepath[filepath.length - 1].split(".");
      var clipboardText = v.replace(/ /g, "%20");
      var fileAndExt = filepath[filepath.length - 1];
      imageListing +=
        `
			<a class="imageManagerItem" href="javascript:void(0);" data-gallery="multiimages" data-title="` +
        name[0] +
        `" data-clipboard-text="` +
        clipboardText +
        `" data-image-path="` +
        v +
        `" data-image-name="` +
        name[0] +
        `" data-image-name-ext="` +
        fileAndExt +
        `"><img data-bs-toggle="tooltip" title="${name[0]}" data-bs-placement="bottom"  data-src="` +
        v +
        `" alt="tabImage" class="all studio lazyload" /> </a>
			`;
    });
  }
  return imageListing;
}
function buildImageManagerView() {
  londerlandAPI2("GET", "api/v2/image")
    .done(function (data) {
      try {
        let response = data.response;
        $(".settings-image-manager-list").html(
          buildImageManagerViewItem(response.data)
        );
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function buildCustomizeAppearance() {
  londerlandAPI2("GET", "api/v2/settings/appearance")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#customize-appearance-form").html(buildFormGroup(response.data));
      initColorPickers("input.pick-a-color-custom-options");
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function buildSSO() {
  londerlandAPI2("GET", "api/v2/settings/sso")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#sso-form").html(buildFormGroup(response.data));
    })
    .fail(function (xhr) {
      console.error("Londerland Function: API Connection Failed");
    });
}
function buildSettingsMain() {
  londerlandAPI2("GET", "api/v2/settings/main")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#settings-main-form").html(buildFormGroup(response.data));
      changeAuth();
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function buildUserManagement() {
  londerlandAPI2("GET", "api/v2/users?includeGroups")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#manageUserTable").html(buildUserManagementItem(response.data));
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function buildGroupManagement() {
  londerlandAPI2("GET", "api/v2/groups?includeUsers")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#manageGroupTable").html(buildGroupManagementItem(response.data));
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function buildTabEditor() {
  londerlandAPI2("GET", "api/v2/tabs")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#tabEditorTable").html(buildTabEditorItem(response.data));
      addTabSortable();
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
async function addTabSortable() {
  await londerlandLoadLibrary("sortable");
  let el = document.getElementById("tabEditorTable");
  let tabSorter = new Sortable(el, {
    handle: ".sort-tabs-handle",
    ghostClass: "sortable-ghost",
    multiDrag: true,
    selectedClass: "multi-selected",
    onUpdate: function (evt) {
      $("input.order").each(function (idx) {
        $(this).val(idx + 1);
      });
      newTabsGlobal = $("#submit-tabs-form").serializeToJSON();
      $(".saveTabOrderButton").removeClass("hidden");
    },
  });
}
function buildCategoryEditor() {
  londerlandAPI2("GET", "api/v2/tabs")
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#categoryEditorTable").html(buildCategoryEditorItem(response.data));
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
/* END LONDERLAND API FUNCTIONS */
function updateUserInformation() {
  var passwordMatch = true;
  var username = $("#accountUsername").val();
  var email = $("#accountEmail").val();
  var password1 = $("#accountPassword1").val();
  var password2 = $("#accountPassword2").val();
  if (password1 != password2) {
    passwordMatch = false;
    messageSingle(
      "",
      "Passwords do not match",
      activeInfo.settings.notifications.position,
      "#FFF",
      "error",
      "5000"
    );
    return false;
  }
  if (username !== "" && email !== "" && passwordMatch == true) {
    var post = {
      username: username,
      email: email,
    };
    if (password1 !== "") {
      post["password"] = password1;
    }
    ajaxloader(".content-wrap", "in");
    londerlandAPI2("PUT", "api/v2/users/" + activeInfo.user.userID, post)
      .done(function (data) {
        try {
          var response = data.response;
          $.magnificPopup.close();
          messageSingle(
            "",
            window.lang.translate("User Info Updated"),
            activeInfo.settings.notifications.position,
            "#FFF",
            "success",
            "5000"
          );
        } catch (e) {
          londerlandCatchError(e, data);
        }
        ajaxloader();
      })
      .fail(function (xhr) {
        LonderlandApiError(xhr, "Update User");
        ajaxloader();
      });
  }
}
// Avatar dialog (Manage Users and Account Settings): a web address or an image path; empty = Gravatar
function changeUserAvatar(id, current, done = null) {
  current = $("<textarea>").html(current || "").text();
  Swal.fire({
    title: window.lang.translate("Change Avatar"),
    html:
      '<img class="rounded-circle mb-3 avatar-preview" width="100" height="100" style="object-fit: cover" alt="">' +
      '<input class="form-control avatar-url" placeholder="https://... or data/userTabs/me.png">' +
      '<p class="text-muted small mt-2 mb-0">' +
      window.lang.translate("A web address of an image, or an image uploaded under Settings > Image Manager (data/userTabs/file-name.png). Leave empty to use the Gravatar of the email address.") +
      "</p>",
    customClass: { popup: "bg-org" },
    showCancelButton: true,
    confirmButtonText: window.lang.translate("Save"),
    cancelButtonText: window.lang.translate("Cancel"),
    didOpen: function (popup) {
      const $input = $(popup).find(".avatar-url").val(current);
      const $preview = $(popup).find(".avatar-preview").attr("src", current);
      $input.on("input", function () {
        const value = this.value.trim();
        $preview.attr("src", /^(https?:\/\/|data\/|plugins\/images\/)/i.test(value) ? value : current);
      });
      $input.trigger("focus");
    },
    preConfirm: function () {
      return $(Swal.getPopup()).find(".avatar-url").val().trim();
    },
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }
    londerlandAPI2("PUT", "api/v2/users/" + id, { image: result.value }, true)
      .done(function (data) {
        const image = (data.response.data && data.response.data.image) || result.value;
        if (id == activeInfo.user.userID) {
          activeInfo.user.image = image;
          $(".profile-image, .dw-user-box .u-img img, .user-pro > a > img, .account-avatar").attr("src", image);
        }
        if (done) {
          done(image);
        }
        message("User Updated", window.lang.translate("Avatar changed"), activeInfo.settings.notifications.position, "#FFF", "success", "5000");
      })
      .fail(function (xhr) {
        LonderlandApiError(xhr, "Avatar");
      });
  });
}
function twoFA(action, type, secret = null) {
  switch (action) {
    case "activate":
      londerlandAPI2("POST", "api/v2/2fa/" + type, {})
        .done(function (data) {
          try {
            var html = data.response;
          } catch (e) {
            londerlandCatchError(e, data);
          }
          let div =
            `
				<div class="card card-default">
                    <div class="card-header">Enable 2FA: ` +
            html.data.type +
            `</div>
                    <div class="card-wrapper collapse show">
                        <div class="card-body">
                            <p class="twofa-modal-image"><img class="center" src="` +
            html.data.url +
            `"></p>
                            <h5 class="twofa-modal-secret text-center">` +
            html.data.secret +
            `</h5>
	                        <div class="form-group m-t-10">
	                            <div class="input-group" style="width: 100%;">
	                                <div class="input-group-text hidden-xs"><i class="ti-lock"></i></div>
	                                <input type="text" class="form-control tfa-input" id="twofa-verify" placeholder="Code" autocomplete="off" autocorrect="off" autocapitalize="off" maxlength="6" spellcheck="false" autofocus="" required="">
	                            </div>
	                            <br>
	                            <button class="btn w-100 btn-info" onclick="twoFA('verify','google');">Verify</button>
	
	                        </div>
                        </div>
                    </div>
                </div>
                `;
          Swal.fire({
            html: createElementFromHTML(div),
            showConfirmButton: false,
            customClass: { popup: "bg-org" },
          });
        })
        .fail(function (xhr) {
          LonderlandApiError(xhr, "2FA");
        });
      break;
    case "deactivate":
      londerlandAPI2("DELETE", "api/v2/2fa")
        .done(function (data) {
          try {
            message(
              "2FA Removed",
              "",
              activeInfo.settings.notifications.position,
              "#FFF",
              "success",
              "5000"
            );
            $(".2fa-list").replaceWith(buildTwoFA("internal"));
          } catch (e) {
            londerlandCatchError(e, data);
          }
        })
        .fail(function (xhr) {
          LonderlandApiError(xhr, "2FA");
        });
      break;
    case "verify":
      var secret = $(".twofa-modal-secret").text();
      var code = $("#twofa-verify").val();
      if (type !== "" && secret !== "" && code !== "") {
        londerlandAPI2("POST", "api/v2/2fa", {
          type: type,
          secret: secret,
          code: code,
        })
          .done(function (data) {
            try {
              var html = data.response;
              message(
                "2FA Success",
                "Input Code Validated! Saving...",
                activeInfo.settings.notifications.position,
                "#FFF",
                "success",
                "5000"
              );
              Swal.close();
              twoFA("save", type, secret);
            } catch (e) {
              londerlandCatchError(e, data);
            }
          })
          .fail(function (xhr) {
            LonderlandApiError(xhr, "2FA");
          });
      } else {
        message(
          "2FA Failed",
          "Input Code",
          activeInfo.settings.notifications.position,
          "#FFF",
          "warning",
          "5000"
        );
      }
      break;
    case "save":
      londerlandAPI2("PUT", "api/v2/2fa", { type: type, secret: secret })
        .done(function (data) {
          try {
            var html = data.response;
            message(
              "2FA Success",
              "2FA Saved",
              activeInfo.settings.notifications.position,
              "#FFF",
              "success",
              "5000"
            );
            $(".2fa-list").replaceWith(buildTwoFA(type));
          } catch (e) {
            londerlandCatchError(e, data);
          }
        })
        .fail(function (xhr) {
          LonderlandApiError(xhr, "2FA");
        });
      break;
  }
}
function buildTwoFA(current) {
  switch (current) {
    case "internal":
      var option = `
                <div class="col-xl-3 col-md-6 row-in-br">
                    <ul class="col-in">
                        <li>
                            <span class="circle circle-md bg-info"><i class="mdi mdi-webpack mdi-24px"></i></span>
                        </li>
                        <li class="col-middle">
                            <h5>Londerland Authenticator</h5>
                            <h5><span lang="en">Current</span></h5>
                        </li>
                    </ul>
                </div>
                <div class="col-xl-3 col-md-6 row-in-br">
                    <ul class="col-in">
                        <li>
                            <span class="circle circle-md bg-info"><i class="fa fa-google"></i></span>
                        </li>
                        <li class="col-middle">
                            <h5>Google Authenticator</h5>
                            <h5><a href="javascript:void(0)" onclick="twoFA('activate','google');"><span lang="en">Activate</span></a></h5>
                        </li>
                    </ul>
                </div>
            `;
      break;
    case "google":
      var option = `
                <div class="col-xl-3 col-md-6 row-in-br">
                    <ul class="col-in">
                        <li>
                            <span class="circle circle-md bg-info"><i class="fa fa-google"></i></span>
                        </li>
                        <li class="col-middle">
                            <h5>Google Authenticator</h5>
                            <h5><a href="javascript:void(0)" onclick="twoFA('deactivate','google');"><span lang="en">Deactivate</span></a></h5>
                        </li>
                    </ul>
                </div>
            `;
      break;
    default:
      break;
  }
  var element =
    `
    <div class="white-box 2fa-list">
        <div class="row row-in">
            ` +
    option +
    `
        </div>
    </div>
    `;
  return element;
}
function revokeToken(id) {
  londerlandAPI2("DELETE", "api/v2/token/" + id, {})
    .done(function (data) {
      try {
        $("#token-" + id).fadeOut();
        message(
          window.lang.translate("Removed Token"),
          "",
          activeInfo.settings.notifications.position,
          "#FFF",
          "success",
          "3500"
        );
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      ajaxloader();
      LonderlandApiError(xhr, "Revoke Token");
    });
}
function buildActiveTokens(array) {
  var tokens = "";
  $.each(array, function (i, v) {
    var className =
      activeInfo.user.token === v.token ? "bg-success text-inverse" : "";
    var extraText =
      activeInfo.user.token === v.token
        ? '<span class="tooltip-info" data-bs-toggle="tooltip" data-bs-placement="right" title="" data-bs-title="Current Token">...' +
          v.token.substr(-10, 10) +
          "</span>"
        : v.token.substr(-10, 10);
    if (typeof v.created == "object") {
      v.created = v.created.date;
    }
    if (typeof v.expires == "object") {
      v.expires = v.expires.date;
    }
    v.created = v.created.indexOf("Z") !== -1 ? v.created : v.created + "Z";
    v.expires = v.expires.indexOf("Z") !== -1 ? v.expires : v.expires + "Z";

    tokens +=
      `
            <tr id="token-` +
      v.id +
      `" class="` +
      className +
      `">
                <td>` +
      v.id +
      `</td>
                <td>` +
      extraText +
      `</td>
                <td>` +
      moment(v.created).format("LLL") +
      `</td>
                <td>` +
      moment(v.expires).format("LLL") +
      `</td>
                <td>` +
      v.ip +
      `</td>
                <td>
                    <button class="btn btn-danger waves-effect waves-light" type="button" onclick="revokeToken('` +
      v.id +
      `');"><i class="fa fa-ban"></i></button>
                </td>
            </tr>
        `;
  });
  return (
    `
        <div class="col-xl-12">
            <div class="card card-info">
                <div class="card-header"> <span lang="en">Active Tokens</span>
                    <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-plus"></i></a> </div>
                </div>
                <div class="card-wrapper collapse" aria-expanded="true">
                    <div class="card-body bg-org p-0">
                        <div class="table-responsive">
                            <table class="table color-table info-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th lang="en">Token</th>
                                        <th lang="en">Created</th>
                                        <th lang="en">Expires</th>
                                        <th lang="en">IP</th>
                                        <th lang="en">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ` +
    tokens +
    `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
  );
}
function accountManager(user) {
  var passwordMessage = "";
  switch (activeInfo.settings.misc.authBackend) {
    case "plex":
      passwordMessage = `
                <div class="col-xl-12">
                    <div class="card card-info">
                        <div class="card-header"> <span lang="en">Password Notice</span>
                            <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-plus"></i></a> </div>
                        </div>
                        <div class="card-wrapper collapse" aria-expanded="true">
                            <div class="card-body bg-org">
                                <p lang="en">If you signed in with a Plex Acct... Please use the following link to change your password there:</p><br>
                                <p><a href="https://app.plex.tv/auth#?resetPassword" target="_blank" lang="en">Change Password on Plex Website</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      break;
    case "emby":
      passwordMessage = `
                <div class="col-xl-12">
                    <div class="card card-info">
                        <div class="card-header"> <span lang="en">Password Notice</span>
                            <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="card-dismiss"><i class="ti-close"></i></a> </div>
                        </div>
                        <div class="card-wrapper collapse show" aria-expanded="true">
                            <div class="card-body bg-org">
                                <p lang="en">If you signed in with a Emby Acct... Please use the following link to change your password there:</p><br>
                                <p><a href="https://emby.media/community/index.php?app=core&module=global&section=lostpass" target="_blank">Change Password on Emby Website</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      break;
    default:
      passwordMessage = "";
      break;
  }
  if (user.data.user.loggedin === true) {
    var twoFADisable =
      buildTwoFA(user.data.user.authService) == "internal" ? "" : "disabled";
    var activeTokens = buildActiveTokens(user.data.user.tokenList);
    var accountDiv =
      `
		<div id="account-area" class="white-popup mfp-with-anim mfp-hide">
			<div class="col-lg-10 offset-lg-1">
				<div class="row">
					<div class="col-lg-12">
						<div class="card card-info m-0">
							<div class="card-header">
								<span lang="en">Account Information</span>
								<div class="float-end d-flex gap-2">
									<button class="btn btn-info waves-effect waves-light" type="button" onclick="updateUserInformation();" title="Save">
										<i class="fa fa-save"></i>
									</button>
									<button class="btn btn-outline-light waves-effect waves-light account-area-close" type="button" onclick="$.magnificPopup.close();" title="Close" aria-label="Close">
										<i class="fa fa-times"></i>
									</button>
								</div>
							</div>
							<div class="card-wrapper collapse show main-email-panel" aria-expanded="true">
								<div class="card-body">
									<div class="form-body">
									    ` +
      buildTwoFA(user.data.user.authService) +
      `
										<div class="row">
                                            <div class="col-xl-12">
                                                <div class="card card-info">
                                                    <div class="card-header"> <span lang="en">User Information</span>
                                                        <div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-plus"></i></a> </div>
                                                    </div>
                                                    <div class="card-wrapper collapse" aria-expanded="true">
                                                        <div class="card-body bg-org p-0 p-t-10">
                                                            <div class="col-lg-12">
                                                                <div class="form-group">
                                                                    <label class="form-label" lang="en">Avatar</label>
                                                                    <div class="d-flex align-items-center gap-3">
                                                                        <img class="rounded-circle account-avatar" width="60" height="60" style="object-fit: cover" alt="" src="` +
      activeInfo.user.image +
      `">
                                                                        <button type="button" class="btn btn-info btn-sm" onclick="changeUserAvatar(activeInfo.user.userID, activeInfo.user.image)"><i class="fa fa-image me-1"></i> <span lang="en">Change</span></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" lang="en">Username</label>
                                                                    <input ` +
      twoFADisable +
      ` type="text" id="accountUsername" class="form-control" value="` +
      activeInfo.user.username +
      `"></div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" lang="en">Email</label>
                                                                    <input ` +
      twoFADisable +
      ` type="text" id="accountEmail" class="form-control" value="` +
      activeInfo.user.email +
      `"></div>
                                                            </div>
                                                            <div class="col-lg-6 userManagementPassword">
                                                                <div class="form-group">
                                                                    <label class="form-label" lang="en">Password</label>
                                                                    <input type="password" id="accountPassword1" class="form-control"></div>
                                                            </div>
                                                            <div class="col-lg-6 userManagementPassword">
                                                                <div class="form-group">
                                                                    <label class="form-label" lang="en">Verify Password</label>
                                                                    <input type="password" id="accountPassword2" class="form-control"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
										</div>
										<!--/row-->
										<div class="row">
											` +
      activeTokens +
      passwordMessage +
      `
										</div>
										<!--/row-->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		`;
    $(".londerland-area").after(accountDiv);
    pageLoad();
  }
}
function userMenu(user) {
  $("body").attr("data-active-user-group-name", user.data.user.group);
  $("body").attr("data-active-user-group-id", user.data.user.groupID);
  var sideMenu = "";
  var menuList =
    '<li class="hidden-xs" onclick="toggleFullScreen();"><a class="waves-effect waves-light"> <i class="ti-fullscreen fullscreen-icon"></i></a></li>';
  var showDebug = activeInfo.settings.misc.debugArea
    ? '<li><a href="javascript:void(0)" onclick="toggleDebug();"><i class="mdi mdi-bug fa-fw"></i> <span lang="en">Debug Area</span></a></li>'
    : "";
  pageLoad();
  if (user.data.user.loggedin === true) {
    menuList +=
      `
			<li class="dropdown">
				<a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="javascript:void(0)"><img alt="" class="rounded-circle profile-image" src="` +
      user.data.user.image +
      `" width="36"><b class="hidden-xs">` +
      user.data.user.username +
      `</b></a>
				<ul class="dropdown-menu dropdown-user animated flipInY">
					<li>
						<div class="dw-user-box">
							<div class="u-img"><img alt="user" src="` +
      user.data.user.image +
      `"></div>
							<div class="u-text"><h4>` +
      user.data.user.username +
      `</h4><p class="text-muted">` +
      user.data.user.email +
      `</p><p class="text-muted">` +
      user.data.user.group +
      `</p></div>
						</div>
					</li>
					<li><hr class="dropdown-divider"></li>
					<li class="append-menu"><a class="inline-popups" href="#account-area" data-effect="mfp-zoom-out"><i class="ti-settings fa-fw"></i> <span lang="en">Account Settings</span></a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a href="javascript:void(0)" onclick="lock();"><i class="ti-lock fa-fw"></i> <span lang="en">Lock Screen</span></a></li>
					${showDebug}
					<li><a href="javascript:void(0)" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span lang="en">Logout</span></a></li>
				</ul><!-- /.dropdown-user -->
			</li><!-- /.dropdown -->
		`;
    sideMenu +=
      `
		<li class="user-pro">
			<a href="#" class="waves-effect">
				<img src="` +
      user.data.user.image +
      `" alt="user-img" class="rounded-circle">
				<span class="hide-menu">` +
      user.data.user.username +
      `<span class="fa arrow"></span></span>
			</a>
			<ul class="nav nav-second-level mm-collapse" aria-expanded="false">
				<li class="append-menu"><a class="inline-popups" href="#account-area" data-effect="mfp-zoom-out"><i class="ti-settings fa-fw"></i> <span lang="en">Account Settings</span></a></li>
				<li><a href="javascript:void(0)" onclick="lock();"><i class="ti-lock fa-fw"></i> <span lang="en">Lock Screen</span></a></li>
				${showDebug}
				<li><a href="javascript:void(0)" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span lang="en">Logout</span></a></li>
			</ul>
		</li>
		`;
  } else {
    menuList +=
      `
			<li class="dropdown">
					<a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="javascript:void(0)"><img alt="" class="rounded-circle profile-image" src="` +
      user.data.user.image +
      `" width="36"><b class="hidden-xs">` +
      user.data.user.username +
      `</b></a>
					<ul class="dropdown-menu dropdown-user animated flipInY">
						<li>
							<div class="dw-user-box">
								<div class="u-img"><img alt="user" src="` +
      user.data.user.image +
      `"></div>
								<div class="u-text"><h4>` +
      user.data.user.username +
      `</h4></div>
							</div>
						</li>
						<li><hr class="dropdown-divider"></li>
						<li class="append-menu"><a href="javascript:void(0)" class="show-login"><i class="fa fa-sign-in fa-fw"></i> <span lang="en">Login/Register</span></a></li>
					</ul><!-- /.dropdown-user -->
				</li><!-- /.dropdown -->
		`;
    sideMenu +=
      `
		<li class="user-pro">
			<a href="#" class="waves-effect">
				<img src="` +
      user.data.user.image +
      `" alt="user-img" class="rounded-circle">
				<span class="hide-menu">` +
      user.data.user.username +
      `<span class="fa arrow"></span></span>
			</a>
			<ul class="nav nav-second-level mm-collapse" aria-expanded="false">
				<li class="append-menu"><a href="javascript:void(0)" class="show-login"><i class="fa fa-sign-in fa-fw"></i> <span lang="en">Login/Register</span></a></li>
			</ul>
		</li>
		`;
  }
  $(menuList).appendTo(".navbar-right").html;
  //$(sideMenu).appendTo('#side-menu').html;
  //message("",window.lang.translate('Welcome')+" "+user.data.user.username,activeInfo.settings.notifications.position,"#FFF","success","3500");
  console.info(
    "%c " +
      window.lang.translate("Welcome") +
      " %c ".concat(user.data.user.username, " "),
    "color: white; background: #AD80FD; font-weight: 700;",
    "color: #AD80FD; background: white; font-weight: 700;"
  );
}
function menuExtraTabs() {
  return [];
}
function menuExtras(active) {
  let adminMenu = '<li class="devider"></li>';
  let extraLonderlandLinks = menuExtraTabs();
  activeInfo.tabs = [].concat(activeInfo.tabs, menuExtraTabs());

  $.each(extraLonderlandLinks, function (i, v) {
    tabInformation[v.id] = {
      id: v.id,
      name: cleanClass(v.name),
      active: false,
      loaded: false,
      increments: 0,
      tabInfo: v,
    };
    if (v.type == 1) {
      let frame = buildFrameContainer(v.id);
      $(frame).appendTo($(".iFrame-listing"));
    }
    adminMenu +=
      activeInfo.user.groupID <= v.group_id && v.active
        ? buildMenuList(v.id)
        : "";
  });
  if (active === true) {
    return activeInfo.settings.menuLink.londerlandSignoutMenuLink
      ? `
			<li class="devider"></li>
			<li id="sign-out"><a class="waves-effect" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span class="hide-menu" lang="en">Logout</span></a></li>
		` + adminMenu
      : "" + adminMenu;
  } else {
    return activeInfo.settings.menuLink.londerlandSignoutMenuLink
      ? `
			<li class="devider"></li>
			<li id="menu-login"><a class="waves-effect show-login" href="javascript:void(0)"><i class="mdi mdi-login fa-fw"></i> <span class="hide-menu" lang="en">Login/Register</span></a></li>
		`
      : "";
  }
}
function categoryProcess(arrayItems) {
  var menuList = "";
  // metisMenu 3 only knows its own classes: mm-collapse/mm-show on the list, mm-active on the open category
  let categoryIn = activeInfo.settings.misc.expandCategoriesByDefault
    ? "mm-show"
    : "";
  let categoryActive = activeInfo.settings.misc.expandCategoriesByDefault
    ? "active mm-active"
    : "";
  let categoryExpanded = activeInfo.settings.misc.expandCategoriesByDefault
    ? "true"
    : "false";
  if (
    Array.isArray(arrayItems["data"]["categories"]) &&
    Array.isArray(arrayItems["data"]["tabs"])
  ) {
    $.each(arrayItems["data"]["categories"], function (i, v) {
      if (v.count !== 0 && v.category_id !== 0) {
        menuList +=
          `
					<li class="allGroupsList ` +
          categoryActive +
          `" data-group-name="` +
          cleanClass(v.category) +
          `">
						<a class="waves-effect" href="javascript:void(0)">` +
          iconPrefix(v.image) +
          `<span class="hide-menu">` +
          v.category +
          ` <span class="fa arrow"></span></span><div class="menu-category-ping" data-good="0" data-bad="0"></div></a>
						<ul class="nav nav-second-level category-` +
          v.category_id +
          ` mm-collapse ` +
          categoryIn +
          `" aria-expanded="` +
          categoryExpanded +
          `"></ul>
					</li>
				`;
      }
    });
    $(menuList).appendTo($("#side-menu"));
  }
}
function buildFrame(id, split = null) {
  let extra = split ? "right-" : "";
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole("Build Frame", "No Tab Info Found... Id: " + id, "error");
    return false;
  }
  var sandbox = activeInfo.settings.misc.sandbox;
  sandbox = sandbox.replace(/,/gi, " ");
  sandbox = sandbox ? ' sandbox="' + sandbox + '"' : "";
  var allow = activeInfo.settings.misc.iframeAllow;
  allow = allow.replace(/,/gi, "; ");
  allow = allow ? ' allow="' + allow + '"' : "";
  return (
    `
		<iframe ` +
    allow +
    ` frameborder="0" id="frame-` +
    extra +
    id +
    `" ` +
    sandbox +
    ` scrolling="auto" src="` +
    tabInfo.access_url +
    `" class="iframe"></iframe>
	`
  );
}
function buildFrameContainer(id, split = null) {
  let extra = split ? "right-" : "";
  return (
    `<div id="container-` +
    extra +
    id +
    `" class="frame-container frame-${id} hidden" ></div>`
  );
}
function buildInternalContainer(id, split = null) {
  let extra = split ? "right-" : "";
  return (
    `<div id="internal-` +
    extra +
    id +
    `" class="internal-container frame-${id} hidden"></div>`
  );
}
function buildMenuList(id) {
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole(
      "Build Menu List",
      "No Tab Info Found... Id: " + id,
      "error"
    );
    return false;
  }
  let name = tabInfo.name;
  let icon = tabInfo.image;
  let ping = typeof tabInfo.ping_url !== "undefined" ? tabInfo.ping_url : null;
  ping =
    ping !== null && ping !== ""
      ? `<small class="menu-` +
        cleanClass(ping) +
        `-ping-ms hidden-xs badge rounded-pill text-bg-dark float-end pingTime hidden">
</small><div class="menu-` +
        cleanClass(ping) +
        `-ping" data-tab-name="` +
        name +
        `" data-previous-state=""></div>`
      : "";
  return (
    `<li class="allTabsList" id="menu-${id}" data-tab-id="${id}"><a class="waves-effect"  href="javascript:void(0)" onclick="tabActions(event,'${id}');" onauxclick="tabActions(event,'${id}');">` +
    iconPrefix(icon) +
    `<span class="hide-menu elip sidebar-tabName">` +
    name +
    `</span>` +
    ping +
    `</a></li>`
  );
}
function tabProcess(arrayItems) {
  var iFrameList = "";
  var internalList = "";
  var defaultTabId = null;
  if (
    Array.isArray(arrayItems["data"]["tabs"]) &&
    arrayItems["data"]["tabs"].length > 0
  ) {
    $.each(arrayItems["data"]["tabs"], function (i, v) {
      if (v.enabled === 1 && v.access_url) {
        tabInformation[v.id] = {
          id: v.id,
          name: cleanClass(v.name),
          active: false,
          loaded: false,
          increments: 0,
          tabInfo: v,
        };
        switch (v.timeout) {
          case 1:
          case "1":
            tabActionsList["close"].push({
              id: v.id,
              tab: cleanClass(v.name),
              action_ms: v.timeout_ms,
            });
            break;
          case 2:
          case "2":
            tabActionsList["refresh"].push({
              id: v.id,
              tab: cleanClass(v.name),
              action_ms: v.timeout_ms,
            });
            break;
          default:
          //nada
        }
        if (v.default === 1) {
          defaultTabId = v.id;
        }
        var menuList = buildMenuList(v.id);
        if (v.category_id === 0) {
          if (activeInfo.settings.misc.unsortedTabs === "top") {
            $(menuList).prependTo($("#side-menu"));
          } else if (activeInfo.settings.misc.unsortedTabs === "bottom") {
            $(menuList).appendTo($("#side-menu"));
          }
        } else {
          if (activeInfo.settings.misc.unsortedTabs === "top") {
            $(menuList).prependTo($(".category-" + v.category_id));
          } else if (activeInfo.settings.misc.unsortedTabs === "bottom") {
            $(menuList).appendTo($(".category-" + v.category_id));
          }
        }
        switch (v.type) {
          case 0:
          case "0":
          case "internal":
            internalList = buildInternalContainer(v.id);
            $(internalList).appendTo($(".internal-listing"));
            internalList = buildInternalContainer(v.id, true);
            $(internalList).appendTo($(".internal-listing-right"));
            if (v.preload) {
              var newTab = $("#internal-" + v.id);
              londerlandConsole(
                "Tab Function",
                "Preloading new tab for: " + cleanClass(v.name)
              );
              $("#menu-" + v.id + " a")
                .children()
                .addClass("tabLoaded");
              newTab.addClass("loaded");
              loadInternal(v.id);
            }
            break;
          case 1:
          case "1":
          case "iframe":
            iFrameList = buildFrameContainer(v.id);
            $(iFrameList).appendTo($(".iFrame-listing"));
            iFrameList = buildFrameContainer(v.id, true);
            $(iFrameList).appendTo($(".iFrame-listing-right"));
            if (v.preload) {
              var newTab = $("#container-" + v.id);
              londerlandConsole(
                "Tab Function",
                "Preloading new tab for: " + cleanClass(v.name)
              );
              $("#menu-" + v.id + " a")
                .children()
                .addClass("tabLoaded");
              newTab.addClass("loaded");
              $(buildFrame(v.id)).appendTo(newTab);
            }
            break;
          case 2:
          case 3:
          case "2":
          case "3":
          case "_blank":
          case "popout":
            break;
          default:
            londerlandConsole("Tab Function", "Action not set", "error");
        }
      }
    });
    $("#side-menu").metisMenu({
      toggle: activeInfo.settings.misc.autoCollapseCategories,
    });
    getDefault(defaultTabId);
  } else {
    noTabs(arrayItems);
  }
  $(menuExtras(arrayItems.data.user.loggedin)).appendTo($("#side-menu"));
}
function buildLogin() {
  swapDisplay("login");
  closeSideMenu();
  removeMenuActive();
  $("#menu-login a").addClass("active");
  londerlandAPI2("GET", "api/v2/page/login")
    .done(function (data) {
      try {
        var response = data.response;
        londerlandConsole("Londerland Function", "Opening Login Page");
        $(".login-area").html(response.data);
        setHash("LonderlandLogin");
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Login Error");
    });
  $("#preloader").fadeOut();
}
function buildLockscreen() {
  $("#preloader").fadeIn();
  closeSideMenu();
  londerlandAPI2("GET", "api/v2/page/lockscreen")
    .done(function (data) {
      try {
        var response = data.response;
        londerlandConsole("Londerland Function", "Adding Lockscreen");
        $(response.data).appendTo($("body"));
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
  $("#preloader").fadeOut();
}
function buildSplashScreenItem(arrayItems) {
  var splashList = "";
  if (
    Array.isArray(arrayItems["data"]["tabs"]) &&
    arrayItems["data"]["tabs"].length > 0
  ) {
    arrayItems["data"]["tabs"].sort(
      (a, b) => parseFloat(a.order) - parseFloat(b.order)
    );
    $.each(arrayItems["data"]["tabs"], function (i, v) {
      if (v.enabled === 1 && v.splash === 1 && v.access_url) {
        var image = iconPrefixSplash(v.image);
        if (image.indexOf(".") !== -1) {
          var dataSrc = 'data-src="' + iconPrefixSplash(v.image) + '"';
          var nonImage = "";
        } else {
          var dataSrc = "";
          var nonImage =
            '<span class="text-uppercase badge bg-org splash-badge">' +
            image +
            "</span>";
        }
        splashList += `
                <div class="col-12 col-md-3 col-lg-3 col-xl-3 col-xl-2 mouse hvr-grow m-b-20" id="menu-${cleanClass(
                  v.name
                )}" type="${v.type}" data-url="${
          v.access_url
        }" onclick="tabActions(event,'${v.id}');">
                    <div class="homepage-drag fc-event bg-org lazyload" ${dataSrc}>
                        ${nonImage}
                        <span class="homepage-text">&nbsp; ${v.name}</span>
                    </div>
                </div>
                `;
      }
    });
  }
  return splashList !== "" ? splashList : false;
}
function buildSplashScreen(json) {
  let hiddenSplash = directToHash ? "hidden" : "show";
  var items = buildSplashScreenItem(json);
  var menu =
    '<li ><a href="javascript:void(0)" onclick="$(\'.splash-screen\').removeClass(\'hidden\').addClass(\'show\')"><i class="ti-layout-grid2 fa-fw"></i> <span lang="en">Splash Page</span></a></li>';
  if (items) {
    closeSideMenu();
    londerlandConsole("Londerland Function", "Adding Splash Screen");
    var splash =
      `
        <section id="splashScreen" class="lock-screen splash-screen default-scroller fade ${hiddenSplash}">
            <div class="row p-20 flexbox">` +
      items +
      `</div>
            <div class="row p-20 p-t-0 flexbox">
                <div class="col-12 col-md-12 col-lg-12 col-xl-12 col-xl-12 mouse hvr-wobble-bottom bottom-close-splash" onclick="$('.splash-screen').addClass('hidden').removeClass('show')">
                    <div class="homepage-drag fc-event bg-danger lazyload"  data-src="">
                        <span class="homepage-text">&nbsp; Close Splash</span>
                    </div>
                </div>
            </div>
        </section>
        `;
    $(splash).appendTo($("body"));
    $(".append-menu").after(menu);
  }
}
function buildUserGroupSelect(array, userID, groupID) {
  var groupSelect = "";
  var selected = "";
  var disabled = "";
  if (groupID == 0 && userID == 1) {
    disabled = "disabled";
  }
  $.each(array, function (i, v) {
    selected = "";
    if (v.group_id == groupID) {
      selected = "selected";
    }
    var selectDisable = v.group_id == 0 || v.group_id == 999 ? "disabled" : "";
    groupSelect +=
      "<option " +
      selected +
      " " +
      selectDisable +
      ' value="' +
      v.group_id +
      '">' +
      v.group +
      "</option>";
  });
  return (
    '<td><select name="userGroupSelect" class="form-control userGroupSelect" ' +
    disabled +
    ">" +
    groupSelect +
    "</select></td>"
  );
}
function buildTabGroupSelect(array, tabID, groupID, type) {
  var groupSelect = "";
  var selected = "";
  let name = type == "tabGroupSelectMax" ? "group_id_max" : "group_id";
  $.each(array, function (i, v) {
    selected = "";
    if (v.group_id == groupID) {
      selected = "selected";
    }
    groupSelect +=
      "<option " +
      selected +
      ' value="' +
      v.group_id +
      '">' +
      v.group +
      "</option>";
  });
  return (
    '<td><select name="tab[' +
    tabID +
    "]." +
    name +
    '" class="form-control ' +
    type +
    '">' +
    groupSelect +
    "</select></td>"
  );
}
function buildTabTypeSelect(tabID, typeID, disabled) {
  var array = [
    {
      type_id: 0,
      type: "Londerland",
    },
    {
      type_id: 1,
      type: "iFrame",
    },
    {
      type_id: 2,
      type: "New Window",
    },
  ];
  var typeSelect = "";
  var selected = "";
  disabled = disabled == "disabled" && typeID !== 0 ? null : disabled;
  $.each(array, function (i, v) {
    selected = "";
    if (v.type_id == typeID) {
      selected = "selected";
    }
    var disabledAttr =
      disabled === "disabled" && v.type !== "Internal" ? "disabled" : "";
    typeSelect +=
      "<option " +
      selected +
      ' value="' +
      v.type_id +
      '" ' +
      disabledAttr +
      ">" +
      v.type +
      "</option>";
  });
  return (
    '<td><select name="tab[' +
    tabID +
    '].type" class="form-control tabTypeSelect">' +
    typeSelect +
    "</select></td>"
  );
}
function buildTabCategorySelect(array, tabID, categoryID) {
  var categorySelect = "";
  var selected = "";
  $.each(array, function (i, v) {
    selected = "";
    if (v.category_id == categoryID) {
      selected = "selected";
    }
    categorySelect +=
      "<option " +
      selected +
      ' value="' +
      v.category_id +
      '">' +
      v.category +
      "</option>";
  });
  return (
    '<td><select name="tab[' +
    tabID +
    '].category_id" class="form-control tabCategorySelect">' +
    categorySelect +
    "</select></td>"
  );
}
function buildUserManagementItem(array) {
  var userList = "";
  $.each(array.users, function (i, v) {
    var disabledDelete =
      v.group_id == 999 || v.group_id == 0 ? "disabled" : "deleteUser";
    userList +=
      `
		<tr class="userManagement" data-id="` +
      v.id +
      `" data-username="` +
      v.username +
      `" data-group="` +
      v.group +
      `" data-email="` +
      v.email +
      `">
			<td class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<img alt="user-img" class="rounded-circle" src="` +
      v.image +
      `" width="45">
						<div class="el-overlay">
							<ul class="el-info">
								` +
      v.id +
      `
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>` +
      v.username +
      `
				<br/><span class="text-muted">` +
      v.email +
      `</span></td>
			<td>` +
      moment(v.register_date).format("ll") +
      `
				<br/><span class="text-muted">` +
      moment(v.register_date).format("LT") +
      `</span></td>
			` +
      buildUserGroupSelect(array.groups, v.id, v.group_id) +
      `
			<td><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editUserButton popup-with-form" href="#edit-user-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-20 emailUser"><i class="ti-email"></i></button></td>
			<td><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 ` +
      disabledDelete +
      `"><i class="ti-trash"></i></button></td>
		</tr>
		`;
  });
  return userList;
}
function buildGroupManagementItem(array) {
  var userList = "";
  $.each(array.groups, function (i, v) {
    var userCount = array.users.reduce(function (n, group) {
      return n + (group.group_id == v.group_id);
    }, 0);
    var disabledDefault =
      v.group_id == 0 || v.group_id == 999 ? "disabled" : "";
    var disabledDelete =
      userCount > 0 || v.default == 1 || v.group_id == 999 || v.group_id <= 1
        ? "disabled"
        : "";
    var defaultIcon =
      v.default == 1 ? "icon-user-following" : "icon-user-follow";
    var defaultColor = v.default == 1 ? "btn-info disabled" : "btn-warning";
    userList +=
      `
		<tr class="userManagement" data-id="` +
      v.id +
      `" data-group-id="` +
      v.group_id +
      `" data-group="` +
      v.group +
      `" data-default="` +
      tof(v.default) +
      `" data-image="` +
      v.image +
      `" data-user-count="` +
      userCount +
      `">
			<td class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="tabEditorIcon">` +
      iconPrefix(v.image) +
      `</div>
						<div class="el-overlay">
							<ul class="el-info">
								` +
      v.group_id +
      `
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>` +
      v.group +
      `</td>
			<td>` +
      userCount +
      `</td>
			<td><button type="button" class="btn ` +
      defaultColor +
      ` btn-outline btn-circle btn-lg m-r-5 changeDefaultGroup" ` +
      disabledDefault +
      `><i class="` +
      defaultIcon +
      `"></i></button></td>
			<td><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editGroupButton popup-with-form" href="#edit-group-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 deleteUserGroup" ` +
      disabledDelete +
      `><i class="ti-trash"></i></button></td>
		</tr>
		`;
  });
  return userList;
}
function buildCategoryEditorItem(array) {
  var categoryList = "";
  $.each(array.categories, function (i, v) {
    var tabCount = array.tabs.reduce(function (n, category) {
      return n + (category.category_id == v.category_id);
    }, 0);
    var disabledDefault = v.default == 1 ? "disabled" : "";
    var disabledDelete =
      tabCount > 0 || v.default == 1 || v.category_id == 0 ? "disabled" : "";
    var defaultIcon =
      v.default == 1 ? "icon-user-following" : "icon-user-follow";
    var defaultColor = v.default == 1 ? "btn-info disabled" : "btn-warning";
    categoryList +=
      `
		<tr class="categoryEditor" data-id="` +
      v.id +
      `" data-order="` +
      v.order +
      `" data-category-id="` +
      v.category_id +
      `" data-name="` +
      v.category +
      `" data-default="` +
      tof(v.default) +
      `" data-image="` +
      v.image +
      `" data-tab-count="` +
      tabCount +
      `">
			<input type="hidden" class="form-control order" name="category[` +
      v.id +
      `].order" value="` +
      v.order +
      `">
			<input type="hidden" class="form-control" name="category[` +
      v.id +
      `].originalOrder" value="` +
      v.order +
      `">
			<input type="hidden" class="form-control" name="category[` +
      v.id +
      `].name" value="` +
      v.category +
      `">
			<input type="hidden" class="form-control" name="category[` +
      v.id +
      `].id" value="` +
      v.id +
      `">
			<td class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="tabEditorIcon">` +
      iconPrefix(v.image) +
      `</div>
						<div class="el-overlay bg-org">
							<ul class="el-info">
								<i class="fa fa-bars"></i>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>` +
      v.category +
      `</td>
			<td style="text-align:center">` +
      tabCount +
      `</td>
			<td style="text-align:center"><button type="button" class="btn ` +
      defaultColor +
      ` btn-outline btn-circle btn-lg m-r-5 changeDefaultCategory" ` +
      disabledDefault +
      `><i class="` +
      defaultIcon +
      `"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editCategoryButton popup-with-form" href="#edit-category-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 deleteCategory" ` +
      disabledDelete +
      `><i class="ti-trash"></i></button></td>
		</tr>
		`;
  });
  return categoryList;
}
function buildTabEditorItem(array) {
  var tabList = "";
  $.each(array.tabs, function (i, v) {
    let deleteDisabled = "deleteTab";
    let buttonDisabled = "";
    let typeDisabled = "";
    if (v.url !== null) {
      deleteDisabled =
        v.url.indexOf("/page/settings") > 0 ? "disabled" : "deleteTab";
      buttonDisabled = v.url.indexOf("/page/settings") > 0 ? "disabled" : "";
      typeDisabled = v.url.indexOf("/v2/page/") > 0 ? "disabled" : "";
    }
    tabList +=
      `
		<tr class="tabEditor" data-order="` +
      v.order +
      `" data-original-order="` +
      v.order +
      `" data-id="` +
      v.id +
      `" data-group-id="` +
      v.group_id +
      `" data-category-id="` +
      v.category_id +
      `" data-name="` +
      v.name +
      `" data-url="` +
      v.url +
      `" data-local-url="` +
      v.url_local +
      `" data-ping-url="` +
      v.ping_url +
      `" data-image="` +
      v.image +
      `" data-tab-action-type="` +
      v.timeout +
      `" data-tab-action-time="` +
      v.timeout_ms +
      `">
			<input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].id" value="` +
      v.id +
      `">
			<input type="hidden" class="form-control order" name="tab[` +
      v.id +
      `].order" value="` +
      v.order +
      `">
			<input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].originalOrder" value="` +
      v.order +
      `">
			<td class="mouse-grab sort-tabs-handle">
				<i class="icon-options-vertical m-r-5"></i> 
				<!-- May use later on
				<div class="btn-group dropside visible-xs">
					<button aria-expanded="false" data-bs-toggle="dropdown" class="btn btn-secondary btn-outline dropdown-toggle waves-effect waves-light" type="button"> <i class="icon-options-vertical m-r-5"></i></button>
					<ul role="menu" class="dropdown-menu">
						<li><a href="#"><i class="fa fa-angle-double-up"></i></a></li>
						<li><a href="#"><i class="fa fa-angle-up"></i></a></li>
						<li><a href="#"><i class="fa fa-angle-double-down"></i></a></li>
						<li><a href="#"><i class="fa fa-angle-down"></i></a></li>
					</ul>
				</div>
				-->
			</td>
			<td style="text-align:center" class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0 tooltip-info" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-title="${v.id}">
						<div class="tabEditorIcon">` +
      iconPrefix(v.image) +
      `</div>
					</div>
				</div>
			</td>
			<td><span class="tooltip-info" data-bs-toggle="tooltip" data-bs-placement="right" title="" data-bs-title="` +
      v.url +
      `">` +
      v.name +
      `</span></td>
			` +
      buildTabCategorySelect(array.categories, v.id, v.category_id) +
      `
			` +
      buildTabGroupSelect(array.groups, v.id, v.group_id, "tabGroupSelectMin") +
      `
			` +
      buildTabGroupSelect(
        array.groups,
        v.id,
        v.group_id_max,
        "tabGroupSelectMax"
      ) +
      `
			` +
      buildTabTypeSelect(v.id, v.type, typeDisabled) +
      `
			<td style="text-align:center"><div class="radio radio-purple"><input onclick="radioLoop(this);" type="radio" class="defaultSwitch" id="tab[` +
      v.id +
      `].default" name="tab[` +
      v.id +
      `].default" value="true" ` +
      tof(v.default, "c") +
      `><label for="tab[` +
      v.id +
      `].default"></label></div></td>

			<td style="text-align:center"><input ` +
      buttonDisabled +
      ` type="checkbox" class="js-switch enabledSwitch ` +
      buttonDisabled +
      `" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[` +
      v.id +
      `].enabled" value="true" ` +
      tof(v.enabled, "c") +
      `/><input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].enabled" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch splashSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[` +
      v.id +
      `].splash" value="true" ` +
      tof(v.splash, "c") +
      `/><input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].splash" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch pingSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[` +
      v.id +
      `].ping" value="true" ` +
      tof(v.ping, "c") +
      `/><input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].ping" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch preloadSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[` +
      v.id +
      `].preload" value="true" ` +
      tof(v.preload, "c") +
      `/><input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].preload" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch addToAdminSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[` +
      v.id +
      `].add_to_admin" value="true" ` +
      tof(v.add_to_admin, "c") +
      `/><input type="hidden" class="form-control" name="tab[` +
      v.id +
      `].add_to_admin" value="false"></td>
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editTabButton popup-with-form" onclick="editTabForm('` +
      v.id +
      `')" href="#edit-tab-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 ` +
      deleteDisabled +
      `"><i class="ti-trash"></i></button></td>
		</tr>
		`;
  });
  return tabList;
}
function editTabForm(id) {
  londerlandAPI2("GET", "api/v2/tabs/" + id, true)
    .done(function (data) {
      try {
        let response = data.response;
        clearSelect(".tabIconImageList, .tabIconIconList");
        $("#edit-tab-form [name=name]").val(response.data.name);
        $("#originalTabName").html(response.data.name);
        $("#edit-tab-form [name=url]").val(response.data.url);
        $("#edit-tab-form [name=url_local]").val(response.data.url_local);
        $("#edit-tab-form [name=ping_url]").val(response.data.ping_url);
        $("#edit-tab-form [name=image]").val(response.data.image);
        $("#edit-tab-form [name=id]").val(response.data.id);
        $("#edit-tab-form [name=timeout_ms]").val(
          convertMsToMinutes(response.data.timeout_ms)
        );
        $("#edit-tab-form [name=timeout]").val(response.data.timeout);
        if (response.data.url.indexOf("/?v") > 0) {
          $("#edit-tab-form [name=url]").prop("disabled", "true");
        } else {
          $("#edit-tab-form [name=url]").prop("disabled", null);
        }
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Tab Error");
    });
}
function getSubmitSettingsFormValueSingle(form, index, value) {
  var values = {};
  if (value !== "#987654" && index.includes("disable-pwd-mgr") == false) {
    var input = $("#" + form + " [name='" + index + "']");
    var dataType = input.attr("data-type");
    switch (dataType) {
      case "switch":
      case "checkbox":
        var value = input.prop("checked") ? true : false;
        break;
      case "select2":
        var value = input.val() !== null ? input.val().toString() : "";
        break;
      default:
        var value = input.val();
    }
    values = { name: index, value: value, type: dataType };
    return values;
  }
  return false;
}
function getSubmitSettingsFormValueObject(form, index, value) {
  var values = [];
  $.each(value, function (i, v) {
    var objectList = [];
    var object = [];
    $.each(v, function (key, val) {
      if (val !== "#987654" && key.includes("disable-pwd-mgr") == false) {
        var input = $(
          "#" + form + " [name='" + index + "[" + i + "]." + key + "']"
        );
        var dataType = input.attr("data-type");
        var dataLabel = input.attr("data-label");
        switch (dataType) {
          case "switch":
          case "checkbox":
            var value = input.prop("checked") ? true : false;
            break;
          case "select2":
            var value = input.val() !== null ? input.val().toString() : "";
            break;
          default:
            var value = input.val();
        }
        var newKey = index + "[" + i + "]." + key;
        object.push({
          type: dataType,
          name: newKey,
          label: dataLabel,
          value: value,
        });
      }
    });
    values.push(object);
  });
  values = { name: index, value: values, type: "array" };
  return values;
}
function submitSettingsForm(form) {
  var list = $("#" + form).serializeToJSON();
  var size = 0;
  var submit = {};
  $.each(list, function (i, v) {
    var values = false;

    if (Object.prototype.toString.call(v) === "[object Object]") {
      values = getSubmitSettingsFormValueObject(form, i, v);
    } else {
      values = getSubmitSettingsFormValueSingle(form, i, v);
    }
    size++;
    if (values) {
      submit[i] = values.value;
    }
  });
  var callbacks = $.Callbacks();
  // Custom Callbacks
  switch (form) {
    case "customize-appearance-form":
      break;
    default:
  }
  if (size > 0) {
    londerlandAPI2("PUT", "api/v2/config", submit, true)
      .done(function (data) {
        try {
          var response = data.response;
        } catch (e) {
          londerlandCatchError(e, data);
        }
        if (callbacks) {
          callbacks.fire();
        }
          message(
            "Updated Items",
            response.message,
            activeInfo.settings.notifications.position,
            "#FFF",
            "success",
            "5000"
          );
      })
      .fail(function (xhr) {
        LonderlandApiError(xhr, "Update Error");
      });
    $("#" + form + " :input").each(function () {
      var input = $(this);
      input
        .closest(".form-group")
        .removeClass("has-success")
        .removeClass("has-error");
    });
    $("#" + form + "-save").addClass("hidden");
  } else {
    $("#" + form + " :input").each(function () {
      var input = $(this);
      input
        .closest(".form-group")
        .removeClass("has-success")
        .addClass("has-error");
    });
  }
}
function submitTabOrder(newTabs) {
  var data = [];
  var process = false;
  $.each(newTabs.tab, function (i, v) {
    if (v.originalOrder == v.order) {
      delete newTabs.tab[i];
    } else {
      let temp = {
        order: v.order,
        id: v.id,
      };
      data.push(temp);
      process = true;
    }
  });
  if (!process) {
    message(
      "Tab Order Warning",
      "Order was not changed - Submission not needed",
      activeInfo.settings.notifications.position,
      "#FFF",
      "warning",
      "5000"
    );
    $(".saveTabOrderButton").addClass("hidden");
    return false;
  }
  var callbacks = $.Callbacks();
  callbacks.add(buildTabEditor);
  londerlandAPI2("PUT", "api/v2/tabs", data, true)
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      message(
        "Tab Order Updated",
        response.message,
        activeInfo.settings.notifications.position,
        "#FFF",
        "success",
        "5000"
      );
      if (callbacks) {
        callbacks.fire();
      }
      $(".saveTabOrderButton").addClass("hidden");
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Update Error");
    });
}
function submitCategoryOrder() {
  var data = [];
  var categories = $("#submit-categories-form").serializeToJSON();
  var callbacks = $.Callbacks();
  callbacks.add(buildCategoryEditor);
  $.each(categories.category, function (i, v) {
    if (v.originalOrder == v.order) {
      delete categories.category[i];
    } else {
      let temp = {
        order: v.order,
        id: v.id,
      };
      data.push(temp);
    }
  });
  londerlandAPI2("PUT", "api/v2/categories", data, true)
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      message(
        "Category Order Updated",
        response.message,
        activeInfo.settings.notifications.position,
        "#FFF",
        "success",
        "5000"
      );
      if (callbacks) {
        callbacks.fire();
      }
      $(".saveTabOrderButton").addClass("hidden");
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Update Error");
    });
}
function getLonderlandBackups() {
  londerlandAPI2("GET", "api/v2/backup")
    .done(function (data) {
      try {
        let json = data.response;
        $("#backup-file-list").html(buildLonderlandBackups(json.data));
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function createLonderlandBackup() {
  $("#settings-settings-backup").block({
    message:
      '<p style="margin:0;padding:8px;font-size:24px;" lang="en">Backing up...</p>',
    css: {
      color: "#fff",
      border: "1px solid #5761a9",
      backgroundColor: "#707cd2",
    },
  });
  londerlandAPI2("POST", "api/v2/backup", {})
    .done(function (data) {
      try {
        let response = data.response;
        if (response) {
          getLonderlandBackups();
        }
      } catch (e) {
        londerlandCatchError(e, data);
      }
      $("#settings-settings-backup").unblock();
    })
    .fail(function (xhr) {
      $("#settings-settings-backup").unblock();
      LonderlandApiError(xhr, "Backup Error");
    });
}
function buildLonderlandBackups(array) {
  let list = "";
  if (array.total_files > 0) {
    $.each(array.files, function (i, v) {
      i++;
      let pattern = /\[[^\]]*\]/gm;
      let version =
        typeof v.name.match(pattern)[1] !== "undefined"
          ? v.name.match(pattern)[1]
          : "N/A";
      list += `
			<tr>
				<td>${i}</td>
				<td class="txt-oflo">${v.name}</td>
				<td><span class="badge text-bg-primary rounded-pill">${version}</span> </td>
				<td class="txt-oflo">${v.size}</td>
				<td><span class="text-info tooltip-info" data-bs-toggle="tooltip" data-bs-placement="right" title="" data-bs-title="${moment(
          v.date
        ).format("LLL")}">${moment
        .utc(v.date, "YYYY-MM-DD hh:mm[Z]")
        .local()
        .fromNow()}</span></td>
				<td><span class="text-primary"><a href="api/v2/backup/${
          v.name
        }"><i class="fa fa-download download-backup" data-file="${
        v.name
      }"></i></a> | <a href="javascript:void(0)"><i class="fa fa-trash-o delete-backup" data-file="${
        v.name
      }"></i></a></span></td>
			</tr>
			`;
    });
  } else {
    list =
      '<tr><td class="text-center" colspan="6">No Backups made yet</td></tr>';
  }
  $("#backup-total-files").html(array.total_files);
  $("#backup-total-size").html(array.total_size);
  return list;
}
$.xhrPool.abortAll = function (url) {
  $(this).each(function (i, jqXHR) {
    //  cycle through list of recorded connection
    if (!url || url === jqXHR.requestURL) {
      londerlandConsole("Londerland API Abort", jqXHR.requestURL, "info");
      jqXHR.abort(); //  aborts connection
      $.xhrPool.splice(i, 1); //  removes from list by index
    }
  });
};
$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
  //londerlandConsole('Londerland API Function',options.url,'info');
  jqXHR.requestURL = options.url;
});
function londerlandAPI2(type, path, data = null, asyncValue = true) {
  $.xhrPool.abortAll(path);
  var timeout = 10000;
  switch (path) {
    case "api/v2/update/windows":
    case "api/v2/update/docker":
    case "api/v2/login":
      timeout = 240000;
      break;
    default:
      timeout = 60000;
  }
  switch (type) {
    case "get":
    case "GET":
    case "g":
      return $.ajax({
        url: path,
        method: "GET",
        beforeSend: function (request) {
          request.setRequestHeader("Token", activeInfo.token);
          request.setRequestHeader("formKey", local("g", "formKey"));
          $.xhrPool.push(request);
        },
        complete: function (jqXHR) {
          var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
          if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
        },
        timeout: timeout,
      });
    case "delete":
    case "DELETE":
    case "d":
      return $.ajax({
        url: path,
        method: "DELETE",
        beforeSend: function (request) {
          request.setRequestHeader("Token", activeInfo.token);
          request.setRequestHeader("formKey", local("g", "formKey"));
          $.xhrPool.push(request);
        },
        complete: function (jqXHR) {
          var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
          if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
        },
        timeout: timeout,
      });
    case "post":
    case "POST":
    case "p":
      data.formKey = local("g", "formKey");
      return $.ajax({
        url: path,
        method: "POST",
        async: asyncValue,
        beforeSend: function (request) {
          request.setRequestHeader("Token", activeInfo.token);
          request.setRequestHeader("formKey", local("g", "formKey"));
          $.xhrPool.push(request);
        },
        complete: function (jqXHR) {
          var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
          if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
        },
        data: data,
      });
    case "put":
    case "PUT":
      data.formKey = local("g", "formKey");
      return $.ajax({
        url: path,
        method: "PUT",
        async: asyncValue,
        beforeSend: function (request) {
          request.setRequestHeader("Token", activeInfo.token);
          request.setRequestHeader("formKey", local("g", "formKey"));
          $.xhrPool.push(request);
        },
        complete: function (jqXHR) {
          var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
          if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
        },
        data: JSON.stringify(data),
        contentType: "application/json",
      });
    default:
      console.warn("Londerland API: Method Not Supported");
  }
}
function loadSettingsPage2(api, element, londerlandFn) {
  $(element).html(
    '<h2 class="col-xl-12 m-t-0 text-center card card-body bg-org"><i class="fa fa-spin fa-refresh"></i><br> <span lang="en">Loading</span></h2><div class="clearfix"></div>'
  );
  londerlandAPI2("get", api)
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      londerlandConsole("Londerland Function", "Loading " + londerlandFn);
      $(element).html(response.data);
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function loadInternal(id, split = null) {
  let extra = split ? "right-" : "";
  let tabInfo = findTab(id);
  if (!tabInfo) {
    londerlandConsole("Load Internal", "No Tab Info Found... Id: " + id, "error");
    return false;
  }
  let url = tabInfo.access_url;
  londerlandAPI2("get", url)
    .done(function (data) {
      try {
        var html = data.response;
        $("#internal-" + extra + id).html(html.data);
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function londerlandAPI(type, path, data = null, asyncValue = true) {
  var timeout = 10000;
  switch (path) {
    case "api/?v1/windows/update":
    case "api/?v1/docker/update":
      timeout = 120000;
      break;
    default:
      timeout = 60000;
  }
  switch (type) {
    case "get":
    case "GET":
    case "g":
      return $.ajax({
        url: path,
        method: "GET",
        beforeSend: function (request) {
          request.setRequestHeader("Token", activeInfo.token);
          request.setRequestHeader("formKey", local("g", "formKey"));
        },
        timeout: timeout,
      });
    case "post":
    case "POST":
    case "p":
      data.formKey = local("g", "formKey");
      return $.ajax({
        url: path,
        method: "POST",
        async: asyncValue,
        beforeSend: function (request) {
          request.setRequestHeader("Token", activeInfo.token);
          request.setRequestHeader("formKey", local("g", "formKey"));
        },
        data: {
          data: data,
        },
      });
    default:
      console.warn("Londerland API: Method Not Supported");
  }
}
function londerlandConnect(path) {
  return $.ajax({
    url: path,
  });
}
function changeSettingsMenu(path) {
  var menuItems = path.split("::");
  var menu = "";
  if (Array.isArray(menuItems)) {
    $.each(menuItems, function (i, v) {
      menu += '<li><a lang="en">' + v + "</a></li>";
    });
  }
  $("#settingsBreadcrumb").html(menu);
}
function buildWizard() {
  londerlandAPI2("GET", "api/v2/page/wizard")
    .done(function (data) {
      try {
        var json = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      londerlandConsole("Londerland Function", "Starting Install Wizard");
      $(json.data).appendTo($(".londerland-area"));
      $(".londerland-area").removeClass("hidden");
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Wiizard Error");
    });
  $("#preloader").fadeOut();
}
function buildDependencyCheck(orgdata) {
  londerlandAPI2("GET", "api/v2/page/dependencies")
    .done(function (data) {
      try {
        var json = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      londerlandConsole("Londerland Function", "Starting Dependencies Check");
      $(json.data).appendTo($(".londerland-area"));
      $(".londerland-area").removeClass("hidden");
      $(buildBrowserInfo()).appendTo($("#browser-info"));
      $("#web-folder").html(buildWebFolder(orgdata));
      $("#php-version-check").html(buildPHPCheck(orgdata));
      $(buildDependencyInfo(orgdata)).appendTo($("#depenency-info"));
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Dependency Error");
    });
  $("#preloader").fadeOut();
}
function buildDependencyInfo(arrayItems) {
  let listing = "";
  $.each(arrayItems.data.status.dependenciesActive, function (i, v) {
    listing +=
      '<li class="depenency-item" data-name="' +
      v +
      '"><a href="javascript:void(0)"><i class="fa fa-check text-success"></i> ' +
      v +
      "</a></li>";
  });
  $.each(arrayItems.data.status.dependenciesInactive, function (i, v) {
    listing +=
      '<li class="depenency-item" data-name="' +
      v +
      '"><a href="javascript:void(0)"><i class="fa fa-close text-danger"><div class="notify"><span class="heartbit depend-heartbit"></span></div></i> ' +
      v +
      "</a></li>";
  });

  let className =
    arrayItems.data.status.dependenciesInactive.length !== 0
      ? "bg-danger text-warning"
      : "bg-primary";
  let icon =
    arrayItems.data.status.dependenciesInactive.length !== 0
      ? "fa fa-exclamation-triangle"
      : "fa fa-check-circle"; //dependency-dependencies-check-listing-header
  let header =
    arrayItems.data.status.dependenciesInactive.length !== 0
      ? "card-danger"
      : "card-info";
  let listingIcon =
    arrayItems.data.status.dependenciesInactive.length !== 0
      ? "ti-alert"
      : "ti-check-box";
  let listingText =
    arrayItems.data.status.dependenciesInactive.length !== 0
      ? "Dependencies Missing"
      : "Dependencies OK";

  $(".dependency-dependencies-check-listing-header")
    .removeClass("card-danger")
    .addClass(header);
  $(".dependency-dependencies-check-listing i")
    .first()
    .removeClass("ti-alert")
    .addClass(listingIcon);
  $(".dependency-dependencies-check-listing span").text(listingText);
  $(".dependency-dependencies-check")
    .removeClass("bg-warning")
    .addClass(className);
  $(".dependency-dependencies-check i")
    .removeClass("fa fa-spin fa-spinner")
    .addClass(icon);
  return listing;
}
function buildWebFolder(arrayItems) {
  let writable = "Not Writable - Please fix permissions";
  let className = "bg-danger text-warning";
  let icon = "fa fa-exclamation-triangle";
  if (arrayItems.data.status.writable == "yes") {
    writable = "Writable - All Good";
    className = "bg-primary";
    icon = "fa fa-check-circle";
  }
  $(".dependency-permissions-check")
    .removeClass("bg-warning")
    .addClass(className);
  $(".dependency-permissions-check i")
    .removeClass("fa fa-spin fa-spinner")
    .addClass(icon);
  $("#web-folder").addClass(className);
  return writable;
}
function buildPHPCheck(arrayItems) {
  let phpTest = "Upgrade PHP Version to 7.2+";
  let className = "bg-danger text-warning";
  let icon = "fa fa-exclamation-triangle";
  if (arrayItems.data.status.minVersion == "yes") {
    phpTest = "PHP Version Approved";
    className = "bg-primary";
    icon = "fa fa-check-circle";
  }
  $(".dependency-phpversion-check")
    .removeClass("bg-warning")
    .addClass(className);
  $(".dependency-phpversion-check i")
    .removeClass("fa fa-spin fa-spinner")
    .addClass(icon);
  $("#php-version-check").addClass(className);
  $("#php-version-check-user").html(
    '<span lang="en">Webserver User</span>: ' + arrayItems.data.status.php_user
  );
  return phpTest;
}
function buildBrowserInfo() {
  var listing = "";
  $.each(activeInfo, function (i, v) {
    listing +=
      `
		<tr>
			<td>` +
      i +
      `</td>
			<td>` +
      tof(v) +
      `</td>
		</tr>
		`;
  });
  return (
    `
	<table class="table table-hover">
		<tbody>
			` +
    listing +
    `
		</tbody>
	</table>
	`
  );
}
function tof(string, type) {
  var result;
  if (
    typeof string == "undefined" ||
    string == "false" ||
    string == false ||
    string == null ||
    string == 0 ||
    string == "off" ||
    string == "no"
  ) {
    result = "0";
  } else if (
    string == "true" ||
    string == true ||
    string == 1 ||
    string == "on" ||
    string == "yes"
  ) {
    result = "1";
  }
  switch (type) {
    case "bool":
    case "b":
      return result == "0" ? false : result == "1" ? true : string;
    case "switch":
    case "s":
      return result == "0" ? "off" : result == "1" ? "on" : string;
    case "checkbox":
    case "c":
      return result == "0" ? "" : result == "1" ? "checked" : string;
    case "integer":
    case "number":
    case "i":
    case "n":
      return result == "0" ? 0 : result == "1" ? 1 : string;
    case "question":
    case "q":
      return result == "0" ? "yes" : result == "1" ? "no" : string;
    case "string":
      return string.toString();
    default:
      return result == "0" ? "false" : result == "1" ? "true" : string;
  }
}
function createRandomString(length) {
  var str = "";
  for (; str.length < length; str += Math.random().toString(36).substr(2));
  return str.substr(0, length);
}
function generateAPI() {
  var string = createRandomString(20);
  $("#form-api").focus();
  $("#form-api").val(string);
  $("#form-api").focusout();
  $("#verify-api").text(string);
  $("#form-username").focus();
}
function getCookie(cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(";");
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == " ") {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
function localStorageSupport() {
  return "localStorage" in window && window["localStorage"] !== null;
}
function local(type, key, value = null) {
  if (localStorageSupport) {
    switch (type) {
      case "set":
      case "s":
        localStorage.setItem(key, value);
        break;
      case "get":
      case "g":
        return localStorage.getItem(key);
      case "remove":
      case "r":
        localStorage.removeItem(key);
        break;
      default:
        console.warn("Londerland Function: localStorage action not defined");
    }
  }
}
function language(language) {
  var language = language.split("-");
  return language[0];
}
function logIcon(type, label = false) {
  type = type.toLowerCase();
  let info = { color: "info", icon: "fa fa-check" };
  switch (type) {
    case "success":
      info.color = "info";
      info.icon = "fa fa-check";
      break;
    case "info":
      info.color = "info";
      info.icon = "mdi mdi-information";
      break;
    case "notice":
      info.color = "dark";
      info.icon = "mdi mdi-information-variant";
      break;
    case "debug":
      info.color = "primary";
      info.icon = "mdi mdi-code-tags-check";
      break;
    case "warning":
      info.color = "warning";
      info.icon = "mdi mdi-alert-box";
      break;
    case "error":
      info.color = "danger";
      info.icon = "mdi mdi-alert-outline";
      break;
    case "critical":
      info.color = "danger";
      info.icon = "mdi mdi-alert";
      break;
    case "alert":
      info.color = "danger";
      info.icon = "mdi mdi-alert-octagon";
      break;
    case "emergency":
      info.color = "danger";
      info.icon = "mdi mdi-alert-octagram";
      break;
    default:
      info = { color: "info", icon: "fa fa-check" };
      break;
  }
  if (label) {
    return (
      '<span class="badge text-bg-' +
      info.color +
      ' log-label"> <i class="fa ' +
      info.icon +
      ' m-l-5 fa-fw"></i>&nbsp; <span lang="en" class="text-uppercase">' +
      type +
      "</span></span>"
    );
  } else {
    return (
      '<button class="btn btn-sm btn-' +
      info.color +
      ' log-label no-mouse" type="button"><span class="btn-label float-start"><i class="' +
      info.icon +
      ' fa-fw"></i></span><span class="text-uppercase" lang="en">' +
      type +
      "</span></button>"
    );
  }
}
function toggleKillLonderlandLiveUpdate(interval = 5000) {
  if ($(".londerland-log-live-update").hasClass("kill-londerland-log")) {
    clearTimeout(timeouts["londerland-log"]);
    $(".londerland-log-live-update i").toggleClass(
      "fa-dot-circle-o animated loop-animation swing"
    );
    $(".londerland-log-live-update").toggleClass("kill-londerland-log");
  } else {
    $(".londerland-log-live-update").toggleClass("kill-londerland-log");
    londerlandLogLiveUpdate(interval);
  }
}
function londerlandLogLiveUpdate(interval = 5000) {
  var timeout = interval;
  let timeoutTitle = "londerland-log";
  $(".londerland-log-live-update i").toggleClass(
    "fa-dot-circle-o animated loop-animation swing"
  );
  londerlandLogTable.ajax.reload(null, false);
  setTimeout(function () {
    if ($(".londerland-log-live-update").hasClass("kill-londerland-log")) {
      $(".londerland-log-live-update i").toggleClass(
        "fa-dot-circle-o animated loop-animation swing"
      );
    }
  }, interval - 500);
  if (typeof timeouts[timeoutTitle] !== "undefined") {
    clearTimeout(timeouts[timeoutTitle]);
  }
  timeouts[timeoutTitle] = setTimeout(function () {
    londerlandLogLiveUpdate(timeout);
  }, timeout);
  delete timeout;
}
function radioLoop(element) {
  $('[type=radio][id!="' + element.id + '"]').each(function () {
    this.checked = false;
  });
}
function loadAppearance(appearance) {
  var cssSettings = "";
  document.title = appearance.title;
  if (appearance.useLogo === false) {
    $("#main-logo").html(appearance.title);
    $("#side-logo").html(appearance.title);
  } else {
    $("#main-logo").html(
      '<img alt="home" class="dark-logo" src="' + appearance.logo + '">'
    );
    $("#side-logo").html(
      '<img alt="home" class="dark-logo-side" src="' + appearance.logo + '">'
    );
  }
  if (appearance.headerColor !== "") {
    cssSettings +=
      `
		    .navbar-header{
			    background: ` +
      appearance.headerColor +
      `;
		    }
		`;
  }
  if (appearance.headerTextColor !== "") {
    cssSettings +=
      `
		    .navbar-top-links > li > a {
			    color: ` +
      appearance.headerTextColor +
      `;
		    }
		`;
  }
  if (appearance.sidebarColor !== "") {
    cssSettings +=
      `
		    .sidebar, .sidebar .sidebar-head{
			    background: ` +
      appearance.sidebarColor +
      `;
		    }
		`;
  }
  if (appearance.sidebarTextColor !== "") {
    cssSettings +=
      `
		    #side-menu li a,
			.sidebar .sidebar-head h3,
			#side-menu > li > a.active, #side-menu > li > ul > li > a.active
			{
			    color: ` +
      appearance.sidebarTextColor +
      `;
		    }
		`;
  }
  if (appearance.accentColor !== "") {
    cssSettings +=
      `
			.bg-info,
			.fc-toolbar,
			.bg-info,
			.text-bg-info,
			.tabs-style-iconbox nav ul li.tab-current a,
			.swapLog.active {
			    background-color: ` +
      appearance.accentColor +
      ` !important;
			}
			.card-blue .card-header, .card-info .card-header {
			    border-color: ` +
      appearance.accentColor +
      `;
			}
			.tabs-style-iconbox nav ul li.tab-current a::after {
				border-top-color: ` +
      appearance.accentColor +
      `;
			}
			.customvtab .tabs-vertical li.active a,
			.customvtab .tabs-vertical li.active a:focus,
			.customvtab .tabs-vertical li.active a:hover {
				border-right: 2px solid ` +
      appearance.accentColor +
      `;
			}
			.text-info,
			.btn-link, a {
			    color: ` +
      appearance.accentColor +
      `;
			}
		`;
  }
  if (appearance.accentTextColor !== "") {
    cssSettings +=
      `
			.bg-info,
			.progress-bar,
			.card-default .card-header,
			.mailbox-widget .customtab li.active a, .mailbox-widget .customtab li.active, .mailbox-widget .customtab li.active a:focus,
			.mailbox-widget .customtab li a,
			.tabs-style-iconbox nav ul li.tab-current a
			.swapLog.active {
				color: ` +
      appearance.accentTextColor +
      `;
			}
		`;
  }
  if (appearance.buttonColor !== "") {
    cssSettings +=
      `
			.btn-info, .btn-info.disabled,
			.btn,
			.paginate_button.current,
			.paginate_button:hover {
				background: ` +
      appearance.buttonColor +
      ` !important;
				border: 1px solid ` +
      appearance.buttonColor +
      ` !important;
			}
		`;
  }
  if (appearance.buttonTextColor !== "") {
    cssSettings +=
      `
			.btn-info, .btn-info.disabled,
			.btn
			.paginate_button.current
			.paginate_button:hover {
				color: ` +
      appearance.buttonTextColor +
      ` !important;
			}
		`;
  }
  if (appearance.loginWallpaper !== "" || appearance.randomMediaImage) {
    if (appearance.randomMediaImage) {
      appearance.loginWallpaper = appearance.randomMediaImage;
    }
    cssSettings +=
      `
		    .login-register {
			    background: url(` +
      randomCSV(appearance.loginWallpaper) +
      `) center center/cover no-repeat!important;
			    height: 100%;
			    position: fixed;
		    }
			.lock-screen {
				background: url(` +
      randomCSV(appearance.loginWallpaper) +
      `) center center/cover no-repeat!important;
			    height: 100%;
			    position: fixed;
			    z-index: 1001;
			    top: 0;
			    width: 100%;
			    -webkit-user-select: none;
			    -moz-user-select: none;
			    -ms-user-select: none;
			    -o-user-select: none;
			    user-select: none;
			}
		`;
  }
  if (activeInfo["settings"]["misc"]["autoExpandNavBar"] == false) {
    cssSettings += `
			@media only screen and (min-width: 768px) {
				.sidebar:hover .hide-menu {
					display: none;
				}
				.sidebar:hover .sidebar-head,
				.sidebar:hover {
					width: 60px;
				}
				.sidebar:hover .nav-second-level li a {
					padding-left: 15px;
				}
			}
		`;
  }
  if (cssSettings !== "") {
    $("#user-appearance").html(cssSettings);
  }
  if (appearance.customThemeCss !== "") {
    $("#custom-theme-css").html(appearance.customThemeCss);
  }
  if (appearance.customCss !== "") {
    $("#custom-css").html(appearance.customCss);
  }
}
function resetCustomColors() {
  let colors = [
    "headerColor",
    "headerTextColor",
    "sidebarColor",
    "sidebarTextColor",
    "accentColor",
    "accentTextColor",
    "buttonColor",
    "buttonTextColor",
  ];
  $.each(colors, function (i, v) {
    $("#customize-appearance-form [name=" + v + "]")
      .val("")
      .trigger("change");
  });
  messageSingle(
    window.lang.translate("Colors Reverted"),
    window.lang.translate("Please Save"),
    activeInfo.settings.notifications.position,
    "#FFF",
    "success",
    "10000"
  );
}
function randomCSV(values) {
  if (typeof values == "string") {
    if (values.includes(",")) {
      var csv = values.split(",");
      var luckyNumber = Math.floor(Math.random() * csv.length);
      return csv[luckyNumber];
    } else {
      return values;
    }
  }
  return false;
}
function loadCustomJava(appearance) {
  if (appearance.customThemeJava !== "") {
    $("#custom-theme-javascript").html(appearance.customThemeJava);
  }
  if (appearance.customJava !== "") {
    $("#custom-javascript").html(appearance.customJava);
  }
}
function clearForm(form) {
  $(form + " input[type=text]").each(function () {
    $(this).val("");
  });
  $(form + " input[type=password]").each(function () {
    $(this).val("");
  });
}
function checkMessage() {
  var check = local("get", "message") ? local("get", "message") : false;
  if (check) {
    local("remove", "message");
    var message = check.split("|");
    messageSingle(
      window.lang.translate(message[0]),
      window.lang.translate(message[1]),
      activeInfo.settings.notifications.position,
      "#FFF",
      message[2],
      "10000"
    );
  }
}
function buildErrorPage(error) {
  var description = "";
  var message = "";
  var color = "";
  switch (error) {
    case "401":
      description = "Unauthorized";
      message = "Look, you dont belong here";
      color = "danger";
      break;
    case "404":
      description = "Not Found";
      message = "I think I lost it...";
      color = "primary";
      break;
    default:
      description = "Something happened";
      message = "But I dont know what";
      color = "muted";
  }
  return (
    `
	<div class="error-box">
		<div class="error-body text-center">
			<h1 class="text-` +
    color +
    `">` +
    error +
    `</h1>
			<h3 class="text-uppercase">` +
    description +
    `</h3>
			<p class="text-muted m-t-30 m-b-30" lang="en">` +
    message +
    `</p>
			<a href="javascript:void(0);" class="btn btn-` +
    color +
    ` btn-rounded waves-effect waves-light m-b-40 closeErrorPage animated tada loop-animation" lang="en">OK</a>
		</div>
	</div>
	`
  );
}
$.urlParam = function (name) {
  var results = new RegExp("[?&]" + name + "=([^&#]*)").exec(
    window.location.href
  );
  if (results == null) {
    return null;
  } else {
    return decodeURI(results[1]) || 0;
  }
};
function errorPage(error = null, uri = null) {
  if (error) {
    local("set", "error", error);
  }
  if (uri) {
    local("set", "uri", uri);
  }
  //var urlParams = new URLSearchParams(window.location.search);

  if ($.urlParam("error") !== null && !isNaN(Number($.urlParam("error")))) {
    local("set", "error", $.urlParam("error"));
  }
  if ($.urlParam("return") !== null && activeInfo.user.loggedin !== true) {
    local("set", "uri", $.urlParam("return"));
  }
  if (window.location !== window.parent.location) {
    var count = 0;
    for (var k in window.parent.location) {
      if (window.parent.location.hasOwnProperty(k)) {
        ++count;
      }
    }
    if (count == 0 || count == "undefined") {
      return false;
    }
    var iframeError = local("get", "error");
    parent.errorPage(iframeError);
    local("remove", "uri");
    $("html").html("");
    return false;
  }
  if (local("get", "error")) {
    //show error page
    $(".error-page").html(buildErrorPage(local("get", "error")));
    $(".error-page").fadeIn();
    local("remove", "error");
    window.history.pushState({}, document.title, "./");
  }
}
function uriRedirect(uri = null) {
  if (uri) {
    local("set", "uri", uri);
  }
  if (activeInfo.user.loggedin === true && activeInfo.user.locked !== 1) {
    var redirect = local("get", "uri");
    local("remove", "uri");
    if (redirect !== null) {
      window.location.href = decodeURIComponent(decodeURI(redirect));
    }
  }
}
function changeTheme(theme) {
  //$("#preloader").fadeIn();
  $("#theme").attr({
    href: theme + ".css?v=" + activeInfo.version,
  });
  //$("#preloader").fadeOut();
  console.info(
    "%c Theme %c ".concat(theme, " "),
    "color: white; background: #AD80FD; font-weight: 700;",
    "color: #AD80FD; background: white; font-weight: 700;"
  );
}
function changeStyle(style) {
  // The server already sends the right stylesheet; swapping in the same file again drops it while it downloads
  // again, which makes the whole page jump (layout shift)
  const current = ($("#style").attr("href") || "").split("?")[0];
  if (current !== "css/" + style + ".min.css") {
    $("#style").attr({
      href: "css/" + style + ".min.css?v=" + activeInfo.version,
    });
  }
  //$("#preloader").fadeOut();
  console.info(
    "%c Style %c ".concat(style, " "),
    "color: white; background: #AD80FD; font-weight: 700;",
    "color: #AD80FD; background: white; font-weight: 700;"
  );
}
function setSSO() {
  $.each(activeInfo.sso, function (i, v) {
    if (v !== false) {
      local("set", i, v);
    } else {
      local("r", i);
    }
  });
  // other items to remove
  $.each(localStorage, function (i, v) {
    if (typeof v == "string") {
      if (i.startsWith("user-")) {
        if (typeof activeInfo.sso[i] == "undefined") {
          local("r", i);
        }
      }
    }
  });
}
function pagination(c, m) {
  var current = c,
    last = m,
    delta = 2,
    left = current - delta,
    right = current + delta + 1,
    range = [],
    rangeWithDots = [],
    l;

  for (let i = 1; i <= last; i++) {
    if (i == 1 || i == last || (i >= left && i < right)) {
      range.push(i);
    }
  }

  for (let i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push("...");
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
}
function testAPIConnection(service, data = "") {
  messageSingle(
    "",
    " Testing now...",
    activeInfo.settings.notifications.position,
    "#FFF",
    "info",
    "60000"
  );
  londerlandAPI2("POST", "api/v2/test/" + service, data)
    .done(function (data) {
      try {
        let response = data.response;
        messageSingle(
          "",
          " API Connection Success",
          activeInfo.settings.notifications.position,
          "#FFF",
          "success",
          "10000"
        );
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "API Error");
    });
}
// Thanks Swifty!
function PopupCenter(url, title, w, h) {
  // Fixes dual-screen position                         Most browsers      Firefox
  var dualScreenLeft =
    window.screenLeft != undefined ? window.screenLeft : window.screenX;
  var dualScreenTop =
    window.screenTop != undefined ? window.screenTop : window.screenY;
  var width = window.innerWidth
    ? window.innerWidth
    : document.documentElement.clientWidth
    ? document.documentElement.clientWidth
    : screen.width;
  var height = window.innerHeight
    ? window.innerHeight
    : document.documentElement.clientHeight
    ? document.documentElement.clientHeight
    : screen.height;
  var left = width / 2 - w / 2 + dualScreenLeft;
  var top = height / 2 - h / 2 + dualScreenTop;
  var newWindow = window.open(
    url,
    title,
    "scrollbars=yes, width=" +
      w +
      ", height=" +
      h +
      ", top=" +
      top +
      ", left=" +
      left
  );
  // Puts focus on the newWindow
  if (window.focus) {
    newWindow.focus();
  }
  return newWindow;
}
function getPlexHeaders() {
  let plexTitle =
    activeInfo.appearance.title == ""
      ? "Londerland"
      : cleanClass(activeInfo.appearance.title);
  return {
    Accept: "application/json",
    "X-Plex-Product": plexTitle,
    "X-Plex-Version": "2.0",
    "X-Plex-Client-Identifier": activeInfo.settings.misc.uuid,
    "X-Plex-Model": "Plex OAuth",
    "X-Plex-Platform": activeInfo.osName,
    "X-Plex-Platform-Version": activeInfo.osVersion,
    "X-Plex-Device": activeInfo.browserName,
    "X-Plex-Device-Name": activeInfo.browserVersion,
    "X-Plex-Device-Screen-Resolution":
      window.screen.width + "x" + window.screen.height,
    "X-Plex-Language": "en",
  };
}
var plex_oauth_window = null;
const plex_oauth_loader =
  "<style>" +
  ".login-loader-container {" +
  'font-family: "Open Sans", Arial, sans-serif;' +
  "position: absolute;" +
  "top: 0;" +
  "right: 0;" +
  "bottom: 0;" +
  "left: 0;" +
  "}" +
  ".login-loader-message {" +
  "color: #282A2D;" +
  "text-align: center;" +
  "position: absolute;" +
  "left: 50%;" +
  "top: 25%;" +
  "transform: translate(-50%, -50%);" +
  "}" +
  ".login-loader {" +
  "border: 5px solid #ccc;" +
  "-webkit-animation: spin 1s linear infinite;" +
  "animation: spin 1s linear infinite;" +
  "border-top: 5px solid #282A2D;" +
  "border-radius: 50%;" +
  "width: 50px;" +
  "height: 50px;" +
  "position: relative;" +
  "left: calc(50% - 25px);" +
  "}" +
  "@keyframes spin {" +
  "0% { transform: rotate(0deg); }" +
  "100% { transform: rotate(360deg); }" +
  "}" +
  "</style>" +
  '<div class="login-loader-container">' +
  '<div class="login-loader-message">' +
  '<div class="login-loader"></div>' +
  "<br>" +
  "Redirecting to the login page..." +
  "</div>" +
  "</div>";
function closePlexOAuthWindow() {
  if (plex_oauth_window) {
    plex_oauth_window.close();
  }
}
getPlexOAuthPin = function () {
  var x_plex_headers = getPlexHeaders();
  var deferred = $.Deferred();
  $.ajax({
    url: "https://plex.tv/api/v2/pins?strong=true",
    type: "POST",
    headers: x_plex_headers,
    success: function (data) {
      deferred.resolve({ pin: data.id, code: data.code });
    },
    error: function () {
      closePlexOAuthWindow();
      deferred.reject();
    },
  });
  return deferred;
};
var polling = null;
function PlexOAuth(
  successCallback,
  errorCallback,
  maxRetryCallback,
  pollingCallback,
  preFunction,
  clientID = null
) {
  if (typeof preFunction === "function") {
    preFunction();
  }
  closePlexOAuthWindow();
  plex_oauth_window = PopupCenter("", "Plex-OAuth", 600, 700);
  $(plex_oauth_window.document.body).html(plex_oauth_loader);
  getPlexOAuthPin().then(
    function (data) {
      var x_plex_headers = getPlexHeaders();
      const pin = data.pin;
      const code = data.code;
      var oauth_params = {
        clientID: x_plex_headers["X-Plex-Client-Identifier"],
        "context[device][product]": x_plex_headers["X-Plex-Product"],
        "context[device][version]": x_plex_headers["X-Plex-Version"],
        "context[device][platform]": x_plex_headers["X-Plex-Platform"],
        "context[device][platformVersion]":
          x_plex_headers["X-Plex-Platform-Version"],
        "context[device][device]": x_plex_headers["X-Plex-Device"],
        "context[device][deviceName]": x_plex_headers["X-Plex-Device-Name"],
        "context[device][model]": x_plex_headers["X-Plex-Model"],
        "context[device][screenResolution]":
          x_plex_headers["X-Plex-Device-Screen-Resolution"],
        "context[device][layout]": "desktop",
        code: code,
      };
      plex_oauth_window.location =
        "https://app.plex.tv/auth/#!?" + encodeData(oauth_params);
      polling = pin;
      let maxPollCount = 120;
      (function poll() {
        maxPollCount--;
        $.ajax({
          url: "https://plex.tv/api/v2/pins/" + pin,
          type: "GET",
          headers: x_plex_headers,
          success: function (data) {
            if (data.authToken) {
              polling = null;
              closePlexOAuthWindow();
              if (typeof successCallback === "function") {
                successCallback("plex", data.authToken, clientID);
              }
            }
          },
          complete: function () {
            if (maxPollCount <= 0) {
              closePlexOAuthWindow();
              if (typeof maxRetryCallback === "function") {
                maxRetryCallback();
              }
            } else if (polling === pin) {
              setTimeout(function () {
                poll();
              }, 1000);
              if (typeof pollingCallback === "function") {
                pollingCallback(maxPollCount);
              }
            }
          },
          timeout: 1000,
        });
      })();
    },
    function () {
      closePlexOAuthWindow();
      if (typeof errorCallback === "function") {
        errorCallback();
      }
    }
  );
}
function encodeData(data) {
  return Object.keys(data)
    .map(function (key) {
      return [key, data[key]].map(encodeURIComponent).join("=");
    })
    .join("&");
}
function oAuthSuccess(type, token, id = null) {
  switch (type) {
    case "plex":
      if (id) {
        $(id).val(token);
        $(id).change();
        messageSingle(
          "",
          window.lang.translate("Grabbed Token - Please Save"),
          activeInfo.settings.notifications.position,
          "#FFF",
          "success",
          "5000"
        );
      } else {
        $("#oAuth-Input").val(token);
        $("#oAuthType-Input").val(type);
        $("#login-username-Input").addClass("hidden");
        $("#login-password-Input").addClass("hidden");
        $("#oAuth-div").removeClass("hidden");
        $(".login-button").first().trigger("click");
      }
      break;
    default:
      break;
  }
}
function oAuthError() {
  messageSingle(
    "",
    window.lang.translate("Error Connecting to oAuth Provider"),
    activeInfo.settings.notifications.position,
    "#FFF",
    "error",
    "5000"
  );
}
function oAuthMaxRetry() {
  messageSingle(
    "",
    window.lang.translate("Max Retry Error Connecting to oAuth Provider"),
    activeInfo.settings.notifications.position,
    "#FFF",
    "error",
    "5000"
  );
}
function oAuthStart(type) {
  switch (type) {
    case "plex":
      PlexOAuth(oAuthSuccess, oAuthError, oAuthMaxRetry, null, null);
      break;
    default:
      break;
  }
}
function oidcStart(provider) {
  // Redirect in same window to OIDC provider
  window.location.href =
    "api/v2/oidc/" + encodeURIComponent(provider) + "/authorize";
}
function testOIDCConnection(provider) {
  londerlandAPI2(
    "GET",
    "api/v2/oidc/" + encodeURIComponent(provider) + "/test",
    ""
  )
    .done(function (data) {
      if (data.response.result === "success") {
        messageSingle(
          "OIDC Test",
          data.response.message || "Connection successful",
          activeInfo.settings.notifications.position,
          "#FFF",
          "success",
          "5000"
        );
      } else {
        messageSingle(
          "OIDC Test",
          data.response.message || "Connection failed",
          activeInfo.settings.notifications.position,
          "#FFF",
          "error",
          "5000"
        );
      }
    })
    .fail(function (xhr) {
      var msg = "Connection failed";
      try {
        var resp = JSON.parse(xhr.responseText);
        msg = resp.response.message || msg;
      } catch (e) {}
      messageSingle(
        "OIDC Test",
        msg,
        activeInfo.settings.notifications.position,
        "#FFF",
        "error",
        "5000"
      );
    });
}
//Generate API
function generateCode() {
  var code = "";
  var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
  for (var i = 0; i < 20; i++)
    code += possible.charAt(Math.floor(Math.random() * possible.length));
  return code;
}
// uppercase word
// human filesize
//youtube search
// The admin's own TMDB key (Settings > System Settings > Main > The Movie Database)
//Import Users
function importUsers(type) {
  $(".importUsersButton").attr("disabled", true);
  messageSingle(
    "",
    window.lang.translate("Importing Users"),
    activeInfo.settings.notifications.position,
    "#FFF",
    "success",
    "5000"
  );
  londerlandAPI2("POST", "api/v2/users/import/" + type, { type: type })
    .done(function (data) {
      try {
        var response = data.response;
        message(
          "User Import",
          response.message,
          activeInfo.settings.notifications.position,
          "#FFF",
          "success",
          "5000"
        );
        $(".importUsersButton").attr("disabled", false);
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Import Error");
    });
}
//Settings change auth
function changeAuth() {
  var type = $("#authSelect").val();
  var service = $("#authBackendSelect").val();
  switch (service) {
    case "plex":
      $(".switchAuth").parent().parent().parent().hide();
      $(".backendAuth").parent().parent().parent().show();
      $(".plexAuth").parent().parent().parent().show();
      break;
    case "emby_local":
    case "emby_connect":
    case "emby_all":
      $(".switchAuth").parent().parent().parent().hide();
      $(".backendAuth").parent().parent().parent().show();
      $(".embyAuth").parent().parent().parent().show();
      break;
    case "jellyfin":
      $(".switchAuth").parent().parent().parent().hide();
      $(".backendAuth").parent().parent().parent().show();
      $(".jellyfinAuth").parent().parent().parent().show();
      break;
    case "ftp":
      $(".switchAuth").parent().parent().parent().hide();
      $(".backendAuth").parent().parent().parent().show();
      $(".ftpAuth").parent().parent().parent().show();
      break;
    case "ldap":
      $(".switchAuth").parent().parent().parent().hide();
      $(".backendAuth").parent().parent().parent().show();
      $(".ldapAuth").parent().parent().parent().show();
      break;
    default:
      $(".switchAuth").parent().parent().parent().hide();
      $(".backendAuth").parent().parent().parent().show();
  }
  if (type == "internal") {
    $(".switchAuth").parent().parent().parent().hide();
  }
}
function checkLocalForwardStatus(array) {
  if (
    array.settings.login.enableLocalAddressForward == true &&
    typeof array.settings.login.enableLocalAddressForward !== "undefined"
  ) {
    if (
      array.settings.login.wanDomain !== "" &&
      array.settings.login.localAddress !== ""
    ) {
      londerlandConsole("Londerland Function", "Local Login Enabled");
      londerlandConsole("Londerland Function", "Local Login Testing...");
      let remoteSite = array.settings.login.wanDomain;
      let localSite = array.settings.login.localAddress;
      try {
        let currentURL = decodeURI(window.location.href);
        let currentSite = window.location.host;
        if (
          activeInfo.settings.user.local &&
          currentSite.indexOf(remoteSite) !== -1 &&
          currentURL.indexOf("override") === -1
        ) {
          londerlandConsole(
            "Londerland Function",
            "Local Login Status: Local | Forwarding Now"
          );
          window.location = localSite;
        } else {
          londerlandConsole(
            "Londerland Function",
            "Local Login Status: Not Local or Override was set - Ignoring Forward Request"
          );
        }
      } catch (e) {
        console.error(e);
      }
    }
  }
}
function getPingList(arrayItems) {
  var pingList = [];
  var timeout =
    activeInfo.user.groupID <= 1
      ? activeInfo.settings.ping.adminRefresh
      : activeInfo.settings.ping.everyoneRefresh;
  if (
    Array.isArray(arrayItems["data"]["tabs"]) &&
    arrayItems["data"]["tabs"].length > 0
  ) {
    $.each(arrayItems["data"]["tabs"], function (i, v) {
      if (v.ping && v.ping_url !== null) {
        pingList.push(v.ping_url);
      }
    });
  }
  return pingList.length > 0 ? pingUpdate(pingList, timeout) : false;
}
function pingUpdateItem(ping) {
  if (activeInfo.user.groupID > activeInfo.settings.ping.auth) {
    return false;
  }
  londerlandAPI2("GET", "api/v2/ping/" + ping)
    .done(function (data) {
      try {
        var response = data.response;
      } catch (e) {
        londerlandCatchError(e, data);
      }
      var i = ping;
      var v = response.data;
      var elm = $(".menu-" + cleanClass(i) + "-ping");
      var elmMs = $(".menu-" + cleanClass(i) + "-ping-ms");
      var catElm = elm
        .parent()
        .parent()
        .parent()
        .parent()
        .children("a")
        .find(".menu-category-ping");
      var error =
        '<div class="ping"><span class="heartbit"></span><span class="point"></span></div>';
      var success = "";
      var badCount = 0;
      var goodCount = 0;
      var previousState =
        elm.attr("data-previous-state") == ""
          ? ""
          : elm.attr("data-previous-state");
      var tabName = elm.attr("data-tab-name");
      var status = v == null ? "down" : "up";
      var ms = v == null ? "down" : v + "ms";
      var sendMessage =
        previousState !== status &&
        previousState !== "" &&
        activeInfo.user.groupID <= activeInfo.settings.ping.authMessage
          ? true
          : false;
      var audioDown = sendMessage
        ? new Audio(activeInfo.settings.ping.offlineSound)
        : "";
      var audioUp = sendMessage
        ? new Audio(activeInfo.settings.ping.onlineSound)
        : "";
      elm.attr("data-previous-state", status);
      let listing = elm
        .parent()
        .parent()
        .parent()
        .parent()
        .children("a")
        .find(".menu-category-ping")
        .parent()
        .parent()
        .find("li")
        .find("div[class$='-ping']");
      $.each(listing, function (i, v) {
        let state = $(v).attr("data-previous-state");
        if (state == "up") {
          goodCount = goodCount + 1;
        } else if (state == "down") {
          badCount = badCount + 1;
        }
      });
      if (catElm.length > 0) {
        catElm.attr("data-bad", badCount);
        catElm.attr("data-good", goodCount);
        if (badCount == 0) {
          catElm.html(success);
        }
      }
      if (
        activeInfo.user.groupID <= activeInfo.settings.ping.authMs &&
        activeInfo.settings.ping.ms
      ) {
        elmMs.removeClass("hidden").html(ms);
      }
      switch (status) {
        case "down":
          elm.html(error);
          catElm.html(error);
          elm.parent().find("img").addClass("grayscale");
          var msg = sendMessage
            ? message(
                tabName,
                "Server Down",
                activeInfo.settings.notifications.position,
                "#FFF",
                "error",
                "600000"
              )
            : "";
          var audio =
            sendMessage && activeInfo.settings.ping.statusSounds
              ? audioDown.play()
              : "";
          break;
        default:
          elm.html(success);
          elm.parent().find("img").removeClass("grayscale");
          var msg = sendMessage
            ? message(
                tabName,
                "Server Back Online",
                activeInfo.settings.notifications.position,
                "#FFF",
                "success",
                "600000"
              )
            : "";
          var audio =
            sendMessage && activeInfo.settings.ping.statusSounds
              ? audioUp.play()
              : "";
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function pingUpdate(pingList, timeout) {
  $.each(pingList, function (i, v) {
    pingUpdateItem(v);
  });
  var timeoutTitle = "ping";
  if (typeof timeouts[timeoutTitle] !== "undefined") {
    clearTimeout(timeouts[timeoutTitle]);
  }
  timeouts[timeoutTitle] = setTimeout(function () {
    pingUpdate(pingList, timeout);
  }, timeout);
}
function include(filename) {
  var type = filename.split(".").pop();
  switch (type) {
    case "js":
      var body = document.getElementsByTagName("body")[0];
      var script = document.createElement("script");
      script.src = filename;
      script.type = "text/javascript";
      body.appendChild(script);
      break;
    case "css":
      var head = document.getElementById("style");
      var script = document.createElement("link");
      script.href = filename;
      script.type = "text/css";
      script.rel = "stylesheet";
      head.appendChild(script);
      break;
    default:
      return false;
  }
  return false;
}
// Notifications are shown as Bootstrap toasts, or with AlertifyJS when that style is selected.
// Older settings (izi, toastr, noty) fall back to Bootstrap toasts.
function notificationBackbone() {
  let backbone =
    typeof activeInfo !== "undefined"
      ? activeInfo.settings.notifications.backbone
      : "bootstrap";
  return backbone === "alertify" ? "alertify" : "bootstrap";
}
function defineNotification() {
  window.notificationsReady = true;
}
function messagePositions() {
  return {
    br: { bootstrap: "bottom-0 end-0", alertify: "bottom-right" },
    bl: { bootstrap: "bottom-0 start-0", alertify: "bottom-left" },
    bc: { bootstrap: "bottom-0 start-50 translate-middle-x", alertify: "bottom-center" },
    tr: { bootstrap: "top-0 end-0", alertify: "top-right" },
    tl: { bootstrap: "top-0 start-0", alertify: "top-left" },
    tc: { bootstrap: "top-0 start-50 translate-middle-x", alertify: "top-center" },
    c: { bootstrap: "top-50 start-50 translate-middle", alertify: "bottom-center" },
  };
}
function messageIcon(icon) {
  return (
    {
      success: "mdi mdi-check-circle-outline",
      info: "mdi mdi-information-outline",
      error: "mdi mdi-close-circle-outline",
      warning: "mdi mdi-alert-circle-outline",
      update: "mdi mdi-webpack",
    }[icon] || "mdi mdi-alert-circle-outline"
  );
}
function message(
  heading,
  text,
  position,
  color,
  icon,
  timeout,
  single = false
) {
  let activePosition =
    typeof activeInfo !== "undefined"
      ? activeInfo.settings.notifications.position
      : "bc";
  position = typeof position !== "undefined" ? position : activePosition;
  text = typeof text !== "undefined" ? text : "";
  icon = typeof icon !== "undefined" ? icon : "info";
  timeout = typeof timeout !== "undefined" ? parseInt(timeout, 10) : 10000;
  let backbone = notificationBackbone();
  let positions = messagePositions();
  let placement = (positions[position] || positions.bc)[backbone];
  if (backbone === "alertify") {
    if (single) {
      alertify.dismissAll();
    }
    let msgFull = heading !== "" ? heading + "<br/>" + text : text;
    alertify.set("notifier", "position", placement);
    alertify.notify(msgFull, icon + "-alertify", timeout / 1000);
    return true;
  }
  let containerId = "org-toasts-" + (positions[position] ? position : "bc");
  let container = $("#" + containerId);
  if (!container.length) {
    container = $(
      `<div id="${containerId}" class="toast-container position-fixed p-3 ${placement}"></div>`
    ).appendTo("body");
  }
  if (single) {
    container.find(".toast").remove();
  }
  let toast = $(`
    <div class="toast org-toast ${icon}-notify" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body d-flex">
          <i class="${messageIcon(icon)} org-toast-icon m-r-10"></i>
          <div><strong class="org-toast-title"></strong><div class="org-toast-text"></div></div>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="org-toast-progress" style="animation-duration: ${timeout}ms"></div>
    </div>`);
  toast.find(".org-toast-title").text(heading);
  toast.find(".org-toast-text").html(text);
  toast.appendTo(container);
  toast.on("hidden.bs.toast", function () {
    toast.remove();
  });
  bootstrap.Toast.getOrCreateInstance(toast[0], { delay: timeout }).show();
  return true;
}

function messageSingle(heading, text, position, color, icon, timeout) {
  let activePosition =
    typeof activeInfo !== "undefined"
      ? activeInfo.settings.notifications.position
      : "bc";
  position = typeof position !== "undefined" ? position : activePosition;
  text = typeof text !== "undefined" ? text : "";
  color = typeof color !== "undefined" ? color : "#FFF";
  icon = typeof icon !== "undefined" ? icon : "info";
  timeout = typeof timeout !== "undefined" ? timeout : 10000;
  message(heading, text, position, color, icon, timeout, true);
}

function blockDev(e) {
  var evtobj = window.event ? event : e;
  if (evtobj.keyCode == 73 && evtobj.shiftKey && evtobj.ctrlKey) {
    evtobj.preventDefault();
  }
}
function authDebugCheck() {
  if (activeInfo.settings.misc.authDebug == true) {
    message(
      "REMINDER",
      "Auth Debug is still enabled",
      activeInfo.settings.notifications.position,
      "#FFF",
      "warning",
      "20000"
    );
  }
}
function lock() {
  if (activeInfo.settings.user.oAuthLogin == true) {
    message(
      "Lock Disabled",
      "Lock function disabled if logged in via oAuth",
      activeInfo.settings.notifications.position,
      "#FFF",
      "warning",
      "5000"
    );
    return false;
  }
  londerlandAPI2("POST", "api/v2/users/lock", "")
    .done(function (data) {
      try {
        let html = data.response;
        location.reload();
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr, "Lock Error");
    });
}
function openSettings() {
  let tabInfo = findTab("api/v2/page/settings", "access_url");
  tabActions("click", tabInfo.id);
}
function toggleFullScreenIcon() {
  $(".fullscreen-icon")
    .toggleClass("ti-fullscreen")
    .toggleClass("mdi mdi-fullscreen-exit");
}
function toggleFullScreen() {
  toggleFullScreenIcon();
  if (
    !document.fullscreenElement && // alternative standard method
    !document.mozFullScreenElement &&
    !document.webkitFullscreenElement &&
    !document.msFullscreenElement
  ) {
    // current working methods
    if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen();
    } else if (document.documentElement.msRequestFullscreen) {
      document.documentElement.msRequestFullscreen();
    } else if (document.documentElement.mozRequestFullScreen) {
      document.documentElement.mozRequestFullScreen();
    } else if (document.documentElement.webkitRequestFullscreen) {
      document.documentElement.webkitRequestFullscreen(
        Element.ALLOW_KEYBOARD_INPUT
      );
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    }
  }
}
function orgErrorCode(code) {
  switch (code) {
    case "upgrading":
      window.location.href = "./plugins/static/upgrade.html";
    default:
  }
}
function toggleWritableFolders() {
  $(".folders-writable").toggleClass("hidden");
}
function getAllTabNames() {
  var allTabs = $(".tabEditor");
  var tabList = [];
  $.each(allTabs, function (i, v) {
    tabList[i] = v.getAttribute("data-name").toLowerCase();
  });
  return tabList;
}
function checkIfTabNameExists(tabName) {
  if (getAllTabNames().indexOf(tabName.toLowerCase()) == -1) {
    return false;
  } else {
    return true;
  }
}
function getLatestBlackberryThemes() {
  return $.ajax({
    url: "https://api.github.com/repos/Archmonger/Blackberry-Themes/contents/Themes",
  });
}
function getBlackberryTheme(theme) {
  return $.ajax({
    url:
      "https://api.github.com/repos/Archmonger/Blackberry-Themes/contents/Themes/" +
      theme +
      "/Icons",
  });
}
function showBlackberryThemes(target) {
  getLatestBlackberryThemes()
    .done(function (data) {
      try {
        let themes = "";
        $.each(data, function (i, v) {
          if (v.name !== "Beta") {
            themes += `<a href="javascript:selectBlackberryTheme('${v.name}','${target}');" class="list-group-item"><span><img class="themeIcon float-end" src="https://raw.githubusercontent.com/Archmonger/Blackberry-Themes/master/Themes/${v.name}/Icons/preview.png"></span>${v.name}</a>`;
          }
        });
        themes = `<div class="list-group">${themes}</div>`;
        let html = `
			<div class="card">
				<div class="bg-org2">
					<div class="card-header">Choose a Theme</div>
					<div class="card-body text-start">${themes}</div>
				</div>
			</div>
			`;
        Swal.fire({
          html: createElementFromHTML(html),
          button: "Close",
          customClass: { popup: "orgErrorAlert" },
        });
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function selectBlackberryTheme(theme, target) {
  getBlackberryTheme(theme)
    .done(function (data) {
      try {
        let icons = "";
        $.each(data, function (i, v) {
          v.name = v.name.split(".")[0];
          v.name = cleanClass(v.name);
          icons += `<a href="#" onclick="javascript:Swal.close();$('#${target}').val('${v.download_url}')"><img alt="${v.name}" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-title="${v.name}"src="${v.download_url}" ></a>`;
        });
        icons = `<div id="gallery-content-center">${icons}</div>`;
        let html = `
			<div class="card">
				<div class="bg-org2">
					<div class="card-header">Choose an Icon</div>
					<div class="card-body text-start">${icons}</div>
				</div>
			</div>
			`;
        Swal.fire({
          html: createElementFromHTML(html),
          confirmButtonText: "Back To Themes",
          customClass: { popup: "orgErrorAlert", confirmButton: "bg-org-alt" },
        }).then((result) => {
          if (result.isConfirmed) {
            showBlackberryThemes();
          }
        });
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function orgErrorAlert(error) {
  let showError = false;
  if (typeof activeInfo === "undefined") {
    showError = true;
  } else {
    if (activeInfo.settings.misc.debugErrors) {
      showError = true;
    }
  }
  if (showError) {
    let div = `
	    <div class="card">
            <div class="bg-org2">
                <div class="card-header">ERROR</div>
                <div class="card-body text-start">${error}</div>
            </div>
        </div>
	    `;
    Swal.fire({
      html: createElementFromHTML(div),
      button: "OK",
      customClass: { popup: "orgErrorAlert" },
    });
  }
}
function toggleDebug() {
  var div = `
	<div class="white-box m-0">
	    <div class="steamline">
	        <div class="sl-item">
	            <div class="sl-left bg-success"><i class="mdi mdi-code-tags"></i></div>
	            <div class="sl-right">
	                <div class="form-group">
	                    <div id="" class="input-group">
	                        <input id="debug-input" lang="en" placeholder="Input Command" type="text"
	                               class="form-control inline-focus">
	                        <div class="input-group-btn">
	                            <button type="button"
	                                    class="btn waves-effect waves-light btn-info dropdown-toggle"
	                                    data-bs-toggle="dropdown" aria-expanded="false"><span lang="en">Commands</span></button>
	                            <ul class="dropdown-menu dropdown-menu-end">
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso');"
	                                       href="javascript:void(0)"
	                                       lang="en">SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.plex');"
	                                       href="javascript:void(0)"
	                                       lang="en">Plex SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.tautulli');"
	                                       href="javascript:void(0)"
	                                       lang="en">Tautulli SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.overseerr');"
	                                       href="javascript:void(0)"
	                                       lang="en">Overseerr SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.petio');"
	                                       href="javascript:void(0)"
	                                       lang="en">Petio SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.ombi');"
	                                       href="javascript:void(0)"
	                                       lang="en">Ombi SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.jellyfin');"
	                                       href="javascript:void(0)"
	                                       lang="en">Jellyfin SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.komga');"
	                                       href="javascript:void(0)"
	                                       lang="en">Komga SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.misc');"
	                                       href="javascript:void(0)"
	                                       lang="en">Misc SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.misc.schema');"
	                                       href="javascript:void(0)"
	                                       lang="en">DB Schema</a></li>
	                            </ul>
	                        </div>
	                    </div>
	                    <div class="clearfix"></div>
	                </div>
	            </div>
	        </div>
	        <div id="debugPreInfoBox" class="sl-item text-start">
	            <div class="sl-left bg-info"><i class="mdi mdi-package-variant-closed"></i></div>
	            <div class="sl-right">
	                <div>
	                    <span lang="en">Londerland Information:</span>&nbsp;
	                </div>
	                <div id="debugPreInfo" class="desc"></div>
	            </div>
	        </div>
	        <div id="debugResultsBox" class="sl-item hidden text-start">
	            <div class="sl-left bg-info"><i class="mdi mdi-receipt"></i></div>
	            <div class="sl-right">
	                <div><span lang="en">Results For cmd:</span>&nbsp;<span class="cmdName"></span>
	                </div>
	                <div id="debugResults" class="desc"></div>
	            </div>
	        </div>
	    </div>
	</div>
	`;
  Swal.fire({
    html: createElementFromHTML(div),
    button: "OK",
    customClass: { popup: "orgErrorAlert" },
  });
  getDebugPreInfo();
}
function closeOrgError() {
  $("#main-org-error-container").removeClass("show");
  $("#main-org-error").html("");
}
function isJSON(data) {
  if (typeof data != "string") {
    data = JSON.stringify(data);
  }
  try {
    JSON.parse(data);
    return true;
  } catch (e) {
    return false;
  }
}
function createElementFromHTML(htmlString) {
  var div = document.createElement("div");
  div.innerHTML = htmlString.trim();
  return div.firstChild;
}
function showLDAPLoginTest() {
  var div = `
        <div class="row">
            <div class="col-12">
                <div class="card m-b-0">
                    <div class="form-horizontal">
                        <div class="card-body">
                            <h4 class="card-title" lang="en">LDAP User Info</h4>
                            <div class="form-group row">
                                <div class="col-md-12">
                                    <input type="text" class="form-control" id="ldapUsernameTest" placeholder="Username">
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-12">
                                    <input type="password" class="form-control" id="ldapPasswordTest" placeholder="Password">
                                </div>
                            </div>
                            <div class="form-group mb-0 p-r-10 text-end">
                                <button type="submit" onclick="testAPIConnection('ldap/login', {'username':$('#ldapUsernameTest').val(),'password':$('#ldapPasswordTest').val()})" class="btn btn-info waves-effect waves-light">Test Login</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
  Swal.fire({
    html: createElementFromHTML(div),
    showConfirmButton: false,
    customClass: { popup: "bg-org" },
  });
}

function showPlexMachineForm(selector = null) {
  var div = `
		<form id="get-plex-machine-form">
		    <h1 lang="en">Get Plex Machine</h1>
		    <div class="card plexMachineHeader">
		        <div class="card-header plexMachineMessage" lang="en">Contacting server...</div>
		    </div>
		    <fieldset style="border:0;">
		        <div class="form-group">
		            <label class="form-label" for="plex-machine-form-machine" lang="en">Plex Machine</label>
		            <div class="plexMachineListing"></div>
		        </div>
		    </fieldset>
		    <div class="clearfix"></div>
		</form>
	`;
  Swal.fire({
    html: createElementFromHTML(div),
    showConfirmButton: false,
    customClass: { popup: "bg-org" },
  }).then(
    londerlandAPI2("GET", "api/v2/plex/servers?owned")
      .done(function (data) {
        try {
          let response = data.response;
          $(".plexMachineMessage").text("Choose Plex Server");
          $(".plexMachineHeader")
            .addClass("card-success")
            .removeClass("card-info")
            .removeClass("card-warning");
          let machines = '<option lang="en">Choose Plex Machine</option>';
          $.each(response.data, function (i, v) {
            let name = v.name;
            let machine = v.machineIdentifier;
            name = name + " [" + machine + "]";
            machines += '<option value="' + machine + '">' + name + "</option>";
          });
          let listing =
            '<select class="form-control" id="plexMachineSelector" data-selector="' +
            selector +
            '" data-type="select">' +
            machines +
            "</select>";
          $(".plexMachineListing").html(listing);
        } catch (e) {
          londerlandCatchError(e, data);
        }
      })
      .fail(function (xhr) {
        LonderlandApiError(xhr, "API Error");
        $(".plexMachineMessage").text("Plex Token Needed First");
        $(".plexMachineHeader")
          .addClass("card-warning")
          .removeClass("card-info")
          .removeClass("card-danger");
      })
  );
}
function bypassLocalLogin() {
  if (activeInfo.settings.user.bypass !== true) {
    return false;
  }
  const bypass = $.urlParam("bypassDisable");
  if (bypass) {
    return false;
  }
  OAuthLoginNeeded = true;
  oAuthLoginNeededCheck("Bypass");
}
function oAuthLoginNeededCheck(type = "OAuth") {
  if (OAuthLoginNeeded == false) {
    return false;
  } else {
    if (activeInfo.user.loggedin == true) {
      return false;
    }
  }
  let data = "";
  if (type === "Bypass") {
    const bypass = $.urlParam("bypassDisable");
    if (bypass) {
      data = "bypass";
    }
  }
  message(
    type,
    " Proceeding to login",
    activeInfo.settings.notifications.position,
    "#FFF",
    "info",
    "10000"
  );
  londerlandAPI2("POST", "api/v2/login", data)
    .done(function (data) {
      local("set", "message", "Welcome|Login Successful|success");
      local("r", "loggingIn");
      location.reload();
    })
    .fail(function (xhr) {
      $("div.login-box").unblock({});
      switch (xhr.status) {
        case 401:
          if (xhr.responseJSON.response.message == "2FA Code incorrect") {
            $("div.login-box").unblock({});
            $("#tfa-div").removeClass("hidden");
            $("#loginform [name=tfaCode]").focus();
          }
          break;
        case 403:
          $("div.login-box").block({
            message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
            css: {
              color: "#fff",
              border: "1px solid #e91e63",
              backgroundColor: "#f44336",
            },
          });
          setTimeout(function () {
            local("r", "loggingIn");
            location.reload();
          }, 10000);
          break;
        case 422:
          $("div.login-box").unblock({});
          $("#tfa-div").removeClass("hidden");
          $("#loginform [name=tfaCode]").focus();
          break;
        default:
          message(
            "Login Error",
            "API Connection Failed",
            activeInfo.settings.notifications.position,
            "#FFF",
            "error",
            "10000"
          );
          console.error("Londerland Function: API Connection Failed");
      }
      message(
        "Login Error",
        xhr.responseJSON.response.message,
        activeInfo.settings.notifications.position,
        "#FFF",
        "warning",
        "10000"
      );
      console.error("Londerland Function: " + xhr.responseJSON.response.message);
      local("r", "loggingIn");
    });
}
function ipInfoSpan(ip) {
  return '<span class="ipInfo mouse">' + ip + "</span>";
}
function exportLogs() {
  const query = "api/v2/log/0?filter=NONE&pageSize=1000&offset=0";
  $.get(query, function (data) {
    const logs = data.response.data.results;
    let csvContent =
      "data:text/csv;charset=utf-8,Date,Severity,Function,Message,IP Address,User\n";
    logs.forEach(function (log) {
      const row = [
        log.datetime,
        log.log_level,
        log.channel,
        log.message,
        log.remote_ip_address,
        log.username,
      ].join(",");
      csvContent += row + "\n";
    });
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "londerland_logs.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
}
function logContext(row) {
  let buttons = "";
  buttons +=
    Object.keys(row).length > 0
      ? '<button data-bs-toggle="tooltip" title="" data-bs-title="View Details" class="btn btn-sm btn-primary waves-effect waves-light log-details m-r-5" data-trace="' +
        row.trace_id +
        '"><i class="mdi mdi-file-find"></i></button>'
      : "";
  buttons +=
    Object.keys(row).length > 0
      ? '<button data-bs-toggle="tooltip" title="" data-bs-title="Copy Log" class="btn btn-sm btn-info waves-effect waves-light log-details m-r-5" data-trace="' +
        row.trace_id +
        '" data-clipboard="true"><i class="mdi mdi-content-copy"></i></button>'
      : "";
  return buttons;
}
function formatLogDetails(details) {
  if (!details) {
    return false;
  }
  let m = moment.tz(details.datetime + "Z", activeInfo.timezone);
  details.datetime = moment(m).format("LLL");
  let items = "";
  items += `<li><div class="bg-inverse"><i class="mdi mdi-calendar-text text-white"></i></div> ${details.datetime}<span class="text-muted" lang="en">Date</span></li>`;
  items += `<li><div class="bg-warning"><i class="mdi mdi-robot text-white"></i></div> ${details.trace_id}<span class="text-muted" lang="en">Trace ID</span></li>`;
  items += `<li><div class="bg-primary"><i class="mdi mdi-account-box-outline text-white"></i></div> ${details.username}<span class="text-muted" lang="en">User</span></li>`;
  items += `<li><div class="bg-info"><i class="mdi mdi-function text-white"></i></div> ${details.channel}<span class="text-muted" lang="en">Function</span></li>`;
  items += `<li><div class="bg-plex"><i class="mdi mdi-language-php text-white"></i></div> ${details.file}<code>#L${details.line}</code><span class="text-muted" lang="en">File</span></li>`;
  let items2 = "";
  items2 +=
    Object.keys(details.context).length > 0
      ? `<div class="sl-item"><div class="sl-left bg-inverse"> <i class="mdi mdi-code-json"></i></div><div class="sl-right"><div class="p-t-10 desc" lang="en">Context</div></div><pre class="m-5 fc-scroller">${JSON.stringify(
          details.context,
          null,
          5
        )}</pre></div>`
      : "";
  items2 +=
    typeof details.errors !== "undefined"
      ? `<div class="sl-item"><div class="sl-left bg-danger"> <i class="mdi mdi-code-braces"></i></div><div class="sl-right"><div class="p-t-10 desc" lang="en">Errors</div></div><pre class="m-5 fc-scroller">${JSON.stringify(
          details.errors,
          null,
          5
        )}</pre></div>`
      : "";
  var div = `
		<div class="col-xl-12">
			<div class="card card-default text-start">
				<div class="card-header"><i class="mdi mdi-file-find fa-lg fa-2x"></i> <span lang="en">Log Details</span> <span class="float-end">${logIcon(
          details.log_level,
          true
        )}</span></div>
				<div class="card-wrapper collapse show">
					<div class="card-body bg-org">
						<h3>${details.message}</h3>
						<div class="white-box">
							<ul class="feeds">
								${items}
							</ul>
						</div>
						<div class="steamline">
							${items2}
						</div>
					</div>
				</div>
			</div>
		</div>`;
  Swal.fire({
    html: createElementFromHTML(div),
    showConfirmButton: false,
    customClass: { popup: "orgAlertTransparent" },
  });
  pageLoad();
}
function checkToken(activate = false) {
  if (typeof activeInfo !== "undefined") {
    if (typeof activeInfo.settings.misc.uuid !== "undefined") {
      var token = getCookie("londerland_token_" + activeInfo.settings.misc.uuid);
      if (token) {
        setTimeout(function () {
          checkToken(true);
        }, 5000);
      } else {
        if (activate) {
          local(
            "set",
            "message",
            "Token Expired|You have been logged out|error"
          );
          location.reload();
        }
      }
    }
  }
}
function londerlandConsole(subject, msg, type = "info") {
  let color;
  switch (type) {
    case "error":
      color = "#ed2e72";
      break;
    case "warning":
      color = "#272361";
      break;
    default:
      color = "#2cabe3";
      break;
  }

  console.info(
    "%c " + subject + " %c ".concat(msg, " "),
    "color: white; background: " + color + "; font-weight: 700;",
    "color: " + color + "; background: white; font-weight: 700;"
  );
}
function londerlandCatchError(e, data) {
  londerlandConsole("Londerland API Function", data, "warning");
  orgErrorAlert(
    "<h4>" +
      e +
      '</h4><p><mark lang="en">Trace Log has been outputted to Browser Console</mark></p><h5 lang="en">Output of last API call</h5>' +
      formatDebug(data)
  );
  console.trace();
  return false;
}
function LonderlandApiError(xhr, secondaryMessage = null) {
  let msg = "";
  if (typeof xhr.responseJSON !== "undefined") {
    msg = xhr.responseJSON.response.message;
  } else if (typeof xhr.statusText !== "undefined") {
    msg = xhr.statusText;
  } else if (typeof xhr.responseText !== "undefined") {
    msg = xhr.responseText;
  } else {
    msg = "Connection Error";
  }
  londerlandConsole("Londerland API Function", msg, "error");

  if (msg !== "abort") {
    if (secondaryMessage) {
      messageSingle(
        secondaryMessage,
        msg,
        activeInfo.settings.notifications.position,
        "#FFF",
        "error",
        "10000"
      );
    }
    console.trace();
  }
  return false;
}

function clickSettingsTab() {
  let tabs = $(".allTabsList");
  $.each(tabs, function (i, v) {
    let tab = $(v);
    if (tab.attr("data-url") == "api/v2/page/settings") {
      tab.find("a").trigger("click");
    }
  });
}
function clickMenuItem(selector) {
  if ($(selector).length >= 1) {
    $(selector).click();
  } else {
    $("body").arrive(selector, { onceOnly: true }, function () {
      $(selector).click();
    });
  }
}
function shortcut(selectors = "") {
  let timeout = 200;
  if (typeof selectors == "string") {
    if (selectors == "") {
      selectors = [];
    } else {
      switch (selectors) {
        case "log-settings":
          clickSettingsTab();
          selectors = [
            "#settings-main-system-settings-anchor",
            "#settings-settings-main-anchor",
            'a[href$="Logs"]',
          ];
          break;
        case "custom-cert":
          clickSettingsTab();
          selectors = [
            "#settings-main-system-settings-anchor",
            "#settings-settings-main-anchor",
            'a[href$="Certificate"]',
          ];
          break;
        default:
          clickSettingsTab();
          selectors = ["#settings-main-system-settings-anchor"];
      }
    }
  }
  selectors.forEach(function (selector) {
    timeout = timeout + 200;
    setTimeout(function () {
      clickMenuItem(selector);
    }, timeout);
  });
}
function getJournalMode() {
  londerlandAPI2("GET", "api/v2/database/journal")
    .done(function (data) {
      try {
        let response = data.response;
        $(".journal-mode").html(response.data.journal_mode);
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function setJournalMode(mode) {
  messageSingle(
    "Setting New Journal Mode",
    "",
    activeInfo.settings.notifications.position,
    "#FFF",
    "info",
    "1500"
  );
  londerlandAPI2("PUT", "api/v2/database/journal/" + mode, {})
    .done(function (data) {
      try {
        getJournalMode();
        let response = data.response;
        message(
          "Set New Journal Mode",
          response.data.journal_mode,
          activeInfo.settings.notifications.position,
          "#FFF",
          "success",
          "5000"
        );
      } catch (e) {
        londerlandCatchError(e, data);
      }
    })
    .fail(function (xhr) {
      LonderlandApiError(xhr);
    });
}
function toggleSideMenuClasses() {
  $("#page-wrapper").toggleClass("sidebar-hidden");
  $(".sidebar").toggleClass("sidebar-hidden");
  $(".navbar").toggleClass("sidebar-hidden");
}
function sideMenuCollapsed() {
  if (activeInfo.settings.misc.sideMenuCollapsed) {
    toggleSideMenuClasses();
  }
}
function toggleSideMenu() {
  toggleSideMenuClasses();
  $(".sidebar-head .open-close i")
    .first()
    .toggleClass("ti-menu ti-shift-left mouse");
  $(".toggle-side-menu").toggleClass("hidden");
}

function toggleTopBarHamburger() {
  toggleSideMenuClasses();
  $(".sidebar-head .hide-menu.hidden-xs").text("Hide Menu");
  $(".sidebar-head .open-close i")
    .first()
    .toggleClass("ti-menu ti-shift-left mouse");
  $(".toggle-side-menu").toggleClass("hidden");
}
function toggleLogFilter(filter = "INFO") {
  //choose-londerland-log
  filter = filter.toUpperCase();
  $.each($(".choose-londerland-log").children(), function (i, v) {
    let url = $(v).val();
    let newURL = updateUrlParameter(url, "filter", filter);
    $(v).val(newURL);
  });
  $(".log-filter-text").text(filter);
  $(".log-filter-text").text(filter);
  let currentURL = londerlandLogTable.ajax.url();
  let updatedURL = updateUrlParameter(currentURL, "filter", filter);
  londerlandLogTable.ajax.url(updatedURL);
  londerlandLogTable.clear().draw().ajax.reload(null, false);
}
function updateUrlParameter(uri, key, value) {
  // remove the hash part before operating on the uri
  var i = uri.indexOf("#");
  var hash = i === -1 ? "" : uri.substr(i);
  uri = i === -1 ? uri : uri.substr(0, i);
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf("?") !== -1 ? "&" : "?";
  if (value === null) {
    // remove key-value pair if value is specifically null
    uri = uri.replace(new RegExp("([?&]?)" + key + "=[^&]*", "i"), "");
    if (uri.slice(-1) === "?") {
      uri = uri.slice(0, -1);
    }
    // replace first occurrence of & by ? if no ? is present
    if (uri.indexOf("?") === -1) uri = uri.replace(/&/, "?");
  } else if (uri.match(re)) {
    uri = uri.replace(re, "$1" + key + "=" + value + "$2");
  } else {
    uri = uri + separator + key + "=" + value;
  }
  return uri + hash;
}
// The start-up request index.php sent early, as a jQuery promise like londerlandConnect() returns;
// falls back to a normal request when there is none (or it was already used)
function londerlandLaunchConnect() {
  let early = window.londerlandLaunchRequest;
  window.londerlandLaunchRequest = null;
  if (!early) {
    return londerlandConnect("api/v2/launch");
  }
  let deferred = $.Deferred();
  early
    .then(function (response) {
      return response.text().then(function (text) {
        let xhr = { status: response.status, responseText: text };
        // Like $.ajax: JSON becomes an object, anything else (such as "upgrading") stays text
        let data = text;
        try {
          data = JSON.parse(text);
        } catch (e) {}
        if (response.ok) {
          deferred.resolve(data, "success", xhr);
        } else {
          deferred.reject(xhr, "error", response.statusText);
        }
      });
    })
    .catch(function (error) {
      deferred.reject({ status: 0, responseText: String(error) }, "error", error);
    })
    .finally(function () {
      // A request through $.ajax would have run this from ajaxComplete
      pageLoad();
    });
  return deferred.promise();
}
function launch() {
  londerlandLaunchConnect()
    .done(function (data) {
      try {
        let json = data.response;
        if (json.data.user == false) {
          location.reload();
        }
        currentVersion = json.data.version;
        activeInfo = {
          tabs: json.data.tabs,
          categories: json.data.categories,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
          offest: new Date().getTimezoneOffset(),
          language: language(moment.locale(navigator.languages[0])),
          browserVersion: browserInfo.version,
          browserName: browserInfo.name,
          mobile: browserInfo.mobile,
          tablet: browserInfo.tablet,
          osName: browserInfo.osname,
          osVersion: browserInfo.osversion,
          serverOS: json.data.status.os,
          phpVersion: json.data.status.php,
          token: json.data.user.token,
          user: json.data.user,
          plugins: json.data.plugins,
          sso: json.data.sso,
          settings: json.data.settings,
          appearance: json.data.appearance,
          theme: json.data.theme,
          style: json.data.style,
          version: json.data.version,
        };
        // Add element to signal activeInfo Ready
        $("#wrapper").after('<div id="activeInfo"></div>');
        console.info(
          "%c Londerland %c ".concat(currentVersion, " "),
          "color: white; background: #66D9EF; font-weight: 700; font-size: 24px; font-family: Monospace;",
          "color: #66D9EF; background: white; font-weight: 700; font-size: 24px; font-family: Monospace;"
        );
        console.info(
          "%c Status %c ".concat("Starting Up...", " "),
          "color: white; background: #F92671; font-weight: 700;",
          "color: #F92671; background: white; font-weight: 700;"
        );
        //local('set','initial',true);
        //setTimeout(function(){ local('r','initial'); }, 300);
        defineNotification();
        checkMessage();
        errorPage();
        uriRedirect();
        changeStyle(activeInfo.style);
        //changeTheme(activeInfo.theme);
        setSSO();
        checkToken();
        switch (json.data.status.status) {
          case "wizard":
            buildWizard();
            break;
          case "dependencies":
            buildDependencyCheck(json);
            break;
          case "ok":
            loadAppearance(json.data.appearance);
            sideMenuCollapsed();
            if (activeInfo.user.locked == 1) {
              buildLockscreen();
            } else {
              userMenu(json);
              categoryProcess(json);
              tabProcess(json);
              buildSplashScreen(json);
              accountManager(json);
              getPingList(json);
              checkLocalForwardStatus(json.data);
            }
            loadCustomJava(json.data.appearance);
            if (getCookie("lockout")) {
              $(".show-login").click();
              setTimeout(function () {
                $("div.login-box").block({
                  message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
                  css: {
                    color: "#fff",
                    border: "1px solid #e91e63",
                    backgroundColor: "#f44336",
                  },
                });
              }, 1000);
              setTimeout(function () {
                location.reload();
              }, 60000);
            }
            break;
          default:
            console.error("Londerland Function: Action not set or defined");
        }
        console.info(
          "%c Londerland %c ".concat("DOM Fully loaded", " "),
          "color: white; background: #AD80FD; font-weight: 700;",
          "color: #AD80FD; background: white; font-weight: 700;"
        );
        oAuthLoginNeededCheck();
        bypassLocalLogin();
      } catch (e) {
        orgErrorCode(data);
        defineNotification();
        message("FATAL ERROR", data, "br", "#FFF", "error", "60000");
        console.warn(data);
        console.warn(e);
        return false;
      }
    })
    .fail(function (xhr) {
      defineNotification();
      if (xhr.status == 404) {
        orgErrorAlert(
          '<h2>Webserver not set up for Londerland</h2><h4>Requests to api/v2 must be passed to api/v2/index.php (see the rewrite rules in .htaccess)</h4><h3>Webserver Error:</h3>' +
            xhr.responseText
        );
        message(
          "FATAL ERROR",
          "You need to update webserver location block... check browser console for migration URL",
          "br",
          "#FFF",
          "error",
          "60000"
        );
      } else {
        orgErrorAlert("<h3>Webserver Error:</h3>" + xhr.responseText);
      }
    });
}

