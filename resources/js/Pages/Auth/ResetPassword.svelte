<script>
  import { login } from '../../routes';
  import { register } from '../../routes';
  import { update as resetPassword } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';

  let { email: initialEmail = '', token } = $props();
  let password = $state('');
  let passwordConfirmation = $state('');
  let showPassword = $state(false);
  let busy = $state(false);
  let errors = $state({});

  async function submit(event) {
    event.preventDefault();
    busy = true;
    errors = {};

    try {
      const response = await fetch(resetPassword().url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
          token,
          email: initialEmail.trim(),
          password,
          password_confirmation: passwordConfirmation,
        }),
      });

      if (response.ok && response.redirected) {
        window.location.assign(response.url);
        return;
      }

      const result = await response.json();
      errors = result.errors || { form: result.message || 'We could not reset your password. Request a new link and try again.' };
    } catch {
      errors = { form: 'We could not reach the server. Check your connection and try again.' };
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head>
  <title>Reset password · Gr8r Studio</title>
</svelte:head>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row"><a class="link" href={register.url()}>Create account</a><a class="link" href={login.url()}>Sign in</a><button class="ibtn" type="button" aria-label="Toggle theme">{@html ic('moon', 16)}</button></div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Reset your password</h1>
      <p class="sub">Choose a new password for your account.</p>
      {#if errors.form}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.form}</span></div>{/if}
      {#if errors.email}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.email[0]}</span></div>{/if}
      <input type="hidden" name="token" value={token} />
      <div class="field">
        <label class="label" for="rp-email">Email</label>
        <input class="input input-lg" id="rp-email" type="email" value={initialEmail} readonly autocomplete="email" />
      </div>
      <div class="field">
        <label class="label" for="rp-password">New password</label>
        <div class="inwrap">
          <input class="input input-lg" class:is-error={!!errors.password} id="rp-password" type={showPassword ? 'text' : 'password'} bind:value={password} autocomplete="new-password" minlength="8" required style="padding-right:38px" aria-invalid={!!errors.password} />
          <button type="button" class="ibtn ibtn-sm" style="position:absolute;right:5px" onclick={() => showPassword = !showPassword} aria-label={showPassword ? 'Hide password' : 'Show password'}>{@html ic(showPassword ? 'eye-off' : 'eye', 15)}</button>
        </div>
        {#if errors.password}<span class="err">{@html ic('circle-alert', 12)}{errors.password[0]}</span>{/if}
      </div>
      <div class="field">
        <label class="label" for="rp-password-confirmation">Confirm new password</label>
        <input class="input input-lg" id="rp-password-confirmation" type={showPassword ? 'text' : 'password'} bind:value={passwordConfirmation} autocomplete="new-password" required />
      </div>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={busy} disabled={busy}>{busy ? 'Updating password…' : 'Reset password'}</button>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
