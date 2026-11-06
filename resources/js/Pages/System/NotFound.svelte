<script>
  import Button from "../../Components/Button.svelte";
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { home } from '../../routes';
  import { ic } from '@/src/core/icons.js';
</script>

<svelte:head><title>Page not found · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="fullstate"><div class="box"><div class="empty-state" style="padding:0"><div class="glyph">{@html ic('file-question', 20)}</div></div><span class="code">ERROR 404</span><h1>Page not found</h1><p>The page you're looking for was moved, deleted, or never existed. Check the link or head back home.</p><div class="row"><Button variant="secondary" size="md" onclick={() => history.back()}>{@html ic('arrow-left', 14)}Go back</Button><Link class="btn btn-primary" href={home().url}>Go to Home</Link></div></div></div>
</AppLayout>
