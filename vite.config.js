import fs from "node:fs";
import path from "node:path";
import { wayfinder } from "@laravel/vite-plugin-wayfinder";
import laravel from "laravel-vite-plugin";
import { icons as lucide } from "lucide";
import { defineConfig } from "vite";

function lucideSubset() {
	return {
		name: "lucide-subset",
		resolveId: (id) => (id === "virtual:icons" ? "\0virtual:icons" : null),
		load(id) {
			if (id !== "\0virtual:icons") return null;
			const walk = (dir) =>
				fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
					const file = path.join(dir, entry.name);
					return entry.isDirectory()
						? walk(file)
						: /\.js$/.test(entry.name)
							? [file]
							: [];
				});
			const pascal = (name) =>
				name
					.split("-")
					.map((part) => part[0].toUpperCase() + part.slice(1))
					.join("");
			const source = walk(path.resolve("src"))
				.map((file) => fs.readFileSync(file, "utf8"))
				.join("\n");
			const names = [
				...new Set(
					[
						...source.matchAll(
							/[\x27\x22\x60]([a-z][a-z0-9]*(?:-[a-z0-9]+)*)[\x27\x22\x60]/g,
						),
					].map((match) => match[1]),
				),
			]
				.filter((name) => lucide[pascal(name)])
				.sort();
			const output = Object.fromEntries(
				names.map((name) => {
					let node = lucide[pascal(name)];
					if (node[0] === "svg") node = node[2];
					return [
						name,
						node
							.map(
								([tag, attributes]) =>
									`<${tag} ${Object.entries(attributes)
										.filter(([key]) => key !== "key")
										.map(([key, value]) => `${key}="${value}"`)
										.join(" ")}/>`,
							)
							.join(""),
					];
				}),
			);
			return `export const ICONS = ${JSON.stringify(output)};`;
		},
	};
}

export default defineConfig({
	plugins: [
		wayfinder({ skipDatabase: true }),
		lucideSubset(),
		laravel({
			input: ["src/main.js"],
			refresh: true,
		}),
	],
	server: {
		watch: {
			ignored: ["**/storage/framework/views/**"],
		},
	},
});
