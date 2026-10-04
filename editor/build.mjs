// GNU GPL v3 or later. Copyright 2026 Andreas Giesen.
// Build the local AMD engine and retain all third-party licence notices in the distributed plugin.
import {build} from 'esbuild';
import {readFile, writeFile, readdir, mkdir} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
const plugin = new URL('../', import.meta.url);
for (const minify of [false, true]) {
    const result = await build({entryPoints: [fileURLToPath(new URL('entry.js', import.meta.url))],
        bundle: true, write: false, minify, format: 'iife', globalName: 'SideNotesEditorEngine',
        target: 'es2019', legalComments: 'inline'});
    const name = minify ? 'amd/build/editor_engine.min.js' : 'amd/src/editor_engine.js';
    await writeFile(new URL(name, plugin), "define('local_sidenotes/editor_engine',[],function(){"
        + result.outputFiles[0].text + ';return SideNotesEditorEngine;});\n');
}
const lock = JSON.parse(await readFile(new URL('package-lock.json', import.meta.url), 'utf8'));
await mkdir(new URL('thirdparty/', plugin), {recursive: true});
const notices = ['# Bundled editor dependencies', '',
    'Side Notes remains GNU GPL v3 or later. The locally bundled editor uses the following MIT-licensed components.',
    'No CDN, hosted editor or telemetry service is used. Build sources and pinned npm lockfile are in editor/.', ''];
for (const [location, info] of Object.entries(lock.packages)) {
    if (!location || info.dev || info.optional) {continue;}
    const directory = new URL(location + '/', import.meta.url);
    const files = await readdir(directory);
    const licence = files.find(name => /^licen[cs]e(?:\.md|\.txt)?$/i.test(name));
    if (!licence || info.license !== 'MIT') {throw new Error('Missing/unsupported licence: ' + location);}
    const name = location.replace(/^node_modules\//, '').replaceAll('/', '-').replace('@', '');
    await writeFile(new URL('thirdparty/' + name + '.txt', plugin), await readFile(new URL(licence, directory)));
    notices.push('- ' + location.replace(/^node_modules\//, '') + ' ' + info.version + ' — MIT; notice in thirdparty/' + name + '.txt');
}
notices.push('', '## Image viewer', '',
    '- PhotoSwipe 5.4.4 — MIT; notice in thirdparty/photoswipe/LICENSE. Sources and CSS in thirdparty/photoswipe/.');
await writeFile(new URL('THIRD_PARTY_NOTICES.md', plugin), notices.join('\n') + '\n');
