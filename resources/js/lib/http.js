import { useHttp } from '@inertiajs/svelte';

export function useHttpRequest() {
  const http = useHttp({});
  let requestData = {};
  http.transform(() => requestData);

  return async function request(url, method, data = {}) {
    if (localStorage.getItem('gr8r.offline') === 'true') {
      throw new Error("Your changes couldn't be saved. You're offline.");
    }

    requestData = data;

    const response = await http.submit(method.toLowerCase(), url);

    if (http.hasErrors) {
      const errorMessage = Object.values(http.errors).flat()[0] || 'The request could not be completed.';
      throw new Error(errorMessage);
    }

    return response;
  };
}
