<script lang="ts">
    import { Link, router, usePage } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { home } from '../routes';
    import { logout as logoutRoute } from '../routes';
    import { index as projectsIndex, show as projectShow } from '../routes/projects';
    import {
        Bell,
        CalendarDays,
        ChevronDown,
        CircleHelp,
        Command,
        Inbox,
        LayoutDashboard,
        Plus,
        Search,
        Settings2,
        SquareCheckBig,
        UsersRound,
    } from '@lucide/svelte';
    import type { Project } from '../types';

    type SharedProps = {
        appName: string;
        projects: Project[];
        auth: { user: { name: string } | null };
    };

    const page = usePage<SharedProps>();
    const initials = page.props.auth.user?.name.slice(0, 1) ?? 'T';
    let { children }: { children?: Snippet } = $props();
</script>

<svelte:head>
    <meta name="theme-color" content="#fbfaf8" />
</svelte:head>

<div class="app-shell">
    <aside class="sidebar">
        <Link class="brand" href={home()} aria-label="Gr8r Studio home">
            <span class="brand-mark">g</span>
            <span>gr8r<span class="brand-light">studio</span></span>
            <ChevronDown size={14} class="brand-chevron" />
        </Link>

        <button class="workspace-switcher" type="button">
            <span class="workspace-avatar">G</span>
            <span class="workspace-copy"><strong>Gr8r Studio</strong><small>Free workspace</small></span>
            <ChevronDown size={14} />
        </button>

        <div class="side-tools">
            <button type="button" class="side-tool"><Search size={15} /><span>Search</span><kbd>⌘ K</kbd></button>
            <button type="button" class="side-tool"><Inbox size={15} /><span>Inbox</span><span class="count-badge">4</span></button>
        </div>

        <div class="side-section-label"><span>Workspace</span><button type="button" aria-label="Add workspace item"><Plus size={14} /></button></div>
        <nav class="side-nav" aria-label="Workspace navigation">
            <Link href={home()} class={page.url === '/' ? 'side-link active' : 'side-link'}><LayoutDashboard size={16} /><span>Overview</span></Link>
            <Link href={projectsIndex()} class={page.url.startsWith('/projects') ? 'side-link active' : 'side-link'}><SquareCheckBig size={16} /><span>Projects</span></Link>
            <button type="button" class="side-link"><CalendarDays size={16} /><span>Calendar</span></button>
            <button type="button" class="side-link"><UsersRound size={16} /><span>Members</span></button>
        </nav>

        <div class="side-section-label projects-label"><span>Projects</span><Link href={projectsIndex()} aria-label="Add project"><Plus size={14} /></Link></div>
        <nav class="side-nav project-nav" aria-label="Projects">
            {#each page.props.projects ?? [] as project (project.id)}
                <Link href={projectShow(project.id)} class="side-link">
                    <span class={`project-color color-${project.color}`}></span><span class="project-link-name">{project.name}</span><span class="nav-count">{project.tasks_count ?? ''}</span>
                </Link>
            {/each}
            <Link href={projectsIndex()} class="side-link muted-link"><Plus size={15} /><span>Add project</span></Link>
        </nav>

        <div class="sidebar-bottom">
            <button type="button" class="side-link"><Settings2 size={16} /><span>Settings</span></button>
            <button type="button" class="side-link"><CircleHelp size={16} /><span>Help center</span></button>
            <button type="button" class="profile-row" onclick={() => router.post(logoutRoute().url)}>
                <span class="profile-avatar">{initials}</span>
                <span class="profile-name">{page.props.auth.user?.name ?? 'Tanjim Islam'}</span>
                <Settings2 size={15} />
            </button>
        </div>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div class="breadcrumbs"><span>Workspace</span><span class="breadcrumb-slash">/</span><span class="breadcrumb-current">{page.props.appName}</span></div>
            <div class="topbar-actions">
                <button type="button" class="icon-button" aria-label="Open command menu"><Command size={17} /></button>
                <button type="button" class="icon-button notification-button" aria-label="Notifications"><Bell size={17} /><i></i></button>
                <span class="topbar-divider"></span>
                <button type="button" class="help-link"><CircleHelp size={15} /> Help</button>
            </div>
        </header>
        <section class="page-content">{@render children?.()}</section>
    </main>
</div>
