<script>
  import { login } from '../../routes';
  import { email as sendResetLink } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';
  import AuthThemeToggle from '../../Components/AuthThemeToggle.svelte';

  let email = $state('');
  let busy = $state(false);
  let message = $state('');
  let errors = $state({});

  async function submit(event) {
    event.preventDefault();
    busy = true;
    message = '';
    errors = {};

    try {
      const response = await fetch(sendResetLink().url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ email: email.trim() }),
      });
      const result = await response.json();

      if (response.ok) {
        message = result.message;
      } else {
        errors = result.errors || { form: result.message || 'We could not send reset instructions. Try again.' };
      }
    } catch {
      errors = { form: 'We could not reach the server. Check your connection and try again.' };
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head>
  <title>Forgot password · Gr8r Studio</title>
</svelte:head>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row"><AuthThemeToggle /></div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center"><span class="ftype" style="--c:var(--accent);width:44px;height:44px;border-radius:11px">{@html ic('key-round', 20)}</span></div>
      <h1>Reset your password</h1>
      <p class="sub">Enter your account email and we'll send you a reset link.</p>
      {#if message}<div class="alert ok" role="status">{@html ic('circle-check', 15)}<span>{message}</span></div>{/if}
      {#if errors.form}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.form}</span></div>{/if}
      <div class="field">
        <label class="label" for="f-email">Email</label>
        <input class="input input-lg" class:is-error={!!errors.email} id="f-email" type="email" bind:value={email} autocomplete="email" required aria-invalid={!!errors.email} />
        {#if errors.email}<span class="err">{@html ic('circle-alert', 12)}{errors.email[0]}</span>{/if}
      </div>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={busy} disabled={busy}>{busy ? 'Sending…' : 'Send reset link'}</button>
      <p class="auth-foot"><a class="link row" style="display:inline-flex;gap:6px" href={login.url()}>{@html ic('arrow-left', 14)}Back to sign in</a></p>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
