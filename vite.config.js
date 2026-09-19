import { defineConfig } from 'vite';
import { resolve, basename } from 'path';
import { globSync } from 'glob';
import { viteStaticCopy } from 'vite-plugin-static-copy';

const root = resolve(import.meta.dirname);

/** @type {string|null} 由 admin:minify 命令在编译主题色时临时替换 */
let theme = null;

function themeCss(relativeBase) {
    const suffix = theme ? `-${theme}` : '';

    return `${relativeBase}${suffix}.css`;
}

const entryOutputMap = {
    __adminlte_js: 'adminlte/adminlte.js',
    __adminlte_scss: themeCss('adminlte/adminlte'),
    'dcat/js/dcat-app': 'dcat/js/dcat-app.js',
    'dcat/css/dcat-app': themeCss('dcat/css/dcat-app'),
};

function buildInputs() {
    const input = {
        __adminlte_js: resolve(root, 'resources/assets/adminlte/js/AdminLTE.js'),
        __adminlte_scss: resolve(root, 'resources/assets/adminlte/scss/AdminLTE.scss'),
        'dcat/js/dcat-app': resolve(root, 'resources/assets/dcat/js/dcat-app.js'),
        'dcat/css/dcat-app': resolve(root, 'resources/assets/dcat/sass/dcat-app.scss'),
    };

    globSync('resources/assets/dcat/extra/*.js', { cwd: root }).forEach((file) => {
        const name = basename(file, '.js');
        const key = `dcat/extra/${name}`;
        input[key] = resolve(root, file);
        entryOutputMap[key] = `${key}.js`;
    });

    globSync('resources/assets/dcat/extra/*.scss', { cwd: root }).forEach((file) => {
        const name = basename(file, '.scss');
        const key = `__extra_scss_${name}`;
        input[key] = resolve(root, file);
        entryOutputMap[key] = `dcat/extra/${name}.css`;
    });

    return input;
}

function resolveCssAssetPath(assetInfo) {
    const originals = assetInfo.originalFileNames ?? [];
    const from = assetInfo.names?.[0] ?? '';
    const pathHint = [...originals, from].join(' ');

    for (const [key, outPath] of Object.entries(entryOutputMap)) {
        if (key.startsWith('__extra_scss_')) {
            const name = key.replace('__extra_scss_', '');
            if (pathHint.includes(`extra/${name}.scss`) || pathHint.includes(`${name}.scss`)) {
                return outPath;
            }
        }
    }

    if (pathHint.includes('AdminLTE.scss')) {
        return entryOutputMap.__adminlte_scss;
    }
    if (pathHint.includes('dcat-app.scss')) {
        return entryOutputMap['dcat/css/dcat-app'];
    }

    return null;
}

export default defineConfig(({ mode }) => {
    const outDir = resolve(root, mode === 'production' ? 'resources/dist' : 'resources/pre-dist');
    const allInputs = buildInputs();
    const singleEntry = process.env.VITE_SINGLE_ENTRY;
    const input = singleEntry
        ? { [singleEntry]: allInputs[singleEntry] }
        : allInputs;
    const emptyOutDir = false;

    return {
        define: {
            jQuery: 'window.jQuery',
            $: 'window.jQuery',
        },
        plugins: [
            ...(process.env.VITE_SKIP_STATIC_COPY === '1'
                ? []
                : [
                      viteStaticCopy({
                          targets: [
                              { src: 'resources/assets/images', dest: '.' },
                              { src: 'resources/assets/fonts', dest: '.' },
                              { src: 'resources/assets/dcat/plugins', dest: 'dcat' },
                              { src: 'resources/assets/dcat/sass/nunito.css', dest: 'dcat/css' },
                          ],
                      }),
                  ]),
        ],
        build: {
            outDir,
            emptyOutDir,
            sourcemap: mode !== 'production',
            rollupOptions: {
                input,
                output: {
                    format: 'iife',
                    inlineDynamicImports: Boolean(singleEntry),
                    entryFileNames: (chunk) => {
                        const mapped = entryOutputMap[chunk.name];

                        if (mapped && mapped.endsWith('.js')) {
                            return mapped;
                        }

                        if (mapped && mapped.endsWith('.css')) {
                            return '_virtual/[name].js';
                        }

                        return '[name].js';
                    },
                    chunkFileNames: 'chunks/[name].js',
                    assetFileNames: (assetInfo) => {
                        const mapped = resolveCssAssetPath(assetInfo);

                        if (mapped) {
                            return mapped;
                        }

                        return 'assets/[name][extname]';
                    },
                },
            },
        },
        css: {
            preprocessorOptions: {
                scss: {
                    silenceDeprecations: ['legacy-js-api'],
                },
            },
        },
    };
});
