/**
 * Compile .vue files with the Vue 3 compiler and report errors and warnings.
 * Usage: node build/check-sfc.mjs <file.vue> [...more files]
 * Exit code 1 when any file has an error.
 */
import { parse, compileTemplate, compileScript } from 'vue/compiler-sfc';
import { readFileSync } from 'node:fs';

let failed = false;
for (const file of process.argv.slice(2)) {
    const source = readFileSync(file, 'utf8');
    const { descriptor, errors } = parse(source, { filename: file });
    const problems = errors.map((e) => `parse: ${e.message}`);

    if (descriptor.template) {
        const result = compileTemplate({ source: descriptor.template.content, filename: file, id: 'x' });
        result.errors.forEach((e) => problems.push(`template: ${typeof e === 'string' ? e : e.message}`));
        result.tips.forEach((t) => problems.push(`tip: ${t}`));
    }
    if (descriptor.script || descriptor.scriptSetup) {
        try {
            compileScript(descriptor, { id: 'x' });
        } catch (e) {
            problems.push(`script: ${e.message}`);
        }
    }

    // Vue 2 leftovers that compile without an error but break at runtime
    const legacy = [
        [/slot-scope=/, 'slot-scope (use #default="scope")'],
        [/\sslot="/, 'slot="..." (use <template #name>)'],
        [/\.sync\b/, '.sync (use v-model:prop)'],
        [/\.native\b/, '.native modifier'],
        [/\{\{[^}]*\|\s*[a-zA-Z]+\s*\}\}/, 'filter in {{ }}'],
        [/el-icon-/, 'el-icon-* class (use @element-plus/icons-vue)'],
        [/size="mini"/, 'size="mini" (use small)'],
        [/:visible\.sync|:visible="/, 'dialog :visible (use v-model)'],
        [/\$(adminGet|adminPost|getJSON)\(|this\.\$get\(|this\.\$post\(/, 'old AJAX helper (use this.$api)'],
        [/\.fail\(|\.always\(/, 'jQuery promise .fail/.always'],
        [/WPJobBoardBus/, 'WPJobBoardBus (use this.$setTitle)'],
        [/beforeDestroy|destroyed\(\)/, 'beforeDestroy/destroyed (use beforeUnmount/unmounted)'],
        [/this\.\$set\(|this\.\$delete\(/, '$set/$delete'],
        [/responseJSON/, 'responseJSON (errors are { status, message, data })'],
        [/type="text\/babel"/, 'script type="text/babel"']
    ];
    legacy.forEach(([re, label]) => { if (re.test(source)) problems.push(`legacy: ${label}`); });

    const hasError = problems.some((p) => !p.startsWith('tip:'));
    failed = failed || hasError;
    console.log(`${hasError ? 'FAIL' : 'OK  '} ${file}`);
    problems.forEach((p) => console.log(`     - ${p}`));
}
process.exit(failed ? 1 : 0);
