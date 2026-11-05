<script>
  import { onMount } from 'svelte';
  import { ic } from '@/src/core/icons.js';
  import { applyTheme, effectiveDark, savedTheme, toggleTheme } from '../lib/theme.js';

  let dark = $state(false);

  onMount(() => {
    const theme = savedTheme();
    applyTheme(theme);
    dark = effectiveDark(theme);
  });

  function handleToggle() {
    dark = toggleTheme() === 'dark';
  }
</script>

<button class="ibtn" type="button" onclick={handleToggle} aria-label="Toggle theme" title="Toggle theme">
  {@html ic(dark ? 'sun' : 'moon', 16)}
</button>
