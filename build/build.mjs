/**
 * Builds every asset of the plugin:
 *
 * 1. The admin app (Vue 3 + Element Plus) as one ES module: assets/admin/app.js + app.css
 * 2. Classic scripts (IIFE) that WordPress enqueues normally: the admin boot script,
 *    the public form scripts and the TinyMCE button
 * 3. Stand-alone stylesheets for the public pages and the post edit screen
 *
 * Usage: npm run build | npm run dev (rebuilds the admin app on change)
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

async function buildClassicScript(entry, outFile, name) {
    await build({
        root,
        configFile: false,
        logLevel: 'warn',
        // Library mode keeps process.env as is; @wordpress/hooks reads it
        define: {
            'process.env.NODE_ENV': JSON.stringify('production')
        },
        build: {
            outDir: path(dirname(outFile)),
            emptyOutDir: false,
            sourcemap: false,
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

buildStylesheet('src/scss/admin/app_edit.scss', 'assets/css/jobboard-edit.css');
buildStylesheet('src/scss/admin/jobboard-print.scss', 'assets/css/jobboard-print.css');
buildStylesheet('src/scss/public/public.scss', 'assets/css/wp_job_board-public.css');
buildStylesheet('src/scss/public/joblist.scss', 'assets/css/joblist.css');

cpSync(path('src/images'), path('assets/images'), { recursive: true });
cpSync(path('src/libs'), path('assets/libs'), { recursive: true });
cpSync(path('src/integrations/tinymce_icon.png'), path('assets/js/tinymce_icon.png'));

await buildAdminApp();

console.log(watch ? 'Watching the admin app for changes...' : 'Build complete.');
