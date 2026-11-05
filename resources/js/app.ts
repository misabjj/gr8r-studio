import { createInertiaApp, type ResolvedComponent } from "@inertiajs/svelte";
import { mount } from "svelte";
import WorkspaceLayout from "./Layouts/WorkspaceLayout.svelte";

const pages = import.meta.glob<ResolvedComponent>("./Pages/**/*.svelte", {
	eager: true,
});

void createInertiaApp({
	title: (title) => (title ? `${title} · Gr8r Studio` : "Gr8r Studio"),
	resolve: (name) => {
		const page = pages[`./Pages/${name}.svelte`];

		if (!page) {
			throw new Error(`Unknown Inertia page: ${name}`);
		}

		return {
			...page,
			layout:
				name === "Auth/Login" ? undefined : (page.layout ?? WorkspaceLayout),
		};
	},
	setup({ el, App, props }) {
		if (!el) {
			throw new Error("Inertia did not provide an application root element.");
		}

		mount(App, { target: el, props });
	},
});
