<script>
  import { LOGO } from '@/src/core/icons.js';
  import AuthThemeToggle from './AuthThemeToggle.svelte';

  let { children, actionLabel = null, actionHref = null } = $props();
</script>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row">
      {#if actionLabel && actionHref}
        <a class="btn btn-ghost btn-sm" href={actionHref}>{actionLabel}</a>
      {/if}
      <AuthThemeToggle />
    </div>
  </div>
  <div class="auth-c">
    {@render children()}
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
