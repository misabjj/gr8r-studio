import "../../src/styles/index.css";
import { createInertiaApp } from "@inertiajs/svelte";
import { mount } from "svelte";

const pages = import.meta.glob("./Pages/**/*.svelte", { eager: true });

createInertiaApp({
	resolve: (name) => pages[`./Pages/${name}.svelte`],
	setup({ el, App, props }) {
		mount(App, { target: el, props });
	},
});
