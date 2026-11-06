import { router } from "@inertiajs/svelte";

export function authenticateWithLaravel({ email, password, remember }) {
	return new Promise((resolve) => {
		router.post(
			"/login",
			{ email, password, remember },
			{
				onSuccess: () => resolve({ authenticated: true, errors: {} }),
				onError: (errors) => resolve({ authenticated: false, errors }),
				onNetworkError: () =>
					resolve({
						authenticated: false,
						errors: {},
						message:
							"We could not reach the server. Check your connection and try again.",
					}),
				onHttpException: () =>
					resolve({
						authenticated: false,
						errors: {},
						message: "We could not sign you in. Try again.",
					}),
			},
		);
	});
}
