<script lang="ts">
  import { ic } from '../../../src/core/icons.js';

  type Props = {
    icon?: boolean;
    id?: string;
    message: string;
    role?: 'alert' | 'status';
  };

  let { icon = true, id, message, role }: Props = $props();
</script>

<span class="err" {id} {role}>
  {#if icon}{@html ic('circle-alert', 12)}{/if}{message}
</span>
