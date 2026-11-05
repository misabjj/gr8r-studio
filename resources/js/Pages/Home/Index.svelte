<script lang="ts">
    import { Link, useHttp } from '@inertiajs/svelte';
    import { ArrowRight, CalendarDays, Check, Circle, Clock3, Plus, Sparkles } from '@lucide/svelte';
    import type { Project, Task, WorkspaceStats } from '../../types';
    import { index as projectsIndex, show as projectShow } from '../../routes/projects';
    import { update as updateTaskRoute } from '../../routes/tasks';

    type Props = { projects: Project[]; tasks: Task[]; stats: WorkspaceStats };
    let { projects, tasks, stats }: Props = $props();
    let localTasks = $state<Task[]>([]);
    let localStats = $state<WorkspaceStats>({ openTasks: 0, completedTasks: 0, projects: 0 });
    const taskUpdate = useHttp<{ status: Task['status'] }, Task>({ status: 'todo' });

    $effect(() => {
        localTasks = [...tasks];
        localStats = { ...stats };
    });

    const completeTask = async (task: Task): Promise<void> => {
        const status = task.status === 'done' ? 'todo' : 'done';
        taskUpdate.setStore('status', status);
        await taskUpdate.patch(updateTaskRoute(task.id).url, {
            onSuccess: (updatedTask) => {
                const previousTask = localTasks.find((item) => item.id === updatedTask.id);
                localTasks = localTasks.map((item) => item.id === updatedTask.id ? updatedTask : item);
                if (previousTask && previousTask.status !== updatedTask.status) {
                    const completed = updatedTask.status === 'done';
                    localStats = {
                        ...localStats,
                        openTasks: localStats.openTasks + (completed ? -1 : 1),
                        completedTasks: localStats.completedTasks + (completed ? 1 : -1),
                    };
                }
            },
        });
    };
</script>

<svelte:head><title>Overview</title></svelte:head>

<div class="dashboard-page">
    <div class="page-heading">
        <div><div class="eyebrow"><Sparkles size={14} /> {new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }).toUpperCase()}</div><h1>Good afternoon, Tanjim <span>✦</span></h1><p>Here’s what’s happening across your workspace today.</p></div>
        <Link href={projectsIndex()} class="button button-primary"><Plus size={16} /> New project</Link>
    </div>

    <div class="dashboard-grid">
        <section class="welcome-card">
            <div class="welcome-copy"><div class="welcome-label">YOUR WORKSPACE</div><h2>Make room for<br />great work.</h2><p>Keep your team aligned and your next big idea moving.</p><Link href="/projects" class="text-link">Explore projects <ArrowRight size={15} /></Link></div>
            <div class="orbit-art" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="orbit-core">g</div><span class="orbit-dot dot-one"></span><span class="orbit-dot dot-two"></span><span class="orbit-dot dot-three"></span></div>
        </section>

        <section class="stat-card"><div class="stat-card-top"><span class="stat-icon stat-icon-blue"><Circle size={17} /></span><span class="stat-caption">Across all projects</span></div><strong class="stat-number">{localStats.openTasks}</strong><span class="stat-label">Open tasks</span><div class="stat-foot"><span class="tiny-trend">↗</span> Keep the momentum going</div></section>
        <section class="stat-card"><div class="stat-card-top"><span class="stat-icon stat-icon-green"><Check size={17} /></span><span class="stat-caption">Nice work</span></div><strong class="stat-number">{localStats.completedTasks}</strong><span class="stat-label">Completed</span><div class="stat-foot"><span class="tiny-trend">✦</span> A little progress adds up</div></section>

        <section class="panel task-panel">
            <div class="panel-heading"><div><span class="panel-kicker">YOUR FOCUS</span><h2>Up next</h2></div><Link class="panel-action" href={projectsIndex()}>View all <ArrowRight size={14} /></Link></div>
            {#if localTasks.length}
                <div class="task-list">
                    {#each localTasks.slice(0, 6) as task (task.id)}
                        <div class="task-row">
                            <button class="task-check" class:checked={task.status === 'done'} aria-label={`Complete ${task.title}`} onclick={() => completeTask(task)}>{#if task.status === 'done'}<Check size={13} />{/if}</button>
                            <div class="task-main"><strong>{task.title}</strong><span><i class={`project-color color-${task.project?.color ?? 'indigo'}`}></i>{task.project?.name ?? 'Workspace'} <span class="task-key">{task.project?.key}</span></span></div>
                            <span class={`status-pill status-${task.status}`}>{task.status === 'progress' ? 'In progress' : task.status}</span>
                            {#if task.due_date}<span class="task-date"><CalendarDays size={13} />{task.due_date}</span>{:else}<span class="task-date empty"><Clock3 size={13} />No date</span>{/if}
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="empty-state"><div class="empty-icon"><Check size={20} /></div><strong>You’re all caught up</strong><p>New tasks will show here when they’re ready.</p><Link class="text-link" href={projectsIndex()}>Go to projects <ArrowRight size={14} /></Link></div>
            {/if}
        </section>

        <section class="panel projects-panel">
            <div class="panel-heading"><div><span class="panel-kicker">WHAT YOU’RE BUILDING</span><h2>Projects <span class="heading-count">{projects.length}</span></h2></div><Link class="panel-action" href={projectsIndex()}>All projects <ArrowRight size={14} /></Link></div>
            <div class="project-cards">
                {#each projects.slice(0, 4) as project (project.id)}
                    <Link href={projectShow(project.id)} class="project-card">
                        <span class={`project-tile color-${project.color}`}>{project.key.slice(0, 1)}</span>
                        <span class="project-card-copy"><strong>{project.name}</strong><small>{project.tasks_count ?? 0} tasks · {project.status}</small></span>
                        <ArrowRight size={15} class="project-arrow" />
                    </Link>
                {/each}
            </div>
            {#if projects.length === 0}<div class="project-empty">Your projects will appear here.</div>{/if}
            <Link href={projectsIndex()} class="create-project-link"><Plus size={15} /> Create a project</Link>
        </section>
    </div>
</div>
