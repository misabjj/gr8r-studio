<script lang="ts">
  import Button from "../../Components/Button.svelte";
  import ErrorMessage from "../../Components/ErrorMessage.svelte";
  import Alert from "../../Components/Alert.svelte";
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { login } from '../../routes';
  import { email as sendResetLink } from '../../routes/password';
  import { ic } from '../../../../src/core/icons.js';
  import AuthLayout from '../../Components/AuthLayout.svelte';

  let { status = null }: { status?: string | null } = $props();
  const form = useForm({ email: '' });

  function submit(event: SubmitEvent): void {
    event.preventDefault();
    form.transform(data => ({ ...data, email: data.email.trim() })).post(sendResetLink().url);
  }
</script>

<svelte:head>
  <title>Forgot password</title>
</svelte:head>

<AuthLayout>
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center"><span class="ftype" style="--c:var(--accent);width:44px;height:44px;border-radius:11px">{@html ic('key-round', 20)}</span></div>
      <h1>Reset your password</h1>
      <p class="sub">Enter your account email and we'll send you a reset link.</p>
      {#if status}<Alert variant="ok" icon="circle-check">{status}</Alert>{/if}
      <div class="field">
        <Label for="f-email">Email</Label>
        <Input size="large" error={!!form.errors.email} id="f-email" type="email" bind:value={form.email} autocomplete="email" required />
        {#if form.errors.email}<ErrorMessage message={form.errors.email} />{/if}
      </div>
      <Button variant="primary" size="lg" block loading={form.processing} disabled={form.processing}>{form.processing ? 'Sending…' : 'Send reset link'}</Button>
      <p class="auth-foot"><a class="link row" style="display:inline-flex;gap:6px" href={login.url()}>{@html ic('arrow-left', 14)}Back to sign in</a></p>
    </form>
</AuthLayout>
