<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import { ArrowRight, Plus } from '@lucide/svelte';
    import type { Project } from '../../types';
    import { show as projectShow, store as projectStore } from '../../routes/projects';

    let { projects }: { projects: Project[] } = $props();
    let showForm = $state(false);
    let name = $state('');
    let key = $state('');
    let description = $state('');
    let errors = $state<Record<string, string>>({});

    function submitProject(event: SubmitEvent): void {
        event.preventDefault();
        errors = {};
        router.post(projectStore().url, { name, key: key.toUpperCase(), description }, {
            onError: (validationErrors) => { errors = validationErrors as Record<string, string>; },
            onSuccess: () => { name = ''; key = ''; description = ''; showForm = false; },
        });
    }
</script>

<svelte:head><title>Projects</title></svelte:head>

<div class="projects-page">
    <div class="page-heading">
        <div><div class="eyebrow">YOUR WORKSPACE</div><h1>Projects</h1><p>Bring your team’s work together and keep every detail moving.</p></div>
        <button class="button button-primary" type="button" onclick={() => (showForm = !showForm)}><Plus size={16} /> New project</button>
    </div>

    {#if showForm}
        <form class="new-project-form" onsubmit={submitProject}>
            <div class="form-header"><div><strong>Create a project</strong><p>Start with a name and a short project key.</p></div><button class="text-button" type="button" onclick={() => (showForm = false)}>Cancel</button></div>
            <div class="form-grid">
                <label>Project name<input bind:value={name} placeholder="e.g. Website redesign" required maxlength="120" />{#if errors.name}<small class="field-error">{errors.name}</small>{/if}</label>
                <label>Project key<input bind:value={key} placeholder="e.g. WEB" required maxlength="12" oninput={() => (key = key.toUpperCase())} />{#if errors.key}<small class="field-error">{errors.key}</small>{/if}</label>
                <label class="form-wide">Description<textarea bind:value={description} placeholder="What is this project about?" rows="2"></textarea></label>
            </div>
            <div class="form-footer"><button class="button button-primary" type="submit">Create project</button></div>
        </form>
    {/if}

    <div class="projects-toolbar"><div class="toolbar-tabs"><button class="toolbar-tab active" type="button">All projects <span>{projects.length}</span></button><button class="toolbar-tab" type="button">Favorites</button><button class="toolbar-tab" type="button">Archived</button></div><span class="toolbar-note">Sorted by name</span></div>

    {#if projects.length}
        <div class="projects-grid">
            {#each projects as project (project.id)}
                <Link href={projectShow(project.id)} class="project-overview-card">
                    <div class="project-overview-top"><span class={`project-tile color-${project.color}`}>{project.key.slice(0, 1)}</span><span class="project-state"><i></i>{project.status}</span></div>
                    <h2>{project.name}</h2><p>{project.description || 'A place to organize the work and keep your team in sync.'}</p>
                    <div class="project-overview-footer"><span class="project-key">{project.key}</span><span>{project.tasks_count ?? 0} tasks <ArrowRight size={14} /></span></div>
                </Link>
            {/each}
        </div>
    {:else}
        <div class="empty-projects"><div class="empty-icon"><Plus size={20} /></div><h2>Your first project starts here</h2><p>Bring tasks, plans, and people together in one focused space.</p><button class="button button-primary" type="button" onclick={() => (showForm = true)}><Plus size={16} /> Create project</button></div>
    {/if}
</div>
