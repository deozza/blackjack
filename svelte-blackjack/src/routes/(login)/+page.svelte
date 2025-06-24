<script lang="ts">
  import { goto } from "$app/navigation";
  import { onMount } from "svelte";

  onMount(() => {
    goto('/login');
  });
</script>

<!-- Cette page redirige automatiquement vers /login -->
