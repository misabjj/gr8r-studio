import "../../src/styles/index.css";
import { createInertiaApp } from "@inertiajs/svelte";
import { mount } from "svelte";
import { enhanceSelects } from "../../src/ui/select.js";
import { applyTheme, savedTheme } from "./lib/theme.js";

applyTheme(savedTheme());

const pages = import.meta.glob("./Pages/**/*.svelte", { eager: true });

createInertiaApp({
	resolve: (name) => pages[`./Pages/${name}.svelte`],
	setup({ el, App, props }) {
		mount(App, { target: el, props });
		enhanceSelects(el);
		new MutationObserver(() => enhanceSelects(el)).observe(el, {
			childList: true,
			subtree: true,
		});
	},
});
