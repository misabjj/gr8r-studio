<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { store as loginRoute } from '../../routes/login';

    const form = useForm({ email: '', password: '', remember: false });

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post(loginRoute().url);
    }
</script>

<svelte:head><title>Sign in · Gr8r Studio</title></svelte:head>

<main class="login-page">
    <section class="login-card">
        <a class="brand login-brand" href="/"><span class="brand-mark">g</span><span>gr8r<span class="brand-light">studio</span></span></a>
        <div class="login-heading"><span class="panel-kicker">WELCOME BACK</span><h1>Sign in to your workspace</h1><p>Pick up where your team left off.</p></div>
        <form class="login-form" onsubmit={submit}>
            <label>Email address<input type="email" bind:value={form.email} autocomplete="username" required /></label>
            {#if form.errors.email}<p class="login-error">{form.errors.email}</p>{/if}
            <label>Password<input type="password" bind:value={form.password} autocomplete="current-password" required /></label>
            <label class="remember-label"><input type="checkbox" bind:checked={form.remember} /> Remember me</label>
            <button class="button button-primary" type="submit" disabled={form.processing}>{form.processing ? 'Signing in…' : 'Sign in'}</button>
        </form>
    </section>
</main>
