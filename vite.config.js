import {defineConfig} from "vite";
import {glob} from "glob";
import {resolve} from "path";

const inputs = glob.sync('./frontend/*.js').reduce((acc, path) => {
    // vue/pages/Admin/Index becomes AdminIndex
    const entry = path.replace(/\.js$/g, '')
        .replace(/^frontend\//g, '')
        .replace(/\//g, '');

    acc[entry] = resolve(__dirname, path)
    return acc
}, {});

console.log(inputs);

// https://vitejs.dev/config/
export default defineConfig({
    base: "/static/dist",
    build: {
        rollupOptions: {
            input: inputs
        },
        manifest: true,
        emptyOutDir: true,
        chunkSizeWarningLimit: "1m",
        outDir: resolve(__dirname, "./web/static/dist"),
    },
    server: {
        strictPort: true,
        host: true,
        fs: {
            allow: ["."],
        },
    },
    resolve: {
        alias: {
            "!": resolve(__dirname),
            "~": resolve(__dirname, "./frontend"),
        },
        extensions: [".mjs", ".js", ".mts", ".ts", ".jsx", ".tsx", ".json", ".vue"],
    },
});
