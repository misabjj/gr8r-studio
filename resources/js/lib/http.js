export async function jsonRequest(url, method, data) {
  if (localStorage.getItem('gr8r.offline') === 'true') {
    throw new Error("Your changes couldn't be saved. You're offline.");
  }

  const response = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
    body: JSON.stringify(data),
  });

  const result = await response.json();
  if (!response.ok) {
    const error = new Error(result.message || 'The request could not be completed.');
    error.errors = result.errors || {};
    throw error;
  }

  return result;
}
