import Auth from './Auth'
import WorkspaceController from './WorkspaceController'
import WorkspaceOverviewController from './WorkspaceOverviewController'
import TaskIndexController from './TaskIndexController'
import WorkspaceCalendarController from './WorkspaceCalendarController'
import WorkspaceTimelineController from './WorkspaceTimelineController'
import ProjectController from './ProjectController'
import TaskController from './TaskController'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    WorkspaceController: Object.assign(WorkspaceController, WorkspaceController),
    WorkspaceOverviewController: Object.assign(WorkspaceOverviewController, WorkspaceOverviewController),
    TaskIndexController: Object.assign(TaskIndexController, TaskIndexController),
    WorkspaceCalendarController: Object.assign(WorkspaceCalendarController, WorkspaceCalendarController),
    WorkspaceTimelineController: Object.assign(WorkspaceTimelineController, WorkspaceTimelineController),
    ProjectController: Object.assign(ProjectController, ProjectController),
    TaskController: Object.assign(TaskController, TaskController),
}

export default Controllers