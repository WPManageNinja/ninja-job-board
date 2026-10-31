/**
 * Prepares the scoped CSS inliner copied from FluentCRM (scoped-vendor/). Safe to run more than once.
 * 1. Renames the autoloader marker "Fcrm" to "Njb", so both plugins can load their copy at the same time.
 * 2. Adds the ABSPATH guard to the procedural Composer files (Plugin Check: direct file access).
 * 3. Marks the Symfony exception messages as not output (Plugin Check: ExceptionNotEscaped); the inliner never prints them.
 */
import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const vendor = join(root, 'includes/Classes/Emogrifier/scoped-vendor');

const GUARD = "if (!defined('ABSPATH')) {\n    exit;\n}\n";
const EXCEPTIONS = '// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- developer exception messages, never printed';

const phpFiles = (dir) => readdirSync(dir).flatMap((name) => {
    const file = join(dir, name);
    if (statSync(file).isDirectory()) {
        return phpFiles(file);
    }
    return name.endsWith('.php') ? [file] : [];
});

let changed = 0;
for (const file of phpFiles(vendor)) {
    const original = readFileSync(file, 'utf8');
    let code = original.replace(/Fcrm([0-9a-f]{32})/g, 'Njb$1');

    const declaresCode = /^\s*(namespace|(abstract |final )?class|interface|trait)\s/m.test(code);
    if (!declaresCode && !code.includes("defined('ABSPATH')")) {
        code = code.replace(/^<\?php\s*/, '<?php\n' + GUARD + '\n');
    }

    if (/\bthrow\b/.test(code) && !code.includes(EXCEPTIONS)) {
        code = code.replace(/^<\?php\n/, '<?php\n' + EXCEPTIONS + '\n');
    }

    if (code !== original) {
        writeFileSync(file, code);
        changed++;
        console.log('prepared ' + relative(root, file));
    }
}

console.log(changed ? `Prepared ${changed} files.` : 'Nothing to change.');
