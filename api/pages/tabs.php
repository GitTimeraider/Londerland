<?php
$GLOBALS['organizrPages'][] = 'tabs';
function get_page_tabs($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	return '
<script>
</script>
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-xl-3 col-lg-4 col-md-4 col-12">
            <h4 class="page-title" lang="en">No Tabs Available</h4>
        </div>
        <!-- /.col-xl-12 -->
    </div>
    <!--.row-->
    <div class="row">
        <div class="col-xl-12">
            <div class="card card-warning">
                <div class="card-header"> <i class="ti-alert fa-fw"></i> <span lang="en">No Tabs Available</span></div>
                <div class="card-wrapper collapse show" aria-expanded="true">
                    <div class="card-body">
                        <p lang="en">There are no available tabs for your group - please contact the Administrator</p>
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