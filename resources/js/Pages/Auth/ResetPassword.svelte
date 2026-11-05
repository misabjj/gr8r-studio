<script>
  import { update as resetPassword } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';

  let { email: initialEmail = '', token } = $props();
  let password = $state('');
  let passwordConfirmation = $state('');
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

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row"><button class="ibtn" type="button" aria-label="Toggle theme">{@html ic('moon', 16)}</button></div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Choose a new password</h1>
      <p class="sub">for {initialEmail}</p>
      <input type="hidden" name="token" value={token} />
      <div class="field">
        <label class="label" for="r-pw">New password</label>
        <input class="input input-lg" class:is-error={!!errors.password} id="r-pw" type="password" bind:value={password} autocomplete="new-password" aria-invalid={!!errors.password} />
        {#if errors.password}<span class="err">{@html ic('circle-alert', 12)}{errors.password[0]}</span>{/if}
        {#if errors.form}<span class="err">{@html ic('circle-alert', 12)}{errors.form}</span>{/if}
      </div>
      <div class="field">
        <label class="label" for="r-pw2">Confirm password</label>
        <input class="input input-lg" class:is-error={!!errors.password_confirmation} id="r-pw2" type="password" bind:value={passwordConfirmation} autocomplete="new-password" aria-invalid={!!errors.password_confirmation} />
        {#if errors.password_confirmation}<span class="err">{@html ic('circle-alert', 12)}{errors.password_confirmation[0]}</span>{/if}
        {#if errors.email}<span class="err">{@html ic('circle-alert', 12)}{errors.email[0]}</span>{/if}
      </div>
      <button class="btn btn-primary btn-lg btn-block" disabled={busy}>Reset password</button>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
