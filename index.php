<?php
include 'api/functions.php';
$Londerland = new Londerland(true);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark" ontouchmove>

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"
          name="viewport">
    <meta content="<?php echo $Londerland->config['description']; ?>" name="description">
    <meta content="Londerland" name="author">
	<?php echo $Londerland->favIcons(); ?>
    <title><?php echo $Londerland->config['title']; ?></title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="application-name" content="<?php echo $Londerland->config['title']; ?>">
    <meta name="apple-mobile-web-app-title" content="<?php echo $Londerland->config['title']; ?>">
    <script>
        // Ask for the start-up data now, while the scripts below are still downloading; launch() picks it up
        window.londerlandLaunchRequest = window.fetch ? fetch('api/v2/launch', {credentials: 'same-origin', headers: {'Accept': 'application/json'}}) : null;
    </script>
	<?php echo $Londerland->startTabPreconnect(); ?>
    <!-- Fonts the menu icons and text need right away (the largest item of the first screen is often a menu icon) -->
    <link rel="preload" href="assets/vendor/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="assets/vendor/fontawesome/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="css/fonts/rubik/files/rubik-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link href="assets/vendor/bootstrap/bootstrap.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/all.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/v4-shims.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <!-- Icon sets that only menu items and pages built later use: fetched right away, but they do not hold up the first paint -->
    <link href="assets/vendor/mdi/css/materialdesignicons.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link href="assets/vendor/mdi/css/materialdesignicons-aliases.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link href="assets/vendor/simple-line-icons/css/simple-line-icons.css?v=<?php echo $Londerland->fileHash; ?>" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="assets/vendor/mdi/css/materialdesignicons.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
        <link href="assets/vendor/mdi/css/materialdesignicons-aliases.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
        <link href="assets/vendor/simple-line-icons/css/simple-line-icons.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    </noscript>
    <link href="assets/vendor/metismenu/metisMenu.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <!-- Styles of parts that only appear after start-up (pop-ups, selects, notifications) load without holding up the first paint;
         colour pickers, uploads, tables and drag-and-drop sorting are fetched with their scripts by londerlandLoadLibrary() -->
    <link href="assets/vendor/magnific-popup/magnific-popup.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="assets/vendor/tom-select/tom-select.bootstrap5.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="assets/vendor/overlayscrollbars/overlayscrollbars.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="assets/vendor/alertifyjs/css/alertify.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="assets/vendor/alertifyjs/css/themes/default.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link id="style" href="css/<?php echo (($Londerland->config['style'] ?? '') === 'light') ? 'light' : 'dark'; ?>.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="css/londerland.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
	<?php echo $Londerland->pluginFiles('css'); ?>
	<?php echo $Londerland->setTheme(); ?>
	<?php echo $Londerland->fontCSS(); ?>
    <style id="user-appearance"></style>
    <style id="custom-theme-css"></style>
    <style id="custom-css"></style>
</head>

<body class="fix-header" data-active-tab="" tabIndex=0>
<!-- ============================================================== -->
<!-- Preloader -->
<!-- ============================================================== -->
<div id="preloader" class="preloader">
    <svg class="circular" viewbox="25 25 50 50">
        <circle class="path" cx="50" cy="50" fill="none" r="20" stroke-miterlimit="10" stroke-width="10"></circle>
    </svg>
</div>
<!-- ============================================================== -->
<!-- Wrapper -->
<!-- ============================================================== -->
<div id="wrapper">
    <!-- ============================================================== -->
    <!-- Topbar header - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <nav class="navbar navbar-default navbar-static-top m-b-0 animated slideInDown">
        <div class="navbar-header">
            <div class="top-left-part hidden-xs p-r-10">
				<?php echo $Londerland->showTopBarHamburger(); ?>
                <!-- Logo -->
                <a class="logo" href="javascript:void(0)">
                    <!-- Logo text image you can use text also -->
                    <span class="hidden-xs elip" id="main-logo"><?php echo $Londerland->logoHTML('dark-logo'); ?></span>
                </a>
            </div>
            <!-- /Logo -->
            <!-- Search input and Toggle icon -->
            <ul class="nav navbar-top-links navbar-left">
                <li><a class="open-close waves-effect waves-light visible-xs" href="javascript:void(0)"><i
                                class="ti-close ti-menu fa-fw"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light" onclick="reloadCurrentTab();"> <i
                                class="ti-reload"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light" onclick="closeCurrentTab(event);"> <i
                                class="ti-close"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light open-in-new-tab" onclick="openInNewBrowserTab();" title="Open in a new browser tab"> <i
                                class="ti-new-window"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light hidden" onclick="splashMenu();"> <i
                                class="ti-layout-grid2"></i></a></li>
            </ul>
            <ul class="nav navbar-top-links navbar-right float-end"></ul>
        </div>
        <!-- /.navbar-header -->
        <!-- /.navbar-top-links -->
        <!-- /.navbar-static-side -->
        <div class="dropdown-menu animated bounceInDown bg-danger text-white" id="main-org-error-container">
            <div class="mega-dropdown-menu row">
                <div class="col-xl-12 mb-4">
                    <h3 class="mb-3 float-start"><i class="fa fa-close text-white"></i>&nbsp; <span lang="en">An Error Occurred</span>
                    </h3>
                    <h3 class="mb-3 float-end mouse" onclick="closeOrgError();"><i
                                class="fa fa-check text-success"></i>&nbsp;
                        <span lang="en">Close Error</span>
                    </h3>
                    <br/>
                    <br/>
                    <div class="m-t-20" id="main-org-error"></div>
                </div>
            </div>
        </div>
    </nav>
    <!-- End Top Navigation -->
    <!-- ============================================================== -->
    <!-- Left Sidebar - style you can find in sidebar.scss  -->
    <!-- ============================================================== -->
    <div class="navbar-default sidebar nav-bar-rtl" role="navigation">
        <div class="sidebar-nav">
            <div class="sidebar-head">
                <h3>
                    <span class="open-close m-r-5">
                        <?php echo $Londerland->showSideBarHamburger(); ?>
                        <i class="ti-close visible-xs"></i>
                    </span>
					<?php echo $Londerland->showSideBarText(); ?>
                    <span class="hide-menu hidden-sm hidden-md hidden-lg" id="side-logo"><?php echo $Londerland->logoHTML('dark-logo-side'); ?></span>
                </h3>
            </div>
            <ul class="nav" id="side-menu">
                <li class="side-menu-top-sort"></li>
                <li class="side-menu-bottom-sort"></li>
            </ul>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Left Sidebar -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Page Content -->
    <!-- ============================================================== -->
    <div class="error-page bg-org"></div>
    <div class="login-area hidden"></div>
    <div class="p-0" id="page-wrapper">
        <div class="londerland-area hidden"></div>
        <div class="plugin-listing p-0 hidden"></div>
        <div class="internal-listing p-0 hidden"></div>
        <div class="iFrame-listing p-0 hidden"></div>
    </div>
    <div class="splitRight hidden" id="page-wrapper-right">
        <div class="londerland-area-right"></div>
        <div class="plugin-listing-right p-0 hidden"></div>
        <div class="internal-listing-right p-0 hidden"></div>
        <div class="iFrame-listing-right p-0 hidden"></div>
    </div>
    <!-- help modal content -->
    <div class="modal fade help-modal-lg" tabindex="-1" role="dialog" aria-labelledby="help-modal-lg" aria-hidden="true"
         style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h4 class="modal-title" id="help-modal-title" lang="en">Large modal</h4></div>
                <div class="modal-body" id="help-modal-body"></div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
    <!-- ============================================================== -->
    <!-- End Page Content -->
    <!-- ============================================================== -->
    <a href="#" id="scroll" style="display: none;"><span></span></a>
    <button id="internal-clipboard" class="hidden"></button>
	<?php echo $Londerland->inconspicuous(); ?>
</div>
<!-- /#wrapper -->
<script src="assets/vendor/jquery/jquery.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/metismenu/metisMenu.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/moment/moment-with-locales.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/moment/moment-timezone-with-data-10-year-range.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/bowser/bowser.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/js-cookie/js.cookie.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/arrive/arrive.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/vanilla-lazyload/lazyload.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/magnific-popup/jquery.magnific-popup.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/sweetalert2/sweetalert2.all.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/alertifyjs/alertify.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/tom-select/tom-select.complete.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/tinykeys/tinykeys.umd.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/i18n.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/helpers.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/functions.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/custom.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script id="custom-theme-javascript"></script>
<script id="custom-javascript"></script>
<?php
echo $Londerland->googleTracking();
echo $Londerland->pluginFiles('js');
echo $Londerland->formKey();
echo $Londerland->CBPFWTabs();
?>
</body>

</html>