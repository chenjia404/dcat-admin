import './NProgress.js';

const NProgress =
    typeof globalThis !== 'undefined' && globalThis.NProgress
        ? globalThis.NProgress
        : undefined;

export default NProgress;
