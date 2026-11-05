<script lang="ts">
    import { Link, useHttp } from '@inertiajs/svelte';
    import { ArrowLeft, CalendarDays, Check, Circle, Plus } from '@lucide/svelte';
    import type { Project, Task } from '../../types';
    import { index as projectsIndex } from '../../routes/projects';
    import { store as createTaskRoute } from '../../routes/projects/tasks';
    import { update as updateTaskRoute } from '../../routes/tasks';

    let { project }: { project: Project & { tasks: Task[] } } = $props();
    let title = $state('');
    let tasks = $state<Task[]>([]);
    const createTaskRequest = useHttp<{ title: string; status: Task['status']; priority: Task['priority'] }, Task>({ title: '', status: 'todo', priority: 'none' });
    const updateTaskRequest = useHttp<{ status: Task['status'] }, Task>({ status: 'todo' });

    $effect(() => {
        tasks = [...project.tasks];
    });

    async function createTask(event: SubmitEvent): Promise<void> {
        event.preventDefault();
        if (!title.trim()) return;
        createTaskRequest.setStore('title', title.trim());
        await createTaskRequest.post(createTaskRoute(project.id).url, {
            onSuccess: (task) => {
                tasks = [task, ...tasks];
                title = '';
            },
        });
    }

    async function toggleTask(task: Task): Promise<void> {
        const status = task.status === 'done' ? 'todo' : 'done';
        updateTaskRequest.setStore('status', status);
        await updateTaskRequest.patch(updateTaskRoute(task.id).url, {
            onSuccess: (updatedTask) => {
                tasks = tasks.map((item) => item.id === updatedTask.id ? updatedTask : item);
            },
        });
    }
</script>

<svelte:head><title>{project.name}</title></svelte:head>

<div class="project-show-page">
    <div class="project-breadcrumb"><Link href={projectsIndex()}><ArrowLeft size={14} /> All projects</Link><span>/</span><span>{project.key}</span></div>
    <div class="page-heading project-show-heading">
        <div class="project-title-wrap"><span class={`project-tile color-${project.color}`}>{project.key.slice(0, 1)}</span><div><div class="eyebrow">PROJECT · {project.key}</div><h1>{project.name}</h1><p>{project.description || 'A focused space for your team’s work.'}</p></div></div>
        <button class="button button-primary" type="button" onclick={() => document.getElementById('new-task-title')?.focus()}><Plus size={16} /> New task</button>
    </div>

    <div class="project-metrics"><div><span class="metric-label">Status</span><strong class="project-state"><i></i>{project.status}</strong></div><div><span class="metric-label">Tasks</span><strong>{tasks.length}</strong></div><div><span class="metric-label">Open</span><strong>{tasks.filter((task) => task.status !== 'done').length}</strong></div><div><span class="metric-label">Completed</span><strong>{tasks.filter((task) => task.status === 'done').length}</strong></div></div>

    <section class="panel project-task-panel">
        <div class="panel-heading"><div><span class="panel-kicker">PROJECT WORK</span><h2>Tasks <span class="heading-count">{project.tasks.length}</span></h2></div><span class="view-mode-label">List view</span></div>
        <form class="quick-task-form" onsubmit={createTask}><Plus size={16} /><input id="new-task-title" bind:value={title} placeholder="Add a task to this project…" aria-label="New task title" /><button type="submit" disabled={!title.trim()}>Add task</button></form>
        {#if tasks.length}
            <div class="task-list project-task-list">
                {#each tasks as task (task.id)}
                    <div class="task-row">
                        <button class="task-check" class:checked={task.status === 'done'} aria-label={`Complete ${task.title}`} onclick={() => toggleTask(task)}>{#if task.status === 'done'}<Check size={13} />{/if}</button>
                        <div class="task-main"><strong class:task-completed={task.status === 'done'}>{task.title}</strong><span><span class="task-key">{project.key}-{task.id}</span></span></div>
                        <span class={`status-pill status-${task.status}`}>{task.status === 'progress' ? 'In progress' : task.status}</span>
                        <span class={`priority-label priority-${task.priority}`}>{task.priority}</span>
                        {#if task.due_date}<span class="task-date"><CalendarDays size={13} />{task.due_date}</span>{:else}<span class="task-date empty"><Circle size={12} />—</span>{/if}
                    </div>
                {/each}
            </div>
        {:else}
            <div class="empty-state"><div class="empty-icon"><Check size={20} /></div><strong>No tasks yet</strong><p>Add the first task to start moving this project forward.</p></div>
        {/if}
    </section>
</div>
