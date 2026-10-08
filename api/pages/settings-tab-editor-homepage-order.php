<?php
$GLOBALS['londerlandPages'][] = 'settings_tab_editor_homepage_order';
function get_page_settings_tab_editor_homepage_order($Londerland)
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
    new Sortable(document.getElementById("homepage-items-sort"), {
        draggable: ".sort-homepage",
        ghostClass: "sort-placeholder",
        animation: 150,
        onEnd: function() {
            $("#homepage-items-sort .sort-homepage").each(function(idx) {
                const position = $(this).find(".ordinal-position").text(idx + 1);
                $("#homepage-values [name=" + position.attr("data-link") + "]").val(idx + 1).attr("data-changed", "true");
            });
            $("#submitHomepageOrder-save").removeClass("hidden");
        }
    });
</script>
<div class="card bg-org card-info">
    <div class="card-header">
		<span lang="en">Homepage Order</span>
        <button id="submitHomepageOrder-save" type="button" class="btn btn-sm btn-info rounded-pill float-end animated loop-animation rubberBand hidden" onclick="submitHomepageOrder()" ><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="card-wrapper collapse show" aria-expanded="true">
        <div class="card-body bg-org" >
        <div class="row el-element-overlay m-b-40" id="settings-homepage-order">' . $Londerland->buildHomepageSettings() . '</div>
        </div>
    </div>
</div>

';
}
