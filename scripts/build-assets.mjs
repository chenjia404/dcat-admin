import { spawnSync } from 'node:child_process';
import { globSync } from 'glob';
import { resolve, basename } from 'node:path';

const root = resolve(import.meta.dirname, '..');

const jsEntries = [
    '__adminlte_js',
    'dcat/js/dcat-app',
    ...globSync('resources/assets/dcat/extra/*.js', { cwd: root }).map((file) => {
        return `dcat/extra/${basename(file, '.js')}`;
    }),
];

const scssEntries = [
    '__adminlte_scss',
    'dcat/css/dcat-app',
    ...globSync('resources/assets/dcat/extra/*.scss', { cwd: root }).map((file) => {
        return `__extra_scss_${basename(file, '.scss')}`;
    }),
];

const entries = [...jsEntries, ...scssEntries];
const mode = process.env.VITE_BUILD_MODE || 'production';

for (let i = 0; i < entries.length; i++) {
    const entry = entries[i];
    const result = spawnSync(
        'pnpm',
        ['exec', 'vite', 'build', '--mode', mode],
        {
            cwd: root,
            stdio: 'inherit',
            env: {
                ...process.env,
                VITE_SINGLE_ENTRY: entry,
                VITE_SKIP_STATIC_COPY: i === 0 ? '0' : '1',
            },
        },
    );

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}
