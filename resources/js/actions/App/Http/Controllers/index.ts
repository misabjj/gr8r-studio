import Auth from './Auth'
import WorkspaceController from './WorkspaceController'
import WorkspaceOverviewController from './WorkspaceOverviewController'
import WorkspaceActivityController from './WorkspaceActivityController'
import WorkspaceFavoritesController from './WorkspaceFavoritesController'
import WorkspaceInboxController from './WorkspaceInboxController'
import WorkspaceMembersController from './WorkspaceMembersController'
import MemberController from './MemberController'
import TeamController from './TeamController'
import TaskIndexController from './TaskIndexController'
import MyTasksController from './MyTasksController'
import WorkspaceCalendarController from './WorkspaceCalendarController'
import WorkspaceTimelineController from './WorkspaceTimelineController'
import WorkspaceNotificationsController from './WorkspaceNotificationsController'
import WorkspaceNotificationReadStateController from './WorkspaceNotificationReadStateController'
import WorkspaceSearchController from './WorkspaceSearchController'
import SettingsController from './SettingsController'
import OnboardingController from './OnboardingController'
import SystemController from './SystemController'
import ProjectController from './ProjectController'
import TaskController from './TaskController'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    WorkspaceController: Object.assign(WorkspaceController, WorkspaceController),
    WorkspaceOverviewController: Object.assign(WorkspaceOverviewController, WorkspaceOverviewController),
    WorkspaceActivityController: Object.assign(WorkspaceActivityController, WorkspaceActivityController),
    WorkspaceFavoritesController: Object.assign(WorkspaceFavoritesController, WorkspaceFavoritesController),
    WorkspaceInboxController: Object.assign(WorkspaceInboxController, WorkspaceInboxController),
    WorkspaceMembersController: Object.assign(WorkspaceMembersController, WorkspaceMembersController),
    MemberController: Object.assign(MemberController, MemberController),
    TeamController: Object.assign(TeamController, TeamController),
    TaskIndexController: Object.assign(TaskIndexController, TaskIndexController),
    MyTasksController: Object.assign(MyTasksController, MyTasksController),
    WorkspaceCalendarController: Object.assign(WorkspaceCalendarController, WorkspaceCalendarController),
    WorkspaceTimelineController: Object.assign(WorkspaceTimelineController, WorkspaceTimelineController),
    WorkspaceNotificationsController: Object.assign(WorkspaceNotificationsController, WorkspaceNotificationsController),
    WorkspaceNotificationReadStateController: Object.assign(WorkspaceNotificationReadStateController, WorkspaceNotificationReadStateController),
    WorkspaceSearchController: Object.assign(WorkspaceSearchController, WorkspaceSearchController),
    SettingsController: Object.assign(SettingsController, SettingsController),
    OnboardingController: Object.assign(OnboardingController, OnboardingController),
    SystemController: Object.assign(SystemController, SystemController),
    ProjectController: Object.assign(ProjectController, ProjectController),
    TaskController: Object.assign(TaskController, TaskController),
}

export default Controllers