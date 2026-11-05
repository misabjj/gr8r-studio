export type Project = {
	id: number;
	name: string;
	key: string;
	description: string | null;
	color: string;
	status: string;
	tasks_count?: number;
	tasks?: Task[];
};

export type Task = {
	id: number;
	project_id: number;
	title: string;
	description: string | null;
	status: "backlog" | "todo" | "progress" | "review" | "done";
	priority: "none" | "low" | "medium" | "high" | "urgent";
	due_date: string | null;
	completed_at: string | null;
	project?: Pick<Project, "id" | "name" | "key" | "color">;
};

export type WorkspaceStats = {
	openTasks: number;
	completedTasks: number;
	projects: number;
};
