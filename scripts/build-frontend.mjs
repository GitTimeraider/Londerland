// Copies the frontend libraries Organizr loads from node_modules into assets/vendor
// and minifies Organizr's own stylesheets and scripts. Run with: npm ci && npm run build
import { cpSync, existsSync, mkdirSync, rmSync, writeFileSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const modules = join(root, 'node_modules');
const vendor = join(root, 'assets', 'vendor');

// [package path inside node_modules, destination inside assets/vendor]
const assets = [
	['bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
	['bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'],
	['jquery/dist/jquery.min.js', 'jquery/jquery.min.js'],
	['@fortawesome/fontawesome-free/css/all.min.css', 'fontawesome/css/all.min.css'],
	['@fortawesome/fontawesome-free/css/v4-shims.min.css', 'fontawesome/css/v4-shims.min.css'],
	['@fortawesome/fontawesome-free/webfonts', 'fontawesome/webfonts'],
	['@mdi/font/css/materialdesignicons.min.css', 'mdi/css/materialdesignicons.min.css'],
	['@mdi/font/fonts', 'mdi/fonts'],
	['simple-line-icons/css/simple-line-icons.css', 'simple-line-icons/css/simple-line-icons.css'],
	['simple-line-icons/fonts', 'simple-line-icons/fonts'],
	['metismenu/dist/metisMenu.min.js', 'metismenu/metisMenu.min.js'],
	['metismenu/dist/metisMenu.min.css', 'metismenu/metisMenu.min.css'],
	['moment/min/moment-with-locales.min.js', 'moment/moment-with-locales.min.js'],
	['moment-timezone/builds/moment-timezone-with-data.min.js', 'moment/moment-timezone-with-data.min.js'],
	['bowser/bundled.js', 'bowser/bowser.js'],
	['js-cookie/dist/js.cookie.min.js', 'js-cookie/js.cookie.min.js'],
	['arrive/minified/arrive.min.js', 'arrive/arrive.min.js'],
	['vanilla-lazyload/dist/lazyload.min.js', 'vanilla-lazyload/lazyload.min.js'],
	['ace-builds/src-min-noconflict', 'ace'],
	['datatables.net/js/dataTables.min.js', 'datatables/dataTables.min.js'],
	['datatables.net-bs5/js/dataTables.bootstrap5.min.js', 'datatables/dataTables.bootstrap5.min.js'],
	['datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'datatables/dataTables.bootstrap5.min.css'],
	['magnific-popup/dist/jquery.magnific-popup.min.js', 'magnific-popup/jquery.magnific-popup.min.js'],
	['magnific-popup/dist/magnific-popup.css', 'magnific-popup/magnific-popup.css'],
	['sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2/sweetalert2.all.min.js'],
	['alertifyjs/build/alertify.min.js', 'alertifyjs/alertify.min.js'],
	['alertifyjs/build/css', 'alertifyjs/css'],
	['tinycolor2/dist/tinycolor-min.js', 'tinycolor2/tinycolor-min.js'],
	['@simonwep/pickr/dist/pickr.min.js', 'pickr/pickr.min.js'],
	['@simonwep/pickr/dist/themes/nano.min.css', 'pickr/nano.min.css'],
	['dropzone/dist/dropzone-min.js', 'dropzone/dropzone-min.js'],
	['dropzone/dist/dropzone.css', 'dropzone/dropzone.css'],
	['swiper/swiper-bundle.min.js', 'swiper/swiper-bundle.min.js'],
	['swiper/swiper-bundle.min.css', 'swiper/swiper-bundle.min.css'],
	['fullcalendar/all/global.js', 'fullcalendar/fullcalendar.global.js'],
	['fullcalendar/skeleton.css', 'fullcalendar/skeleton.css'],
	['fullcalendar/themes/classic/global.js', 'fullcalendar/theme-classic.global.js'],
	['fullcalendar/themes/classic/theme.css', 'fullcalendar/theme.css'],
	['fullcalendar/themes/classic/palette.css', 'fullcalendar/palette.css'],
	['fullcalendar/locales-all/global.js', 'fullcalendar/locales-all.global.js'],
	['tom-select/dist/js/tom-select.complete.min.js', 'tom-select/tom-select.complete.min.js'],
	['tom-select/dist/css/tom-select.bootstrap5.min.css', 'tom-select/tom-select.bootstrap5.min.css'],
	['tinymce', 'tinymce'],
	['tinykeys/dist/tinykeys.umd.js', 'tinykeys/tinykeys.umd.js'],
	['easy-pie-chart/dist/jquery.easypiechart.min.js', 'easy-pie-chart/jquery.easypiechart.min.js'],
	['tabulator-tables/dist/js/tabulator.min.js', 'tabulator/tabulator.min.js'],
	['tabulator-tables/dist/css/tabulator_bootstrap5.min.css', 'tabulator/tabulator_bootstrap5.min.css'],
	['gaugeJS/dist/gauge.min.js', 'gaugejs/gauge.min.js'],
	['sortablejs/Sortable.min.js', 'sortablejs/Sortable.min.js'],
	['overlayscrollbars/browser/overlayscrollbars.browser.es6.min.js', 'overlayscrollbars/overlayscrollbars.browser.es6.min.js'],
	['overlayscrollbars/styles/overlayscrollbars.min.css', 'overlayscrollbars/overlayscrollbars.min.css'],
	['pusher-js/dist/web/pusher.min.js', 'pusher-js/pusher.min.js'],
	['rapidoc/dist/rapidoc-min.js', 'rapidoc/rapidoc-min.js'],
	['swagger-ui-dist/swagger-ui-bundle.js', 'swagger-ui/swagger-ui-bundle.js'],
	['swagger-ui-dist/swagger-ui-standalone-preset.js', 'swagger-ui/swagger-ui-standalone-preset.js'],
	['swagger-ui-dist/swagger-ui.css', 'swagger-ui/swagger-ui.css'],
	['@highlightjs/cdn-assets/highlight.min.js', 'highlightjs/highlight.min.js'],
	['@highlightjs/cdn-assets/styles/github-dark.min.css', 'highlightjs/github-dark.min.css'],
	['marked/lib/marked.umd.js', 'marked/marked.umd.js'],
	['dompurify/dist/purify.min.js', 'dompurify/purify.min.js'],
	['@highlightjs/cdn-assets/styles/default.min.css', 'highlightjs/default.min.css'],
];

// Organizr's own files: [source, minified output]
const ownFiles = [
	['css/dark.css', 'css/dark.min.css'],
	['css/organizr.css', 'css/organizr.min.css'],
	['js/custom.js', 'js/custom.min.js'],
];

rmSync(vendor, { recursive: true, force: true });
for (const [from, to] of assets) {
	const source = join(modules, from);
	if (!existsSync(source)) {
		throw new Error(`Missing ${from} - is the package installed (npm ci)?`);
	}
	mkdirSync(dirname(join(vendor, to)), { recursive: true });
	cpSync(source, join(vendor, to), { recursive: true });
}

for (const [from, to] of ownFiles) {
	const loader = from.endsWith('.css') ? 'css' : 'js';
	const result = await esbuild.transform(readFileSync(join(root, from), 'utf8'), { loader, minify: true, legalComments: 'none' });
	writeFileSync(join(root, to), result.code);
}

// Material Design Icons renamed many icons over the years; keep the old names (stored in users' tab and
// bookmark settings) working by mapping every alias to its current glyph
const mdiMeta = JSON.parse(readFileSync(join(modules, '@mdi/svg/meta.json'), 'utf8'));
const mdiNames = new Set(mdiMeta.map((icon) => icon.name));
const mdiAliases = new Map();
for (const icon of mdiMeta) {
	for (const alias of icon.aliases) {
		// an alias can be listed on several icons; the first (oldest) one is what it used to mean
		if (!mdiNames.has(alias) && !mdiAliases.has(alias)) {
			mdiAliases.set(alias, `.mdi-${alias}::before{content:"\\${icon.codepoint}"}`);
		}
	}
}
writeFileSync(join(vendor, 'mdi/css/materialdesignicons-aliases.min.css'), [...mdiAliases.values()].join(''));

// Icon picker list (api/v2/icon): the fixed sets in js/icons.json plus every icon of the bundled Font Awesome and MDI fonts
const pickerSets = JSON.parse(readFileSync(join(root, 'js/icons.json'), 'utf8'));
const faMeta = JSON.parse(readFileSync(join(modules, '@fortawesome/fontawesome-free/metadata/icon-families.json'), 'utf8'));
const faIcons = Object.entries(faMeta).flatMap(([name, icon]) => {
	const styles = (icon.familyStylesByLicense?.free ?? []).map((free) => free.style);
	if (styles.includes('solid')) {
		return [{ id: `fontawesome::${name}`, text: name }];
	}
	return styles.includes('brands') ? [{ id: `fontawesome-brands::${name}`, text: name }] : [];
});
const iconSets = pickerSets.map((set) => {
	if (set.text === 'Font Awesome') {
		return { text: set.text, children: faIcons };
	}
	if (set.text === 'Materialize') {
		return { text: set.text, children: mdiMeta.filter((icon) => !icon.deprecated).map((icon) => ({ id: `materialize::${icon.name}`, text: icon.name })) };
	}
	return set;
});
writeFileSync(join(vendor, 'icons.json'), JSON.stringify(iconSets));

console.log(`Copied ${assets.length} vendor assets, minified ${ownFiles.length} Organizr files, ${mdiAliases.size} icon aliases`);
