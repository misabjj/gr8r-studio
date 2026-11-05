import Auth from './Auth'
import WorkspaceController from './WorkspaceController'
import WorkspaceOverviewController from './WorkspaceOverviewController'
import WorkspaceActivityController from './WorkspaceActivityController'
import WorkspaceFavoritesController from './WorkspaceFavoritesController'
import WorkspaceInboxController from './WorkspaceInboxController'
import TaskIndexController from './TaskIndexController'
import MyTasksController from './MyTasksController'
import WorkspaceCalendarController from './WorkspaceCalendarController'
import WorkspaceTimelineController from './WorkspaceTimelineController'
import WorkspaceNotificationsController from './WorkspaceNotificationsController'
import WorkspaceSearchController from './WorkspaceSearchController'
import ProjectController from './ProjectController'
import TaskController from './TaskController'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    WorkspaceController: Object.assign(WorkspaceController, WorkspaceController),
    WorkspaceOverviewController: Object.assign(WorkspaceOverviewController, WorkspaceOverviewController),
    WorkspaceActivityController: Object.assign(WorkspaceActivityController, WorkspaceActivityController),
    WorkspaceFavoritesController: Object.assign(WorkspaceFavoritesController, WorkspaceFavoritesController),
    WorkspaceInboxController: Object.assign(WorkspaceInboxController, WorkspaceInboxController),
    TaskIndexController: Object.assign(TaskIndexController, TaskIndexController),
    MyTasksController: Object.assign(MyTasksController, MyTasksController),
    WorkspaceCalendarController: Object.assign(WorkspaceCalendarController, WorkspaceCalendarController),
    WorkspaceTimelineController: Object.assign(WorkspaceTimelineController, WorkspaceTimelineController),
    WorkspaceNotificationsController: Object.assign(WorkspaceNotificationsController, WorkspaceNotificationsController),
    WorkspaceSearchController: Object.assign(WorkspaceSearchController, WorkspaceSearchController),
    ProjectController: Object.assign(ProjectController, ProjectController),
    TaskController: Object.assign(TaskController, TaskController),
}

export default Controllers