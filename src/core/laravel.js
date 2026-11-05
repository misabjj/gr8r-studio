export async function authenticateWithLaravel({ email, password, remember }) {
	const response = await fetch("/login", {
		method: "POST",
		headers: {
			Accept: "application/json",
			"Content-Type": "application/json",
			"X-CSRF-TOKEN":
				document.querySelector('meta[name="csrf-token"]')?.content || "",
		},
		body: JSON.stringify({ email, password, remember }),
	});

	if (response.ok && response.redirected) {
		return { authenticated: true, destination: response.url };
	}

	const result = await response.json();
	return {
		authenticated: false,
		errors: result.errors || {},
		message: result.message,
	};
}
