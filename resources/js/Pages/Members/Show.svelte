<script>
import Dropdown from "../../Components/Dropdown.svelte";
import Button from "../../Components/Button.svelte";
import { onMount } from "svelte";
import { Link, router, usePage } from "@inertiajs/svelte";
import AppLayout from "../../Layouts/AppLayout.svelte";
import TaskDrawer from "../../Components/TaskDrawer.svelte";
import {
	index as membersIndex,
	show as memberShow,
} from "../../routes/members";
import { show as projectShow } from "../../routes/projects";
import { index as tasksIndex, update as taskUpdate } from "../../routes/tasks";
import { useHttpRequest } from "../../lib/http.js";
import { setNavigationIntent } from "../../lib/navigation-intents.js";
import { ago, minsAgo } from "@/src/core/utils.js";
import { ic } from "@/src/core/icons.js";
import { dueHtml, prIcon, stIcon } from "@/src/ui/helpers.js";
import { PCOLORS } from "@/src/core/constants.js";
import { D, isOver, save } from "@/src/core/store.js";

const httpRequest = useHttpRequest();
let { memberId, teams = [], workspaceData = {} } = $props();
const page = usePage();
let tab = $state("assigned");
let menuOpen = $state(false);
let roleMenuOpen = $state(false);
let removeDialogOpen = $state(false);
let drawerTaskId = $state(null);
let menuPosition = $state({ left: 8, top: 8 });
let roleMenuPosition = $state({ left: 8, top: 8 });
let roleOverride = $state(
	D().members.find((item) => item.id === memberId)?.role || null,
);
let members = $derived(workspaceData.members || []);
let member = $derived(members.find((item) => item.id === memberId));
let projects = $derived(workspaceData.projects || []);
let workspaceTasks = $state([]);
$effect(() => {
	workspaceTasks = [...(workspaceData.tasks || [])];
});
let tasks = $derived(
	workspaceTasks.filter((task) => task.assignee === memberId && !task.archived),
);
let openTasks = $derived(tasks.filter((task) => task.status !== "done"));
let completedTasks = $derived(tasks.filter((task) => task.status === "done"));
let visibleProjects = $derived(
	projects.filter(
		(project) =>
			!project.archived &&
			project.status !== "complete" &&
			project.members?.includes(memberId) &&
			(!project.private || project.members.includes(workspaceData.me)),
	),
);
let taskById = $derived(
	new Map((workspaceData.tasks || []).map((task) => [task.id, task])),
);
let projectById = $derived(
	new Map(projects.map((project) => [project.id, project])),
);
let activity = $derived(
	(workspaceData.activity || [])
		.filter((item) => item.by === memberId)
		.slice(0, 8),
);
let displayedRole = $derived(roleOverride || member?.role);
let canManageMember = $derived(
	["Owner", "Admin"].includes(page.props.auth?.user?.role) &&
		member?.role !== "Owner" &&
		member?.id !== workspaceData.me,
);
let sortedOpenTasks = $derived(
	[...openTasks].sort((first, second) =>
		(first.due || "9999").localeCompare(second.due || "9999"),
	),
);
let displayedTasks = $derived(
	tab === "assigned" ? sortedOpenTasks : completedTasks,
);
let drawerTask = $derived.by(() => {
	const sourceTask = workspaceTasks.find(
		(task) => String(task.database_id) === String(drawerTaskId),
	);
	if (!sourceTask) return null;

	const project = projects.find((item) => item.id === sourceTask.project);
	const assignee = members.find((item) => item.id === sourceTask.assignee);

	return {
		...sourceTask,
		id: sourceTask.database_id,
		source_key: sourceTask.id,
		project_id: project?.database_id,
		assignee_id: assignee?.database_id,
		description: sourceTask.desc || "",
		due_date: sourceTask.due,
		start_date: sourceTask.start,
		project: project ? { ...project, color: project.color || "indigo" } : null,
		assignee: assignee || null,
	};
});

onMount(() => {
	const closeMenusOnPointerDown = (event) => {
		if (!event.target.closest(".member-options-control")) {
			menuOpen = false;
			roleMenuOpen = false;
		}
	};
	const closeMenusOnEscape = (event) => {
		if (event.key === "Escape") {
			menuOpen = false;
			roleMenuOpen = false;
			removeDialogOpen = false;
		}
	};
	document.addEventListener("pointerdown", closeMenusOnPointerDown, true);
	document.addEventListener("keydown", closeMenusOnEscape);
	return () => {
		document.removeEventListener("pointerdown", closeMenusOnPointerDown, true);
		document.removeEventListener("keydown", closeMenusOnEscape);
	};
});

function toggleOptionsMenu(event) {
	if (menuOpen) {
		menuOpen = false;
		return;
	}

	const bounds = event.currentTarget.getBoundingClientRect();
	menuPosition = {
		left: Math.max(8, Math.min(bounds.right - 220, window.innerWidth - 228)),
		top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 180)),
	};
	menuOpen = true;
}

function openRoleMenu(event) {
	const bounds = event?.currentTarget?.getBoundingClientRect?.() ?? event;
	if (!bounds) return;
	roleMenuPosition = {
		left: Math.max(8, Math.min(bounds.left, window.innerWidth - 308)),
		top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 200)),
	};
	menuOpen = false;
	roleMenuOpen = true;
}

function assignTaskFromMenu() {
	menuOpen = false;
	setNavigationIntent({ type: "create-task", assigneeSourceKey: memberId });
	router.visit(tasksIndex().url);
}

async function copyMemberEmail() {
	await navigator.clipboard.writeText(member.email);
	menuOpen = false;
}

function changeMemberRole(role) {
	const storedMember = D().members.find((item) => item.id === memberId);
	if (storedMember) {
		storedMember.role = role;
		save();
		roleOverride = role;
	}
	roleMenuOpen = false;
}

function resendMemberInvite() {
	menuOpen = false;
	const host = document.querySelector("#toasts");
	if (!host) return;
	const toast = document.createElement("div");
	toast.className = "toast";
	toast.setAttribute("role", "status");
	toast.textContent = `Invite resent to ${member.email}`;
	host.append(toast);
	window.setTimeout(() => toast.remove(), 3000);
}

function removeMember() {
	D().members = D().members.filter((item) => item.id !== memberId);
	D().tasks.forEach((task) => {
		if (task.assignee === memberId) task.assignee = null;
	});
	D().projects.forEach((project) => {
		project.members = project.members.filter((id) => id !== memberId);
	});
	save();
	removeDialogOpen = false;
	router.visit(membersIndex().url);
}

function assignTaskToMember() {
	setNavigationIntent({ type: "create-task", assigneeSourceKey: memberId });
	router.visit(tasksIndex().url);
}

function initials(name) {
	return name
		.split(" ")
		.map((part) => part[0])
		.join("")
		.slice(0, 2);
}

function lastActive() {
	if (member.last == null) return "Never signed in";
	if (member.last < 5) return "Online now";
	return `Active ${ago(minsAgo(member.last))}`;
}

function openTask(task) {
	drawerTaskId = task.database_id;
}

function handleTaskRowKeydown(event, task) {
	if (event.target !== event.currentTarget) return;
	if (event.key === "Enter" || event.key === " ") {
		event.preventDefault();
		openTask(task);
	}
}

async function toggleTask(task) {
	const nextStatus = task.status === "done" ? "todo" : "done";
	const updated = await httpRequest(taskUpdate(task.database_id).url, "PATCH", {
		status: nextStatus,
	});
	workspaceTasks = workspaceTasks.map((item) =>
		String(item.database_id) === String(updated.id)
			? { ...item, status: updated.status }
			: item,
	);
}

function updateDrawerTask(updatedTask) {
	workspaceTasks = workspaceTasks.map((task) =>
		String(task.database_id) === String(updatedTask.id)
			? {
					...task,
					title: updatedTask.title,
					status: updatedTask.status,
					priority: updatedTask.priority,
					due: updatedTask.due_date?.slice(0, 10) || null,
					start: updatedTask.start_date?.slice(0, 10) || null,
					desc: updatedTask.description,
					labels: updatedTask.labels,
					subtasks: updatedTask.subtasks,
					attachments: updatedTask.attachments,
				}
			: task,
	);
}
</script>

<svelte:head><title>{member?.name || 'Member'} · Members</title></svelte:head>

{#if member}
  <AppLayout>
    <div class="page">
      <div class="row" style="gap:18px;margin-bottom:22px;flex-wrap:wrap;align-items:flex-start">
        <span class={`av xl ${member.last != null && member.last < 5 ? 'presence' : ''}`} style={`--c:${member.c}`} aria-label={member.name}>{initials(member.name)}</span>
        <div class="grow" style="min-width:220px"><div class="row" style="gap:10px;flex-wrap:wrap"><h1 style="font-size:var(--fs-2xl);margin:0;font-weight:600;letter-spacing:-.02em">{member.name}</h1><span class="badge">{#if displayedRole === 'Owner'}{@html ic('crown', 11)}{/if}{displayedRole}</span>{#if member.status === 'invited'}<span class="badge amber">Invite pending</span>{/if}</div>
          <div class="muted" style="margin-top:3px">{member.title} · {teams.find(team => team.id === member.team)?.name || member.team}</div>
          <div class="row faint" style="margin-top:8px;gap:14px;font-size:12.5px;flex-wrap:wrap"><span class="row" style="gap:5px">{@html ic('mail', 13)}<span style="user-select:all">{member.email}</span></span><span class="row" style="gap:5px">{@html ic('map-pin', 13)}{member.tz}</span><span class="row" style="gap:5px">{@html ic('clock', 13)}{lastActive()}</span></div></div>
        <div class="row"><Button type="button" variant="secondary" size="md" onclick={() => navigator.clipboard.writeText(member.email)}>{@html ic('copy', 14)}Copy email</Button><Button type="button" variant="primary" size="md" onclick={assignTaskToMember}>{@html ic('plus', 14)}Assign task</Button><div class="member-options-control"><button type="button" class="ibtn" aria-label="More options" aria-haspopup="menu" aria-expanded={menuOpen || roleMenuOpen} onclick={toggleOptionsMenu}>{@html ic('ellipsis', 16)}</button>
          {#if menuOpen}
            <Dropdown
              config={{
                className: 'pop floating enter',
                role: 'menu',
                label: `More options for ${member.name}`,
                style: `left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`,
                items: [
                  { id: 'member-profile', type: 'link', label: 'View profile', href: memberShow(memberId).url, icon: 'user', action: 'close-menu' },
                  { id: 'member-assign', type: 'action', action: 'assign-task', label: 'Assign a task', icon: 'plus' },
                  { id: 'member-copy-email', type: 'action', action: 'copy-email', label: 'Copy email', icon: 'mail' },
                  ...(canManageMember ? [
                    { id: 'member-role-separator', type: 'separator' },
                    { id: 'member-open-role', type: 'action', action: 'change-role', label: 'Change role…', icon: 'shield', stopPropagation: true },
                    ...(member.status === 'invited' ? [{ id: 'member-resend-invite', type: 'action', action: 'resend-invite', label: 'Resend invite', icon: 'send' }] : []),
                    { id: 'member-remove-separator', type: 'separator' },
                    { id: 'member-remove', type: 'action', action: 'remove-member', label: 'Remove from workspace', icon: 'user-minus', danger: true },
                  ] : []),
                ],
              }}
              onAction={(action, _item, context) => {
                if (action === 'close-menu') menuOpen = false;
                if (action === 'assign-task') assignTaskFromMenu();
                if (action === 'copy-email') copyMemberEmail();
                if (action === 'change-role') openRoleMenu(context?.targetBounds);
                if (action === 'resend-invite') resendMemberInvite();
                if (action === 'remove-member') {
                  menuOpen = false;
                  removeDialogOpen = true;
                }
              }}
            />
          {/if}
          {#if roleMenuOpen}
            <Dropdown
              config={{
                className: 'pop floating enter',
                role: 'menu',
                label: `Role for ${member.name}`,
                style: `left:${roleMenuPosition.left}px;top:${roleMenuPosition.top}px;width:300px`,
                items: [
                  { id: 'role-heading', type: 'heading', label: `Role for ${member.name}` },
                  ...['Admin', 'Member', 'Guest'].map(role => ({
                    id: `role-${role.toLowerCase()}`,
                    type: 'element',
                    tag: 'button',
                    elementType: 'button',
                    className: 'mi',
                    role: 'menuitemradio',
                    ariaChecked: displayedRole === role,
                    style: 'height:auto;padding:7px 8px;align-items:flex-start',
                    payload: role,
                    events: { click: 'select-role' },
                    items: [
                      { id: `role-copy-${role}`, type: 'element', tag: 'span', className: 'grow', style: 'white-space:normal', items: [
                        { id: `role-name-${role}`, type: 'element', tag: 'span', style: 'font-weight:500', text: role },
                        { id: `role-description-${role}`, type: 'element', tag: 'span', className: 'faint', style: 'font-size:11.5px;display:block', text: role === 'Admin' ? 'Manage members, settings, and all projects' : role === 'Member' ? 'Create projects and work on tasks' : 'Only sees projects they are invited to' },
                      ] },
                      ...(displayedRole === role ? [{ id: `role-selected-${role}`, type: 'element', tag: 'span', className: 'ck', icon: 'check', iconSize: 14 }] : []),
                    ],
                  })),
                ],
              }}
              onAction={(action, item) => {
                if (action === 'select-role' && typeof item.payload === 'string') changeMemberRole(item.payload);
              }}
            />
          {/if}
        </div></div>
      </div>
      <div class="stats" style="margin-bottom:18px"><div class="stat"><span class="k">Active projects</span><span class="v">{visibleProjects.length}</span></div><div class="stat"><span class="k">Assigned tasks</span><span class="v">{openTasks.length}</span></div><div class="stat"><span class="k">Completed tasks</span><span class="v">{completedTasks.length}</span></div><div class="stat"><span class="k">Overdue</span><span class="v" style={openTasks.some(isOver) ? 'color:var(--red)' : ''}>{openTasks.filter(isOver).length}</span></div></div>
      <div class="grid2"><section class="panel"><div class="tabs" style="padding:0 8px" role="tablist" aria-label="Member tasks"><button type="button" role="tab" class="tab" class:on={tab === 'assigned'} aria-selected={tab === 'assigned'} onclick={() => tab = 'assigned'}>Assigned · {openTasks.length}</button><button type="button" role="tab" class="tab" class:on={tab === 'completed'} aria-selected={tab === 'completed'} onclick={() => tab = 'completed'}>Completed · {completedTasks.length}</button></div>
        {#each displayedTasks as task (task.id)}{@const project = projectById.get(task.project)}<div class={`mini ${task.status === 'done' ? 'done' : ''}`} role="button" tabindex="0" onclick={() => openTask(task)} onkeydown={event => handleTaskRowKeydown(event, task)}>
          <input type="checkbox" class="check round" checked={task.status === 'done'} aria-label={`${task.status === 'done' ? 'Reopen' : 'Complete'} ${task.title}`} onclick={event => event.stopPropagation()} onchange={() => toggleTask(task)} />
          <span data-tip={task.status === 'done' ? 'Done' : task.status === 'progress' ? 'In Progress' : task.status === 'review' ? 'Review' : task.status === 'backlog' ? 'Backlog' : 'To Do'}>{@html stIcon(task.status)}</span><span class="tt">{task.title}</span>
          {#if project}<span class="pj hide-m"><span class="pdot" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}></span><span class="trunc">{project.name}</span></span>{/if}
          <span data-tip={`${task.priority === 'none' || !task.priority ? 'No' : task.priority[0].toUpperCase() + task.priority.slice(1)} priority`}>{@html prIcon(task.priority)}</span><span class="hide-m" style="width:74px;text-align:right">{@html dueHtml(task, false)}</span>
        </div>{:else}<div class="panel-b faint">No tasks here</div>{/each}
      </section>
      <div class="stack"><section class="panel"><div class="panel-h"><h2>Active projects</h2></div>{#each visibleProjects as project (project.id)}<Link class="mini" href={project.database_id ? projectShow(project.database_id).url : '/projects'}><span class="picon" style={`--c:${project.c || '#5A67D8'}`}>{@html ic(project.icon || 'folder', 14)}</span><span class="tt">{project.name}</span><span class="faint" style="font-size:12px">{project.lead === memberId ? 'Lead' : 'Member'}</span></Link>{:else}<div class="panel-b faint">No active projects</div>{/each}</section>
        <section class="panel"><div class="panel-h"><h2>Recent activity</h2></div><div class="panel-b feed">{#each activity as item (item.id)}{@const task = taskById.get(item.task)}{@const project = projectById.get(item.project)}<div class="fitem"><span class="av sm" style={`--c:${member.c}`}>{initials(member.name)}</span><div>{member.name} {item.verb}{#if task} <b>{task.title}</b>{/if}{#if project} in {project.name}{/if}</div><span class="faint">{ago(item.at)}</span></div>{:else}<span class="faint">No recent activity</span>{/each}</div></section></div></div>
    </div>
    {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateDrawerTask} />{/if}
  </AppLayout>
{/if}

{#if removeDialogOpen && member}
  <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (removeDialogOpen = false)}>
    <div class="modal sm" transition:scale={{ duration: 260, start: 0.97 }} role="alertdialog" aria-modal="true" aria-labelledby="remove-member-title">
      <div class="modal-h"><div><h2 id="remove-member-title">Remove {member.name}?</h2><p class="muted" style="font-size:12.5px;margin:4px 0 0">{member.name} will lose access to {workspaceData.ws?.name || 'this workspace'} immediately. Tasks assigned to them will become unassigned.</p></div><button class="ibtn ibtn-sm" type="button" aria-label="Close" onclick={() => removeDialogOpen = false}>{@html ic('x', 16)}</button></div>
      <div class="modal-f"><span class="sp"></span><Button variant="secondary" size="md" type="button" onclick={() => removeDialogOpen = false}>Cancel</Button><Button variant="danger" size="md" type="button" onclick={removeMember}>Remove member</Button></div>
    </div>
  </div>
{/if}
