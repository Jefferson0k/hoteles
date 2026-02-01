import "@/assets/styles.scss";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import type { DefineComponent } from "vue";
import { createApp, h } from "vue";
import { ZiggyVue } from "ziggy-js";
import "primeicons/primeicons.css";
import { setupPrimeVue } from "./plugins/primevue";
import { createPinia } from "pinia";

// Extend ImportMeta interface for Vite...
declare module "vite/client" {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }
    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// 👇 CREAR PINIA FUERA DEL SETUP PARA GARANTIZAR UNA ÚNICA INSTANCIA
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });
        
        // 👇 ORDEN CORRECTO
        vueApp.use(pinia);           // 1️⃣ Pinia PRIMERO
        vueApp.use(plugin);          // 2️⃣ Inertia
        vueApp.use(ZiggyVue);        // 3️⃣ Ziggy
        setupPrimeVue(vueApp);       // 4️⃣ PrimeVue
        
        vueApp.mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});