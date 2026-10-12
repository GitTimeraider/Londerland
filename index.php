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
        // for the libraries londerlandLoadLibrary() (helpers.js) fetches later
        window.londerlandVersion = <?php echo json_encode((string)$Londerland->fileHash, JSON_HEX_TAG); ?>;
        window.londerlandMomentLocale = <?php echo json_encode($Londerland->momentLocale()); ?>;
    </script>
	<?php echo $Londerland->startTabPreconnect(); ?>
    <link href="assets/vendor/bootstrap/bootstrap.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/all.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/v4-shims.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <!-- Icon sets that only menu items and pages built later use: they do not hold up the first paint, and media="print" makes
         the browser fetch them after the stylesheets that do (as rel="preload" they took bandwidth from those) -->
    <link href="assets/vendor/mdi/css/materialdesignicons.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="assets/vendor/mdi/css/materialdesignicons-aliases.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="assets/vendor/simple-line-icons/css/simple-line-icons.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="assets/vendor/mdi/css/materialdesignicons.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
        <link href="assets/vendor/mdi/css/materialdesignicons-aliases.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
        <link href="assets/vendor/simple-line-icons/css/simple-line-icons.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    </noscript>
    <link href="assets/vendor/metismenu/metisMenu.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <!-- Styles of parts that only appear after start-up (pop-ups) load without holding up the first paint; selects, notifications,
         colour pickers, uploads, tables and drag-and-drop sorting are fetched with their scripts by londerlandLoadLibrary() -->
    <link href="assets/vendor/magnific-popup/magnific-popup.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="assets/vendor/overlayscrollbars/overlayscrollbars.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link id="style" href="css/<?php echo (($Londerland->config['style'] ?? '') === 'light') ? 'light' : 'dark'; ?>.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="css/londerland.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
	<?php echo $Londerland->pluginFiles('css'); ?>
	<?php echo $Londerland->setTheme(); ?>
    <!-- Fonts the top bar needs right away (text and its icons). Asked for after the stylesheets above, which the first paint
         waits for; the stylesheets would only ask for them once loaded. Font Awesome (side menu icons, 137 KiB) is not preloaded:
         Chrome held back the first paint while it was still loading (Lighthouse: first paint ~1.2 s later in most runs), and
         the side menu, built at start-up, asks for it anyway. -->
    <link rel="preload" href="<?php echo $Londerland->assetUrl('css/fonts/rubik/files/rubik-latin-wght-normal.woff2'); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo $Londerland->assetUrl('less/icons/themify-icons/fonts/themify.woff2'); ?>" as="font" type="font/woff2" crossorigin>
	<?php echo $Londerland->appearanceCSS(); ?>
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
    <nav class="navbar navbar-default navbar-static-top m-b-0">
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
            <ul class="nav navbar-top-links navbar-right float-end"><?php echo $Londerland->topBarUserHTML(); ?></ul>
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
    <!-- Listed here rather than with the scripts below: startTabFrame() gives #page-wrapper its scrollbars before it adds the
         start tab's iframe, because setting them up later moves the iframe, and a moved iframe loads its site a second time -->
    <script src="assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js?v=<?php echo $Londerland->fileHash; ?>" defer></script>
	<?php echo $Londerland->startTabFrame(); ?>
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
<!-- Start-up scripts, as one file (startupScripts in scripts/build-frontend.mjs): jQuery, Bootstrap, metisMenu, Bowser, arrive,
     vanilla-lazyload, Magnific Popup, tinykeys and Londerland's i18n, helpers, functions and custom. With defer it downloads while
     the page is drawn and runs once the page has been read. Libraries start-up does not need come via londerlandLoadLibrary(). -->
<script src="js/londerland.min.js?v=<?php echo $Londerland->fileHash; ?>" defer></script>
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