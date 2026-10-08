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
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/v4-shims.min.css" rel="stylesheet">
    <link href="assets/vendor/mdi/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="assets/vendor/mdi/css/materialdesignicons-aliases.min.css" rel="stylesheet">
    <link href="assets/vendor/simple-line-icons/css/simple-line-icons.css" rel="stylesheet">
    <link href="assets/vendor/metismenu/metisMenu.min.css" rel="stylesheet">
    <link href="assets/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/vendor/magnific-popup/magnific-popup.css" rel="stylesheet">
    <link href="assets/vendor/dropzone/dropzone.css" rel="stylesheet">
    <link href="assets/vendor/fullcalendar/skeleton.css" rel="stylesheet">
    <link href="assets/vendor/fullcalendar/theme.css" rel="stylesheet">
    <link href="assets/vendor/fullcalendar/palette.css" rel="stylesheet">
    <link href="assets/vendor/pickr/nano.min.css" rel="stylesheet">
    <link href="assets/vendor/tom-select/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/vendor/tabulator/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link href="assets/vendor/overlayscrollbars/overlayscrollbars.min.css" rel="stylesheet">
    <link href="assets/vendor/alertifyjs/css/alertify.min.css" rel="stylesheet">
    <link href="assets/vendor/alertifyjs/css/themes/default.min.css" rel="stylesheet">
    <link id="style" href="css/<?php echo (($Londerland->config['style'] ?? '') === 'light') ? 'light' : 'dark'; ?>.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
    <link href="css/londerland.min.css?v=<?php echo $Londerland->fileHash; ?>" rel="stylesheet">
	<?php echo $Londerland->pluginFiles('css'); ?>
	<?php echo $Londerland->setTheme(); ?>
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
                    <span class="hidden-xs elip" id="main-logo"></span>
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
                <li class=""><a class="dropdown-toggle waves-effect waves-light" onclick="openInNewBrowserTab();"> <i
                                class="ti-arrow-top-right"></i></a></li>
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
                    <span class="hide-menu hidden-sm hidden-md hidden-lg" id="side-logo"></span>
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
<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/metismenu/metisMenu.min.js"></script>
<script src="assets/vendor/moment/moment-with-locales.min.js"></script>
<script src="assets/vendor/moment/moment-timezone-with-data.min.js"></script>
<script src="assets/vendor/bowser/bowser.js"></script>
<script src="assets/vendor/js-cookie/js.cookie.min.js"></script>
<script src="assets/vendor/arrive/arrive.min.js"></script>
<script src="assets/vendor/vanilla-lazyload/lazyload.min.js"></script>
<script src="assets/vendor/ace/ace.js"></script>
<script src="assets/vendor/datatables/dataTables.min.js"></script>
<script src="assets/vendor/datatables/dataTables.bootstrap5.min.js"></script>
<script src="assets/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
<script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="assets/vendor/alertifyjs/alertify.min.js"></script>
<script src="assets/vendor/tinycolor2/tinycolor-min.js"></script>
<script src="assets/vendor/pickr/pickr.min.js"></script>
<script src="assets/vendor/dropzone/dropzone-min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/fullcalendar/fullcalendar.global.js"></script>
<script src="assets/vendor/fullcalendar/theme-classic.global.js"></script>
<script src="assets/vendor/fullcalendar/locales-all.global.js"></script>
<script src="assets/vendor/tom-select/tom-select.complete.min.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/tinykeys/tinykeys.umd.js"></script>
<script src="assets/vendor/easy-pie-chart/jquery.easypiechart.min.js"></script>
<script src="assets/vendor/tabulator/tabulator.min.js"></script>
<script src="assets/vendor/gaugejs/gauge.min.js"></script>
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>
<script src="assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js"></script>
<script src="assets/vendor/pusher-js/pusher.min.js"></script>
<script src="js/i18n.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/helpers.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/functions.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script src="js/custom.min.js?v=<?php echo $Londerland->fileHash; ?>"></script>
<script id="custom-theme-javascript"></script>
<script id="custom-javascript"></script>
<?php
echo $Londerland->googleTracking();
echo $Londerland->pluginFiles('js');
echo $Londerland->formKey();
echo $Londerland->loadCalendarJS();
echo $Londerland->CBPFWTabs();
?>
</body>

</html>