/**
 * SweetAlert2 为 UMD/eval 包，打包后需从 window 读取实例。
 */
import './sweetalert2.all.global.js';

const Swal =
    typeof globalThis !== 'undefined' && globalThis.Swal
        ? globalThis.Swal
        : typeof window !== 'undefined'
          ? window.Swal
          : undefined;

export default Swal;
