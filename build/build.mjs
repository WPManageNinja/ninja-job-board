/**
 * Builds all plugin assets: the admin app (one ES module), the classic IIFE scripts that
 * WordPress enqueues, and the stand-alone stylesheets. `npm run dev` rebuilds the admin app on change.
 */
import { build } from 'vite';
import vue from '@vitejs/plugin-vue';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import * as sass from 'sass';
import { cpSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const watch = process.argv.includes('--watch');
const path = (p) => resolve(root, p);

async function buildAdminApp() {
    await build({
        root,
        configFile: false,
        base: './',
        logLevel: 'warn',
        plugins: [
            vue(),
            AutoImport({ resolvers: [ElementPlusResolver()], dts: false }),
            Components({ resolvers: [ElementPlusResolver({ importStyle: 'css' })], dts: false })
        ],
        resolve: {
            alias: {
                // vuedraggable's UMD build requires 'vue', which would pull in the template compiler
                vue: path('node_modules/vue/dist/vue.runtime.esm-bundler.js')
            }
        },
        define: {
            __VUE_OPTIONS_API__: 'true',
            __VUE_PROD_DEVTOOLS__: 'false',
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false'
        },
        build: {
            outDir: path('assets/admin'),
            emptyOutDir: false,
            cssCodeSplit: false,
            sourcemap: false,
            target: 'es2019',
            watch: watch ? {} : null,
            rollupOptions: {
                input: path('src/admin/main.js'),
                output: {
                    format: 'es',
                    inlineDynamicImports: true,
                    entryFileNames: 'app.js',
                    assetFileNames: (asset) => asset.names?.some((n) => n.endsWith('.css')) ? 'app.css' : 'fonts/[name][extname]'
                }
            }
        }
    });
}

/**
 * @param {object} options jsx: compile JSX to wp.element (block editor scripts);
 *                         minify: false keeps __() calls readable for translation tools
 */
async function buildClassicScript(entry, outFile, name, options = {}) {
    await build({
        root,
        configFile: false,
        logLevel: 'warn',
        esbuild: options.jsx ? {
            jsx: 'transform',
            jsxFactory: 'wp.element.createElement',
            jsxFragment: 'wp.element.Fragment'
        } : {},
        // Library mode keeps process.env as is; @wordpress/hooks reads it
        define: {
            'process.env.NODE_ENV': JSON.stringify('production')
        },
        build: {
            outDir: path(dirname(outFile)),
            emptyOutDir: false,
            sourcemap: false,
            minify: options.minify === false ? false : 'esbuild',
            target: 'es2019',
            lib: {
                entry: path(entry),
                name,
                formats: ['iife'],
                fileName: () => outFile.split('/').pop()
            }
        }
    });
}

function buildStylesheet(entry, outFile) {
    const result = sass.compile(path(entry), {
        style: 'compressed',
        loadPaths: [path('node_modules')],
        silenceDeprecations: ['import', 'global-builtin']
    });
    mkdirSync(dirname(path(outFile)), { recursive: true });
    writeFileSync(path(outFile), result.css);
}

// Only one build at a time: parallel builds write the same files
const lockDir = path('build/.lock');
for (let attempt = 0; ; attempt++) {
    try {
        mkdirSync(lockDir);
        break;
    } catch (e) {
        if (attempt > 600) {
            throw new Error('Another build holds build/.lock. Remove it if no build is running.');
        }
        await new Promise((r) => setTimeout(r, 500));
    }
}
const releaseLock = () => rmSync(lockDir, { recursive: true, force: true });
process.on('exit', releaseLock);
process.on('SIGINT', () => process.exit(130));

await buildClassicScript('src/admin/boot.js', 'assets/admin/boot.js', 'WPJobBoardBoot');
await buildClassicScript('src/public/public.js', 'assets/js/jobboard-public.js', 'WPJobBoardPublic');
await buildClassicScript('src/public/fileupload.js', 'assets/js/fileupload.js', 'WPJobBoardFileUpload');
await buildClassicScript('src/integrations/tinymce.js', 'assets/js/tinymce.js', 'WPJobBoardTinyMce');

// Gutenberg blocks (WordPress provides React and the editor packages as wp.* globals)
await buildClassicScript('src/blocks/index.jsx', 'assets/blocks/blocks.js', 'WPJobBoardBlocks', { jsx: true, minify: false });
await buildClassicScript('src/blocks/job-editor.jsx', 'assets/blocks/job-editor.js', 'WPJobBoardJobEditor', { jsx: true, minify: false });
buildStylesheet('src/blocks/editor.scss', 'assets/blocks/editor.css');
for (const block of ['job-list', 'application-form', 'job-details']) {
    mkdirSync(path('assets/blocks/' + block), { recursive: true });
    cpSync(path('src/blocks/' + block + '/block.json'), path('assets/blocks/' + block + '/block.json'));
}

buildStylesheet('src/scss/admin/app_edit.scss', 'assets/css/jobboard-edit.css');
buildStylesheet('src/scss/admin/jobboard-print.scss', 'assets/css/jobboard-print.css');
buildStylesheet('src/scss/public/public.scss', 'assets/css/wp_job_board-public.css');
buildStylesheet('src/scss/public/joblist.scss', 'assets/css/joblist.css');

cpSync(path('src/images'), path('assets/images'), { recursive: true });
// Public form libraries, pinned in package.json: ship only the minified files
rmSync(path('assets/libs'), { recursive: true, force: true });
for (const [from, to] of [
    ['node_modules/dropzone/dist/min/dropzone.min.js', 'assets/libs/dropzone/dropzone.min.js'],
    ['node_modules/flatpickr/dist/flatpickr.min.js', 'assets/libs/flatpickr/flatpickr.min.js'],
    ['node_modules/flatpickr/dist/flatpickr.min.css', 'assets/libs/flatpickr/flatpickr.min.css']
]) {
    cpSync(path(from), path(to));
}
cpSync(path('src/integrations/tinymce_icon.png'), path('assets/js/tinymce_icon.png'));

await buildAdminApp();

console.log(watch ? 'Watching the admin app for changes...' : 'Build complete.');
