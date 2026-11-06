import "../../src/styles/index.css";
import { createInertiaApp } from "@inertiajs/svelte";
import { mount } from "svelte";
import { enhanceSelects } from "../../src/ui/select.js";
import { applyTheme, savedTheme } from "./lib/theme.js";

applyTheme(savedTheme());

const pages = import.meta.glob("./Pages/**/*.svelte", { eager: true });

const titleSuffix = " · Gr8r Studio";

function applyTitleSuffix(): void {
	const title = document.title.trim();

	if (!title || title === "Gr8r Studio" || title.endsWith(titleSuffix)) {
		return;
	}

	document.title = `${title}${titleSuffix}`;
}

createInertiaApp({
	resolve: (name) => pages[`./Pages/${name}.svelte`],
	setup({ el, App, props }) {
		new MutationObserver(applyTitleSuffix).observe(document.head, {
			childList: true,
			characterData: true,
			subtree: true,
		});
		mount(App, { target: el, props });
		applyTitleSuffix();
		enhanceSelects(el);
		new MutationObserver(() => enhanceSelects(el)).observe(el, {
			childList: true,
			subtree: true,
		});
	},
});
